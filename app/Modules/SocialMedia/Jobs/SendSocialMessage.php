<?php

namespace App\Modules\SocialMedia\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Services\SocialPlatformRegistry;
use App\Modules\SocialMedia\Services\XDirectMessageClient;
use App\Modules\SocialMedia\Services\XTokenManager;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Modules\SocialMedia\Models\SocialAccount;

class SendSocialMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $messageId;

    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        $message = ConversationMessage::query()
            ->find($this->messageId);
        if (!$message) {
            return;
        }

        TenantContext::setCurrentId((int) $message->tenant_id);
        $message->load('conversation');

        if (!$message->conversation || $message->conversation->channel !== 'social_dm') {
            return;
        }

        $platform = $message->conversation->metadata['platform'] ?? 'facebook';
        $platformConfig = app(SocialPlatformRegistry::class)->find((string) $platform);
        $recipient = $message->conversation->contact_external_id;
        $accountId = $message->conversation->instance_id;
        $account = $accountId
            ? SocialAccount::query()->where('tenant_id', (int) $message->tenant_id)->find($accountId)
            : null;
        $graphVersion = config('services.meta.graph_version', 'v22.0');
        $pageToken = $account?->access_token;
        $pageId = $account?->page_id;
        $igBusinessId = $account?->ig_business_id;

        if (!$platformConfig || !($platformConfig['supports_outbound_messages'] ?? false)) {
            $errorMessage = 'Outbound connector untuk platform ini belum aktif.';
            $message->update(['status' => 'error', 'error_message' => $errorMessage]);
            $account?->updateOperationalMetadata([
                'last_outbound_error_at' => now()->toDateTimeString(),
                'last_outbound_error_message' => $errorMessage,
            ]);

            return;
        }

        if (!$account || !$pageToken) {
            $message->update(['status' => 'error', 'error_message' => 'Social account token is not connected for this tenant.']);
            $account?->updateOperationalMetadata([
                'last_outbound_error_at' => now()->toDateTimeString(),
                'last_outbound_error_message' => 'Social account token is not connected for this tenant.',
            ]);
            return;
        }

        try {
            if ($platform === 'x') {
                /** @var XDirectMessageClient $xClient */
                $xClient = app(XDirectMessageClient::class);
                /** @var XTokenManager $xTokenManager */
                $xTokenManager = app(XTokenManager::class);
                $dmConversationId = trim((string) data_get($message->conversation->metadata, 'x_dm_conversation_id', ''));
                $mediaIds = [];

                if ($message->media_url) {
                    if (!in_array((string) $message->type, ['image', 'video'], true)) {
                        $errorMessage = 'X hanya mendukung lampiran image, gif, atau video.';
                        $message->update(['status' => 'error', 'error_message' => $errorMessage]);
                        $account->updateOperationalMetadata([
                            'last_outbound_error_at' => now()->toDateTimeString(),
                            'last_outbound_error_message' => $errorMessage,
                        ]);
                        return;
                    }

                    $localPath = $this->resolvePublicStoragePath((string) $message->media_url);
                    if ($localPath === null) {
                        $errorMessage = 'Media X harus berasal dari file lokal workspace.';
                        $message->update(['status' => 'error', 'error_message' => $errorMessage]);
                        $account->updateOperationalMetadata([
                            'last_outbound_error_at' => now()->toDateTimeString(),
                            'last_outbound_error_message' => $errorMessage,
                        ]);
                        return;
                    }

                    $uploadResponse = $xClient->uploadMedia($pageToken, $localPath, (string) ($message->media_mime ?: 'image/jpeg'));
                    if (in_array($uploadResponse->status(), [401, 403], true) && $xTokenManager->canRefresh($account)) {
                        $refreshedToken = $xTokenManager->refreshAccessToken($account);
                        if ($refreshedToken) {
                            $pageToken = $refreshedToken;
                            $uploadResponse = $xClient->uploadMedia($pageToken, $localPath, (string) ($message->media_mime ?: 'image/jpeg'));
                        }
                    }

                    if (!$uploadResponse->successful()) {
                        $errorMessage = $uploadResponse->body();
                        $message->update(['status' => 'error', 'error_message' => $errorMessage]);
                        $account->updateOperationalMetadata([
                            'last_outbound_error_at' => now()->toDateTimeString(),
                            'last_outbound_error_message' => mb_substr($errorMessage, 0, 500),
                        ]);
                        return;
                    }

                    $mediaId = trim((string) (data_get($uploadResponse->json(), 'data.id')
                        ?? data_get($uploadResponse->json(), 'media_id')
                        ?? data_get($uploadResponse->json(), 'data.media_id')));
                    if ($mediaId === '') {
                        $errorMessage = 'X tidak mengembalikan media_id yang valid.';
                        $message->update(['status' => 'error', 'error_message' => $errorMessage]);
                        $account->updateOperationalMetadata([
                            'last_outbound_error_at' => now()->toDateTimeString(),
                            'last_outbound_error_message' => $errorMessage,
                        ]);
                        return;
                    }

                    $mediaIds = [$mediaId];
                }

                $resp = $dmConversationId !== ''
                    ? $xClient->sendToConversation($pageToken, $dmConversationId, (string) $message->body, $mediaIds)
                    : ($mediaIds === []
                        ? $xClient->sendTextToParticipant($pageToken, (string) $recipient, (string) $message->body)
                        : $xClient->sendToParticipantWithMedia($pageToken, (string) $recipient, (string) $message->body, $mediaIds));

                if (in_array($resp->status(), [401, 403], true) && $xTokenManager->canRefresh($account)) {
                    $refreshedToken = $xTokenManager->refreshAccessToken($account);
                    if ($refreshedToken) {
                        $pageToken = $refreshedToken;
                        $resp = $dmConversationId !== ''
                            ? $xClient->sendToConversation($pageToken, $dmConversationId, (string) $message->body, $mediaIds)
                            : ($mediaIds === []
                                ? $xClient->sendTextToParticipant($pageToken, (string) $recipient, (string) $message->body)
                                : $xClient->sendToParticipantWithMedia($pageToken, (string) $recipient, (string) $message->body, $mediaIds));
                    }
                }

                if ($resp->successful()) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'external_message_id' => data_get($resp->json(), 'data.event_id')
                            ?? data_get($resp->json(), 'data.id')
                            ?? $message->external_message_id,
                    ]);
                    $account->updateOperationalMetadata([
                        'last_outbound_at' => now()->toDateTimeString(),
                        'last_outbound_error_at' => null,
                        'last_outbound_error_message' => null,
                    ]);
                } else {
                    $errorMessage = $resp->body();
                    $message->update([
                        'status' => 'error',
                        'error_message' => $errorMessage,
                    ]);
                    $account->updateOperationalMetadata([
                        'last_outbound_error_at' => now()->toDateTimeString(),
                        'last_outbound_error_message' => mb_substr($errorMessage, 0, 500),
                    ]);
                }

                return;
            }

            if ($platform === 'instagram') {
                if (!$igBusinessId) {
                    $message->update(['status' => 'error', 'error_message' => 'META_IG_BUSINESS_ID not set']);
                    $account->updateOperationalMetadata([
                        'last_outbound_error_at' => now()->toDateTimeString(),
                        'last_outbound_error_message' => 'META_IG_BUSINESS_ID not set',
                    ]);
                    return;
                }
                $url = "https://graph.facebook.com/{$graphVersion}/{$igBusinessId}/messages";
            } else { // facebook page
                if (!$pageId) {
                    $message->update(['status' => 'error', 'error_message' => 'META_PAGE_ID not set']);
                    $account->updateOperationalMetadata([
                        'last_outbound_error_at' => now()->toDateTimeString(),
                        'last_outbound_error_message' => 'META_PAGE_ID not set',
                    ]);
                    return;
                }
                $url = "https://graph.facebook.com/{$graphVersion}/{$pageId}/messages";
            }

            $payload = [
                'messaging_type' => 'RESPONSE',
                'recipient' => ['id' => $recipient],
                'message' => $this->buildMessagePayload($message),
                'access_token' => $pageToken,
            ];

            $resp = Http::timeout(10)->post($url, $payload);
            if ($resp->successful()) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'external_message_id' => $resp->json('message_id') ?? $resp->json('id') ?? $message->external_message_id,
                ]);
                $account->updateOperationalMetadata([
                    'last_outbound_at' => now()->toDateTimeString(),
                    'last_outbound_error_at' => null,
                    'last_outbound_error_message' => null,
                ]);
            } else {
                $errorMessage = $resp->body();
                $message->update([
                    'status' => 'error',
                    'error_message' => $errorMessage,
                ]);
                $account->updateOperationalMetadata([
                    'last_outbound_error_at' => now()->toDateTimeString(),
                    'last_outbound_error_message' => mb_substr($errorMessage, 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendSocialMessage failed', ['message_id' => $message->id, 'error' => $e->getMessage()]);
            $message->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            $account->updateOperationalMetadata([
                'last_outbound_error_at' => now()->toDateTimeString(),
                'last_outbound_error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }
    }

    private function buildMessagePayload(ConversationMessage $message): array
    {
        if ($message->type === 'text' || !$message->media_url) {
            return ['text' => (string) $message->body];
        }

        return [
            'attachment' => [
                'type' => $this->resolveAttachmentType((string) $message->type),
                'payload' => [
                    'url' => (string) $message->media_url,
                ],
            ],
        ];
    }

    private function resolveAttachmentType(string $type): string
    {
        return match ($type) {
            'document' => 'file',
            'image', 'video', 'audio' => $type,
            default => 'file',
        };
    }

    private function resolvePublicStoragePath(string $publicUrl): ?string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $publicUrl = trim($publicUrl);

        if ($publicUrl === '') {
            return null;
        }

        if ($appUrl !== '' && Str::startsWith($publicUrl, $appUrl . '/storage/')) {
            return public_path('storage/' . ltrim(Str::after($publicUrl, $appUrl . '/storage/'), '/'));
        }

        if (Str::startsWith($publicUrl, '/storage/')) {
            return public_path(ltrim($publicUrl, '/'));
        }

        if (Str::contains($publicUrl, '/storage/')) {
            return public_path('storage/' . ltrim(Str::after($publicUrl, '/storage/'), '/'));
        }

        return null;
    }
}


