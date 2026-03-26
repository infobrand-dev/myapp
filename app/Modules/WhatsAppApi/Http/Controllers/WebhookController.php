<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Services\ConversationBotManager;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Models\WhatsAppInstanceChatbotIntegration;
use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use App\Modules\WhatsAppApi\Support\ConversationAutoAssigner;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Http\RedirectResponse;

class WebhookController extends Controller
{
    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    public function __construct(
        private readonly ConversationAutoAssigner $autoAssigner,
        private readonly InboxMessageIngester $ingester,
    )
    {
    }

    public function verify(Request $request)
    {
        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode'));
        $token = (string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token'));
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge'));

        if ($mode !== 'subscribe') {
            return response('Invalid mode', Response::HTTP_FORBIDDEN);
        }

        $instance = WhatsAppInstance::query()
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->get()
            ->first(function (WhatsAppInstance $instance) use ($token): bool {
                $verifyToken = $this->instanceSettingValue($instance, ['wa_cloud_verify_token', 'verify_token']);
                return $verifyToken !== null && hash_equals($verifyToken, $token);
            });

        if (!$instance) {
            return response('Invalid verify token', Response::HTTP_UNAUTHORIZED);
        }

        TenantContext::setCurrentId((int) $instance->tenant_id);

        return response($challenge, Response::HTTP_OK)->header('Content-Type', 'text/plain');
    }

    public function inbound(Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();
        $payload = $request->all();

        $tenantId = $this->resolveTenantIdForPublicPayload($payload, $request);
        if ($tenantId !== null) {
            TenantContext::setCurrentId($tenantId);
        }

        $event = $this->createOrTouchWebhookEvent($request, $payload, $rawPayload);

        if ($event->process_status === 'processed') {
            return response()->json(['stored' => true, 'deduplicated' => true, 'mode' => 'event']);
        }

        $result = $this->processStoredEvent($event, $payload, $request);

        if (($result['status_code'] ?? 200) !== 200) {
            return response()->json(['message' => $result['message'] ?? 'Webhook processing failed'], $result['status_code']);
        }

        return response()->json($result['payload'] ?? ['stored' => true]);
    }

    public function reprocessEvent(WhatsAppWebhookEvent $event): RedirectResponse
    {
        if (!$event->canReprocess()) {
            return back()->with('status', 'Webhook event ini tidak bisa direprocess otomatis. Hanya gateway atau cloud dengan signature valid yang didukung.');
        }

        $event->update([
            'process_status' => 'pending',
            'processed_at' => null,
            'error_message' => null,
        ]);

        $result = $this->processStoredEvent($event, (array) ($event->payload ?? []), null, true);
        $status = $result['payload']['stored'] ?? false
            ? 'Webhook berhasil direprocess.'
            : ($result['message'] ?? 'Webhook direprocess dengan hasil tidak diketahui.');

        return back()->with('status', $status);
    }

    private function processStoredEvent(WhatsAppWebhookEvent $event, array $payload, ?Request $request = null, bool $trustedReplay = false): array
    {
        if ($this->looksLikeCloudPayload($payload)) {
            try {
                $result = $this->handleCloudPayload($payload, $request, $event, $trustedReplay);
                if (!($result['ok'] ?? false)) {
                    $reason = (string) ($result['reason'] ?? 'Cloud payload ignored');
                    $status = (string) ($result['status'] ?? 'failed');

                    if ($status === 'unauthorized') {
                        $this->markWebhookEventFailed($event, $reason, false);
                        return [
                            'status_code' => Response::HTTP_UNAUTHORIZED,
                            'message' => $reason,
                        ];
                    }

                    $this->markWebhookEventFailed($event, $reason, null, $status === 'ignored' ? 'ignored' : 'failed');
                    return [
                        'status_code' => Response::HTTP_OK,
                        'message' => $reason,
                        'payload' => ['stored' => false, 'ignored' => true, 'message' => $reason],
                    ];
                }

                if (($result['signature_valid'] ?? null) === false) {
                    $this->markWebhookEventFailed($event, 'Invalid signature', false);
                    return [
                        'status_code' => Response::HTTP_UNAUTHORIZED,
                        'message' => 'Invalid signature',
                    ];
                }
            } catch (Throwable $e) {
                Log::error('WhatsApp cloud webhook process failed', ['error' => $e->getMessage()]);
                $this->markWebhookEventFailed($event, $e->getMessage());
                return [
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Webhook processing failed',
                ];
            }

            $this->markWebhookEventProcessed($event);
            return [
                'status_code' => Response::HTTP_OK,
                'payload' => ['stored' => true, 'mode' => 'cloud'],
            ];
        }

        $data = validator($payload, [
            'token' => [($trustedReplay && $event->instance_id) ? 'nullable' : 'required', 'string'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
            'instance_key' => ['nullable', 'string'],
        ])->validate();

        $instance = $this->resolveGatewayInstanceForPayload($data);
        if (!$instance && $trustedReplay && $event->instance_id) {
            $instance = WhatsAppInstance::query()
                ->where('tenant_id', $event->tenant_id ?: $this->tenantId())
                ->find($event->instance_id);
        }

        if (!$instance) {
            $this->markWebhookEventFailed($event, 'Invalid token');
            return [
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Invalid token',
            ];
        }

        TenantContext::setCurrentId((int) $instance->tenant_id);

        $event->update([
            'tenant_id' => $instance->tenant_id,
            'instance_id' => $instance->id,
            'provider' => strtolower((string) ($instance->provider ?? 'gateway')),
        ]);

        $isIncoming = ($data['direction'] ?? 'in') === 'in';
        $result = $this->ingester->ingest(new InboxMessageEnvelope(
            channel: 'wa_api',
            instanceId: (int) $instance->id,
            conversationExternalId: null,
            contactExternalId: $data['contact_id'],
            contactName: $data['contact_name'] ?? null,
            direction: $data['direction'] ?? 'in',
            type: 'text',
            body: $data['message'],
            externalMessageId: $data['external_message_id'] ?? null,
            payload: $this->sanitizeWebhookPayload($request?->all() ?? $payload),
            messageStatus: $isIncoming ? 'delivered' : 'sent',
            ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
            incrementUnread: $isIncoming,
            writeActivityLog: false,
            broadcast: false,
        ));
        $conversation = $result->conversation;
        $msg = $result->message;

        if ($result->deduplicated) {
            $this->markWebhookEventProcessed($event);
            return [
                'status_code' => Response::HTTP_OK,
                'payload' => ['stored' => true, 'deduplicated' => true],
            ];
        }

        if ($result->conversationWasCreated) {
            $this->autoAssigner->assignIfEligible($conversation, $instance);
            $conversation->refresh();
        }

        $chatbot = $this->chatbotIntegration($instance);
        $shouldAutoReply = $chatbot['auto_reply']
            && $isIncoming
            && $this->shouldAutoReply($conversation, $chatbot['chatbot_account_id'], (string) ($msg->body ?? ''));
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $msg->id, $chatbot['chatbot_account_id']);
        }

        $this->markWebhookEventProcessed($event);
        return [
            'status_code' => Response::HTTP_OK,
            'payload' => ['stored' => true],
        ];
    }

    private function createOrTouchWebhookEvent(Request $request, array $payload, string $rawPayload): WhatsAppWebhookEvent
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $eventKey = hash('sha256', $rawPayload . '|' . $signature);

        $event = WhatsAppWebhookEvent::query()
            ->where('tenant_id', $this->tenantId())
            ->where('event_key', $eventKey)
            ->first();
        if (!$event) {
            return WhatsAppWebhookEvent::create([
                'tenant_id' => $this->tenantId(),
                'provider' => $this->looksLikeCloudPayload($payload) ? 'cloud' : 'gateway',
                'event_key' => $eventKey,
                'headers' => $this->sanitizeWebhookHeaders($request->headers->all()),
                'payload' => $this->sanitizeWebhookPayload($payload),
                'process_status' => 'pending',
                'retry_count' => 0,
                'received_at' => now(),
            ]);
        }

        $event->update([
            'tenant_id' => $this->tenantId(),
            'headers' => $this->sanitizeWebhookHeaders($request->headers->all()),
            'payload' => $this->sanitizeWebhookPayload($payload),
            'retry_count' => (int) $event->retry_count + 1,
            'received_at' => now(),
        ]);

        return $event;
    }

    private function markWebhookEventProcessed(WhatsAppWebhookEvent $event): void
    {
        $event->update([
            'process_status' => 'processed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    private function markWebhookEventFailed(WhatsAppWebhookEvent $event, string $error, ?bool $signatureValid = null, string $processStatus = 'failed'): void
    {
        $updates = [
            'process_status' => $processStatus,
            'error_message' => mb_substr($error, 0, 65535),
            'processed_at' => now(),
        ];

        if ($signatureValid !== null) {
            $updates['signature_valid'] = $signatureValid;
        }

        $event->update($updates);
    }

    private function looksLikeCloudPayload(array $payload): bool
    {
        return (string) ($payload['object'] ?? '') === 'whatsapp_business_account'
            || array_key_exists('entry', $payload);
    }

    private function isValidCloudSignature(Request $request, ?WhatsAppInstance $instance): bool
    {
        $secret = $instance ? $this->instanceSettingValue($instance, ['wa_cloud_app_secret', 'app_secret']) : null;
        if ($secret === null || trim($secret) === '') {
            return false;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $signature);
    }

    private function handleCloudPayload(array $payload, ?Request $request, WhatsAppWebhookEvent $event, bool $trustedReplay = false): array
    {
        $matchedInstance = false;
        $unmatchedTargets = [];

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $instance = $this->resolveCloudInstance($value, (array) $entry, (array) $change, $request);
                if (!$instance) {
                    $candidatePhoneId = (string) Arr::get($value, 'metadata.phone_number_id', '');
                    $candidateBusinessId = (string) (
                        Arr::get($entry, 'id', '')
                        ?: Arr::get($value, 'business_account_id', '')
                        ?: Arr::get($value, 'waba_id', '')
                    );
                    if ($candidatePhoneId !== '') {
                        $unmatchedTargets[] = 'phone_number_id:' . $candidatePhoneId;
                    } elseif ($candidateBusinessId !== '') {
                        $unmatchedTargets[] = 'waba_id:' . $candidateBusinessId;
                    }
                    continue;
                }
                $matchedInstance = true;

                $event->update([
                    'tenant_id' => $instance->tenant_id,
                    'instance_id' => $instance->id,
                    'provider' => 'cloud',
                ]);

                if (!$trustedReplay) {
                    if (!$request) {
                        return [
                            'ok' => false,
                            'status' => 'failed',
                            'reason' => 'Stored cloud event membutuhkan request asli atau signature valid tersimpan untuk direprocess.',
                        ];
                    }

                    if (!$this->isValidCloudSignature($request, $instance)) {
                        $event->update(['signature_valid' => false]);
                        return [
                            'ok' => false,
                            'status' => 'unauthorized',
                            'reason' => 'Invalid signature',
                            'signature_valid' => false,
                        ];
                    }
                }

                $event->update(['signature_valid' => true]);

                $this->handleCloudIncomingMessages($instance, $value);
                $this->handleCloudMessageStatuses($value);
                $this->handleCloudTemplateStatusUpdates($instance, (array) $change, $value);
            }
        }

        if (!$matchedInstance) {
            $detail = !empty($unmatchedTargets)
                ? implode(', ', array_unique($unmatchedTargets))
                : 'payload tidak mengandung target instance cloud yang dikenali';

            return [
                'ok' => false,
                'status' => 'ignored',
                'reason' => 'Cloud webhook diabaikan: instance tidak ditemukan untuk ' . $detail,
            ];
        }

        return [
            'ok' => true,
            'status' => 'processed',
            'signature_valid' => true,
        ];
    }

    private function resolveCloudInstance(array $value, array $entry = [], array $change = [], ?Request $request = null): ?WhatsAppInstance
    {
        $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id', '');
        if ($phoneNumberId === '') {
            $businessId = (string) (
                Arr::get($entry, 'id', '')
                ?: Arr::get($value, 'business_account_id', '')
                ?: Arr::get($value, 'waba_id', '')
                ?: Arr::get($change, 'value.business_account_id', '')
            );

            if ($businessId === '') {
                return $request ? $this->resolveCloudInstanceBySignature($request) : null;
            }

            $instance = WhatsAppInstance::where('provider', 'cloud')
                ->where('is_active', true)
                ->where('cloud_business_account_id', $businessId)
                ->first();

            return $instance ?: ($request ? $this->resolveCloudInstanceBySignature($request) : null);
        }

        $instance = WhatsAppInstance::where('provider', 'cloud')
            ->where('is_active', true)
            ->where('phone_number_id', $phoneNumberId)
            ->first();

        return $instance ?: ($request ? $this->resolveCloudInstanceBySignature($request) : null);
    }

    private function resolveCloudInstanceBySignature(Request $request): ?WhatsAppInstance
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if (!str_starts_with($signature, 'sha256=')) {
            return null;
        }

        $matches = WhatsAppInstance::query()
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->get()
            ->filter(fn (WhatsAppInstance $instance) => $this->isValidCloudSignature($request, $instance))
            ->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    private function resolveTenantIdForPublicPayload(array $payload, ?Request $request = null): ?int
    {
        if ($this->looksLikeCloudPayload($payload)) {
            $instance = $this->resolvePublicCloudInstance($payload, $request);

            return $instance ? (int) $instance->tenant_id : null;
        }

        $instance = $this->resolveGatewayInstanceForPayload($payload);

        return $instance ? (int) $instance->tenant_id : null;
    }

    private function resolvePublicCloudInstance(array $payload, ?Request $request = null): ?WhatsAppInstance
    {
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $instance = $this->resolveCloudInstance($value, (array) $entry, (array) $change, $request);

                if ($instance) {
                    return $instance;
                }
            }
        }

        return $request ? $this->resolveCloudInstanceBySignature($request) : null;
    }

    private function resolveGatewayInstanceForPayload(array $payload): ?WhatsAppInstance
    {
        $token = trim((string) ($payload['token'] ?? ''));
        if ($token === '') {
            return null;
        }

        $instanceKey = trim((string) ($payload['instance_key'] ?? ''));
        $tokenHash = hash('sha256', $token);

        return WhatsAppInstance::query()
            ->where('is_active', true)
            ->where(function ($query) use ($token, $tokenHash) {
                $query->where('api_token_hash', $tokenHash)
                    ->orWhere('api_token', $token);
            })
            ->when($instanceKey !== '', fn ($query) => $query->where('id', $instanceKey))
            ->get()
            ->first(fn (WhatsAppInstance $instance) => hash_equals((string) $instance->api_token, $token));
    }

    private function handleCloudIncomingMessages(WhatsAppInstance $instance, array $value): void
    {
        $contacts = collect((array) ($value['contacts'] ?? []))
            ->keyBy(fn ($contact) => (string) Arr::get($contact, 'wa_id', ''));

        foreach ((array) ($value['messages'] ?? []) as $item) {
            $from = (string) Arr::get($item, 'from', '');
            if ($from === '') {
                continue;
            }

            $waMessageId = (string) Arr::get($item, 'id', '');

            [$type, $body, $mediaUrl, $mediaMime] = $this->extractIncomingMessageData((array) $item);
            $result = $this->ingester->ingest(new InboxMessageEnvelope(
                channel: 'wa_api',
                instanceId: (int) $instance->id,
                conversationExternalId: null,
                contactExternalId: $from,
                contactName: Arr::get($contacts->get($from), 'profile.name'),
                direction: 'in',
                type: $type,
                body: $body,
                externalMessageId: $waMessageId !== '' ? $waMessageId : null,
                payload: $item,
                messageStatus: 'delivered',
                mediaUrl: $mediaUrl,
                mediaMime: $mediaMime,
                occurredAt: $this->parseTimestamp(Arr::get($item, 'timestamp')),
                ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
                incrementUnread: true,
                writeActivityLog: false,
                broadcast: false,
            ));
            $conversation = $result->conversation;
            $message = $result->message;

            if ($result->deduplicated) {
                continue;
            }

            if ($result->conversationWasCreated) {
                $this->autoAssigner->assignIfEligible($conversation, $instance);
                $conversation->refresh();
            }

            $chatbot = $this->chatbotIntegration($instance);
            if ($chatbot['auto_reply'] && $this->shouldAutoReply($conversation, $chatbot['chatbot_account_id'], (string) ($message->body ?? ''), (array) $item)) {
                GenerateAiReply::dispatch($conversation->id, $message->id, $chatbot['chatbot_account_id']);
            }
        }
    }

    private function chatbotIntegration(WhatsAppInstance $instance): array
    {
        $fallback = [
            'auto_reply' => (bool) ($instance->auto_reply ?? false),
            'chatbot_account_id' => $instance->chatbot_account_id ? (int) $instance->chatbot_account_id : null,
        ];

        if (!Schema::hasTable('whatsapp_instance_chatbot_integrations')) {
            return $fallback;
        }

        /** @var WhatsAppInstanceChatbotIntegration|null $integration */
        $integration = $instance->chatbotIntegration()->first();
        if (!$integration) {
            return $fallback;
        }

        return [
            'auto_reply' => (bool) $integration->auto_reply,
            'chatbot_account_id' => $integration->chatbot_account_id ? (int) $integration->chatbot_account_id : null,
        ];
    }

    private function shouldAutoReply(Conversation $conversation, ?int $chatbotAccountId, string $incomingBody, array $incomingPayload = []): bool
    {
        if (!$chatbotAccountId) {
            return false;
        }

        $account = $this->resolveChatbotAccount($chatbotAccountId);
        if (!$account) {
            return false;
        }

        $mode = strtolower((string) ($account->operation_mode ?? 'ai_only'));
        if ($mode === 'ai_only') {
            return true;
        }

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if ((bool) ($metadata['auto_reply_paused'] ?? false)) {
            return false;
        }

        $handoffReason = $this->detectHandoffReason($incomingBody, $incomingPayload);
        if ($handoffReason !== null) {
            $this->sendHumanHandoffAcknowledgement($conversation);
            $this->markConversationHandoff($conversation, $handoffReason);
            return false;
        }

        return true;
    }

    private function resolveChatbotAccount(int $chatbotAccountId)
    {
        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        if (!class_exists($chatbotClass) || !Schema::hasTable('chatbot_accounts')) {
            return null;
        }

        return $chatbotClass::query()
            ->where('status', 'active')
            ->find($chatbotAccountId);
    }

    private function detectHandoffReason(string $incomingBody, array $incomingPayload = []): ?string
    {
        if ($this->isHumanHandoffInteractiveReply($incomingPayload)) {
            return 'interactive_request_human';
        }

        return $this->shouldHandoffToHuman($incomingBody)
            ? 'keyword_request_human'
            : null;
    }

    private function shouldHandoffToHuman(string $text): bool
    {
        $haystack = mb_strtolower(trim($text));
        if ($haystack === '') {
            return false;
        }

        $keywords = [
            'agent',
            'admin',
            'operator',
            'manusia',
            'human',
            'cs',
            'customer service',
            'staff',
        ];

        foreach ($keywords as $keyword) {
            if (mb_stripos($haystack, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isHumanHandoffInteractiveReply(array $payload): bool
    {
        $interactiveType = strtolower((string) Arr::get($payload, 'interactive.type', ''));
        if (!in_array($interactiveType, ['button_reply', 'list_reply'], true)) {
            return false;
        }

        $id = trim((string) (
            Arr::get($payload, 'interactive.button_reply.id')
            ?: Arr::get($payload, 'interactive.list_reply.id')
        ));

        if ($id === '') {
            return false;
        }

        $normalizedId = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $id), '_'));

        $handoffIds = [
            'handoff_human',
            'request_human',
            'hubungi_human',
            'hubungi_cs',
            'hubungi_admin',
            'connect_human',
            'connect_agent',
            'talk_to_human',
            'talk_to_agent',
        ];

        return in_array($normalizedId, $handoffIds, true);
    }

    private function markConversationHandoff(Conversation $conversation, string $reason): void
    {
        app(ConversationBotManager::class)->pause($conversation, $reason);
    }

    private function sendHumanHandoffAcknowledgement(Conversation $conversation): void
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if ((bool) ($metadata['auto_reply_paused'] ?? false)) {
            return;
        }

        $instance = $conversation->instance_id
            ? WhatsAppInstance::query()->where('tenant_id', $this->tenantId())->find($conversation->instance_id)
            : null;
        $body = $this->humanHandoffAcknowledgementMessage($instance);

        $message = ConversationMessage::create([
            'tenant_id' => $this->tenantId(),
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'direction' => 'out',
            'type' => 'text',
            'body' => $body,
            'status' => 'queued',
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_outgoing_at' => now(),
        ]);

        SendWhatsAppMessage::dispatch($message->id);

        try {
            broadcast(new ConversationMessageCreated($message))->toOthers();
        } catch (Throwable $e) {
            Log::warning('Broadcast handoff acknowledgement skipped', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function humanHandoffAcknowledgementMessage(?WhatsAppInstance $instance): string
    {
        $settings = $instance && is_array($instance->settings) ? $instance->settings : [];
        $configured = trim((string) Arr::get($settings, 'handoff_ack_message', ''));

        return $configured !== ''
            ? $configured
            : 'Baik, Anda akan kami hubungkan dengan Customer Service kami. Mohon tunggu, tim kami akan merespons secepatnya.';
    }

    private function handleCloudMessageStatuses(array $value): void
    {
        foreach ((array) ($value['statuses'] ?? []) as $statusItem) {
            $waMessageId = (string) Arr::get($statusItem, 'id', '');
            if ($waMessageId === '') {
                continue;
            }

            $message = ConversationMessage::where('external_message_id', $waMessageId)
                ->where('tenant_id', $this->tenantId())
                ->where('direction', 'out')
                ->latest('id')
                ->first();

            if (!$message) {
                continue;
            }

            $status = strtolower((string) Arr::get($statusItem, 'status', ''));
            $timestamp = $this->parseTimestamp(Arr::get($statusItem, 'timestamp'));

            $updates = [];
            if ($status === 'sent') {
                $updates['status'] = 'sent';
                $updates['sent_at'] = $timestamp ?? now();
            } elseif ($status === 'delivered') {
                $updates['status'] = 'delivered';
                $updates['delivered_at'] = $timestamp ?? now();
            } elseif ($status === 'read') {
                $updates['status'] = 'read';
                $updates['read_at'] = $timestamp ?? now();
            } elseif (in_array($status, ['failed', 'undelivered', 'rejected'], true)) {
                $updates['status'] = $this->classifyCloudFailureStatus($statusItem);
                $updates['error_message'] = $this->cloudFailureErrorMessage($statusItem);
            } else {
                continue;
            }

            $message->update($updates);

            if ($message->conversation_id) {
                Conversation::query()
                    ->where('tenant_id', $this->tenantId())
                    ->whereKey($message->conversation_id)
                    ->update([
                    'last_outgoing_at' => $timestamp ?? now(),
                    ]);
            }
        }
    }

    private function handleCloudTemplateStatusUpdates(WhatsAppInstance $instance, array $change, array $value): void
    {
        $field = strtolower((string) Arr::get($change, 'field', ''));
        $items = (array) ($value['statuses'] ?? []);
        if (empty($items) && !empty($value)) {
            $items = [$value];
        }

        foreach ($items as $statusItem) {
            if (!is_array($statusItem)) {
                continue;
            }

            $type = strtolower((string) Arr::get($statusItem, 'type', $field));
            if ($type !== 'message_template_status_update' && $field !== 'message_template_status_update') {
                continue;
            }

            $rawStatus = strtoupper((string) (
                Arr::get($statusItem, 'status', '')
                ?: Arr::get($statusItem, 'event', '')
            ));
            if ($rawStatus === '') {
                continue;
            }

            $template = $this->findTemplateForStatusUpdate($instance, $statusItem);
            if (!$template) {
                Log::info('WhatsApp template status webhook unmatched', [
                    'instance_id' => $instance->id,
                    'payload' => $this->sanitizeWebhookPayload($statusItem),
                ]);
                continue;
            }

            $normalizedStatus = $this->normalizeTemplateStatus($rawStatus);
            $errorDetail = $this->templateStatusErrorDetail($statusItem, $normalizedStatus);
            $metaTemplateId = trim((string) (
                Arr::get($statusItem, 'message_template_id', '')
                ?: Arr::get($statusItem, 'template_id', '')
                ?: Arr::get($statusItem, 'id', '')
            ));
            $templateName = trim((string) (
                Arr::get($statusItem, 'message_template_name', '')
                ?: Arr::get($statusItem, 'template_name', '')
                ?: Arr::get($statusItem, 'name', '')
            ));

            $template->fill([
                'status' => $normalizedStatus,
                'namespace' => $instance->cloud_business_account_id ?: $template->namespace,
                'meta_template_id' => $metaTemplateId !== '' ? $metaTemplateId : $template->meta_template_id,
                'meta_name' => $templateName !== '' ? $templateName : $template->meta_name,
                'last_submit_error' => $errorDetail,
            ]);
            $template->save();
        }
    }

    private function findTemplateForStatusUpdate(WhatsAppInstance $instance, array $statusItem): ?WATemplate
    {
        $metaTemplateId = trim((string) (
            Arr::get($statusItem, 'message_template_id', '')
            ?: Arr::get($statusItem, 'template_id', '')
            ?: Arr::get($statusItem, 'id', '')
        ));
        if ($metaTemplateId !== '') {
            return WATemplate::query()
                ->where('tenant_id', $this->tenantId())
                ->where('meta_template_id', $metaTemplateId)
                ->first();
        }

        $templateName = trim((string) (
            Arr::get($statusItem, 'message_template_name', '')
            ?: Arr::get($statusItem, 'template_name', '')
            ?: Arr::get($statusItem, 'name', '')
        ));
        $language = trim((string) (
            Arr::get($statusItem, 'message_template_language', '')
            ?: Arr::get($statusItem, 'template_language', '')
            ?: Arr::get($statusItem, 'language', '')
        ));

        if ($templateName === '') {
            return null;
        }

        return WATemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->where('namespace', $instance->cloud_business_account_id)
            ->when($language !== '', fn ($query) => $query->where('language', $language))
            ->where(function ($query) use ($templateName) {
                $query->where('meta_name', $templateName)
                    ->orWhere(function ($fallbackQuery) use ($templateName) {
                        $fallbackQuery->whereNull('meta_name')
                            ->where('name', $templateName);
                    });
            })
            ->first();
    }

    private function normalizeTemplateStatus(string $rawStatus): string
    {
        return match (strtoupper($rawStatus)) {
            'APPROVED', 'ACTIVE' => 'approved',
            'PENDING', 'IN_APPEAL', 'PAUSED' => 'pending',
            'REJECTED', 'DISABLED', 'DELETED' => 'rejected',
            default => 'rejected',
        };
    }

    private function templateStatusErrorDetail(array $statusItem, string $normalizedStatus): ?string
    {
        if ($normalizedStatus === 'approved') {
            return null;
        }

        $parts = array_values(array_filter([
            trim((string) Arr::get($statusItem, 'reason', '')),
            trim((string) Arr::get($statusItem, 'information', '')),
            trim((string) Arr::get($statusItem, 'rejection_reason', '')),
        ], fn ($value) => $value !== ''));

        if (empty($parts)) {
            return null;
        }

        return mb_substr(implode(' | ', array_unique($parts)), 0, 65535);
    }

    private function extractIncomingMessageData(array $item): array
    {
        $type = strtolower((string) ($item['type'] ?? 'text'));
        $mediaUrl = null;
        $mediaMime = null;
        $body = null;

        if ($type === 'text') {
            $body = Arr::get($item, 'text.body');
        } elseif (in_array($type, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            $section = (array) ($item[$type] ?? []);
            $mediaId = (string) Arr::get($section, 'id', '');
            $mediaMime = Arr::get($section, 'mime_type');
            $mediaUrl = $mediaId !== '' ? "wa://media/{$mediaId}" : null;
            $body = Arr::get($section, 'caption')
                ?? Arr::get($section, 'filename')
                ?? '[' . $type . ']';
        } elseif ($type === 'location') {
            $lat = Arr::get($item, 'location.latitude');
            $lng = Arr::get($item, 'location.longitude');
            $name = Arr::get($item, 'location.name');
            $body = trim("Location {$name} ({$lat}, {$lng})");
        } elseif ($type === 'button') {
            $body = Arr::get($item, 'button.text') ?? Arr::get($item, 'button.payload');
        } elseif ($type === 'interactive') {
            $interactiveType = Arr::get($item, 'interactive.type');
            if ($interactiveType === 'button_reply') {
                $body = Arr::get($item, 'interactive.button_reply.title');
            } elseif ($interactiveType === 'list_reply') {
                $body = Arr::get($item, 'interactive.list_reply.title');
            } else {
                $body = '[interactive]';
            }
        } elseif ($type === 'contacts') {
            $name = Arr::get($item, 'contacts.0.name.formatted_name');
            $body = $name ? "Contact: {$name}" : '[contacts]';
        } else {
            $body = '[' . $type . ']';
        }

        return [$type, $body, $mediaUrl, $mediaMime];
    }

    private function parseTimestamp(mixed $timestamp): ?Carbon
    {
        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp((int) $timestamp);
        }

        if (is_string($timestamp) && trim($timestamp) !== '') {
            try {
                return Carbon::parse($timestamp);
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function instanceSettingValue(WhatsAppInstance $instance, array $keys): ?string
    {
        $settings = is_array($instance->settings) ? $instance->settings : [];
        foreach ($keys as $key) {
            $value = Arr::get($settings, $key);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function classifyCloudFailureStatus(array $statusItem): string
    {
        $code = (string) (Arr::get($statusItem, 'errors.0.code', '') ?: Arr::get($statusItem, 'error.code', ''));
        $message = mb_strtolower($this->cloudFailureErrorMessage($statusItem));

        $retryableCodes = ['2', '4', '80007', '130429', '131016'];
        if (in_array($code, $retryableCodes, true)) {
            return 'error_retryable';
        }

        $retryableMarkers = ['rate limit', 'temporar', 'try again', 'timeout', 'unavailable', 'internal error'];
        foreach ($retryableMarkers as $marker) {
            if (str_contains($message, $marker)) {
                return 'error_retryable';
            }
        }

        return 'error_permanent';
    }

    private function cloudFailureErrorMessage(array $statusItem): string
    {
        return (string) (
            Arr::get($statusItem, 'errors.0.title')
            ?? Arr::get($statusItem, 'errors.0.message')
            ?? json_encode($statusItem)
        );
    }

    private function sanitizeWebhookHeaders(array $headers): array
    {
        return $this->maskSensitiveValues($headers, [
            'authorization',
            'cookie',
            'x-bridge-token',
            'x-hub-signature-256',
        ]);
    }

    private function sanitizeWebhookPayload(array $payload): array
    {
        return $this->maskSensitiveValues($payload, [
            'token',
            'api_token',
            'access_token',
            'cloud_token',
            'verify_token',
            'wa_cloud_verify_token',
            'wa_cloud_app_secret',
            'app_secret',
            'signature',
            'signature_key',
            'authorization',
        ]);
    }

    private function maskSensitiveValues(array $payload, array $sensitiveKeys): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveValues($value, $sensitiveKeys);
                continue;
            }

            if (in_array(mb_strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}



