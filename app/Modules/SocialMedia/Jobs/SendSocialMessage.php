<?php

namespace App\Modules\SocialMedia\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
            ->where('tenant_id', TenantContext::currentId())
            ->with('conversation')
            ->find($this->messageId);
        if (!$message || !$message->conversation || $message->conversation->channel !== 'social_dm') {
            return;
        }

        $platform = $message->conversation->metadata['platform'] ?? 'facebook';
        $recipient = $message->conversation->contact_external_id;
        $accountId = $message->conversation->instance_id;
        $account = $accountId ? SocialAccount::find($accountId) : null;
        $graphVersion = config('services.meta.graph_version', 'v22.0');
        $pageToken = $account->access_token ?? config('services.meta.page_token');
        $pageId = $account->page_id ?? config('services.meta.page_id');
        $igBusinessId = $account->ig_business_id ?? config('services.meta.ig_business_id');

        if (!$pageToken) {
            $message->update(['status' => 'error', 'error_message' => 'META_PAGE_TOKEN not set']);
            return;
        }

        try {
            if ($platform === 'instagram') {
                if (!$igBusinessId) {
                    $message->update(['status' => 'error', 'error_message' => 'META_IG_BUSINESS_ID not set']);
                    return;
                }
                $url = "https://graph.facebook.com/{$graphVersion}/{$igBusinessId}/messages";
                $payload = [
                    'messaging_type' => 'RESPONSE',
                    'recipient' => ['id' => $recipient],
                    'message' => ['text' => $message->body],
                    'access_token' => $pageToken,
                ];
            } else { // facebook page
                if (!$pageId) {
                    $message->update(['status' => 'error', 'error_message' => 'META_PAGE_ID not set']);
                    return;
                }
                $url = "https://graph.facebook.com/{$graphVersion}/{$pageId}/messages";
                $payload = [
                    'messaging_type' => 'RESPONSE',
                    'recipient' => ['id' => $recipient],
                    'message' => ['text' => $message->body],
                    'access_token' => $pageToken,
                ];
            }

            $resp = Http::timeout(10)->post($url, $payload);
            if ($resp->successful()) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'external_message_id' => $resp->json('message_id') ?? $resp->json('id') ?? $message->external_message_id,
                ]);
            } else {
                $message->update([
                    'status' => 'error',
                    'error_message' => $resp->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SendSocialMessage failed', ['message_id' => $message->id, 'error' => $e->getMessage()]);
            $message->update(['status' => 'error', 'error_message' => $e->getMessage()]);
        }
    }
}


