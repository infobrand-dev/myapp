<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Models\WhatsAppInstanceChatbotIntegration;
use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode'));
        $token = (string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token'));
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge'));

        if ($mode !== 'subscribe') {
            return response('Invalid mode', Response::HTTP_FORBIDDEN);
        }

        $hasMatch = WhatsAppInstance::query()
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->get()
            ->contains(function (WhatsAppInstance $instance) use ($token): bool {
                $verifyToken = $this->instanceSettingValue($instance, ['wa_cloud_verify_token', 'verify_token']);
                return $verifyToken !== null && hash_equals($verifyToken, $token);
            });

        if (!$hasMatch) {
            return response('Invalid verify token', Response::HTTP_UNAUTHORIZED);
        }

        return response($challenge, Response::HTTP_OK)->header('Content-Type', 'text/plain');
    }

    public function inbound(Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();
        $payload = $request->all();
        $event = $this->createOrTouchWebhookEvent($request, $payload, $rawPayload);

        if ($event->process_status === 'processed') {
            return response()->json(['stored' => true, 'deduplicated' => true, 'mode' => 'event']);
        }

        if ($this->looksLikeCloudPayload($payload)) {
            try {
                $result = $this->handleCloudPayload($payload, $request, $event);
                if (!($result['ok'] ?? false)) {
                    $reason = (string) ($result['reason'] ?? 'Cloud payload ignored');
                    $status = (string) ($result['status'] ?? 'failed');

                    if ($status === 'unauthorized') {
                        $this->markWebhookEventFailed($event, $reason, false);
                        return response()->json(['message' => $reason], Response::HTTP_UNAUTHORIZED);
                    }

                    $this->markWebhookEventFailed($event, $reason);
                    // Return 200 to avoid noisy retries for unmapped/malformed events.
                    return response()->json(['stored' => false, 'ignored' => true, 'message' => $reason], Response::HTTP_OK);
                }

                if (($result['signature_valid'] ?? null) === false) {
                    $this->markWebhookEventFailed($event, 'Invalid signature', false);
                    return response()->json(['message' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
                }
            } catch (Throwable $e) {
                Log::error('WhatsApp cloud webhook process failed', ['error' => $e->getMessage()]);
                $this->markWebhookEventFailed($event, $e->getMessage());
                return response()->json(['message' => 'Webhook processing failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->markWebhookEventProcessed($event);
            return response()->json(['stored' => true, 'mode' => 'cloud']);
        }

        $data = $request->validate([
            'token' => ['required', 'string'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
            'instance_key' => ['nullable', 'string'],
        ]);

        $instance = WhatsAppInstance::where('api_token', $data['token'])
            ->when($data['instance_key'] ?? null, fn ($q) => $q->where('id', $data['instance_key']))
            ->first();

        if (!$instance) {
            $this->markWebhookEventFailed($event, 'Invalid token');
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $event->update([
            'instance_id' => $instance->id,
            'provider' => strtolower((string) ($instance->provider ?? 'gateway')),
        ]);

        $isIncoming = ($data['direction'] ?? 'in') === 'in';
        $conversation = Conversation::firstOrCreate(
            [
                'channel' => 'wa_api',
                'instance_id' => $instance->id,
                'contact_external_id' => $data['contact_id'],
            ],
            [
                'contact_name' => $data['contact_name'] ?? null,
                'status' => 'open',
                'last_message_at' => now(),
                'last_incoming_at' => $isIncoming ? now() : null,
                'last_outgoing_at' => $isIncoming ? null : now(),
                'unread_count' => 0,
            ]
        );

        if (!empty($data['external_message_id'])) {
            $alreadyStored = ConversationMessage::where('conversation_id', $conversation->id)
                ->where('external_message_id', $data['external_message_id'])
                ->exists();
            if ($alreadyStored) {
                $this->markWebhookEventProcessed($event);
                return response()->json(['stored' => true, 'deduplicated' => true]);
            }
        }

        $conversationUpdates = [
            'contact_name' => $data['contact_name'] ?? $conversation->contact_name,
            'last_message_at' => now(),
        ];
        if ($isIncoming) {
            $conversationUpdates['last_incoming_at'] = now();
            $conversationUpdates['unread_count'] = ($conversation->unread_count ?? 0) + 1;
        } else {
            $conversationUpdates['last_outgoing_at'] = now();
        }
        $conversation->update($conversationUpdates);

        $msg = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => $data['direction'] ?? 'in',
            'type' => 'text',
            'body' => $data['message'],
            'status' => $isIncoming ? 'delivered' : 'sent',
            'external_message_id' => $data['external_message_id'] ?? null,
            'payload' => $request->all(),
        ]);

        $chatbot = $this->chatbotIntegration($instance);
        $shouldAutoReply = $chatbot['auto_reply']
            && $isIncoming
            && $this->shouldAutoReply($conversation, $chatbot['chatbot_account_id'], (string) ($msg->body ?? ''));
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $msg->id, $chatbot['chatbot_account_id']);
        }

        $this->markWebhookEventProcessed($event);
        return response()->json(['stored' => true]);
    }

    private function createOrTouchWebhookEvent(Request $request, array $payload, string $rawPayload): WhatsAppWebhookEvent
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $eventKey = hash('sha256', $rawPayload . '|' . $signature);

        $event = WhatsAppWebhookEvent::query()->where('event_key', $eventKey)->first();
        if (!$event) {
            return WhatsAppWebhookEvent::create([
                'provider' => $this->looksLikeCloudPayload($payload) ? 'cloud' : 'gateway',
                'event_key' => $eventKey,
                'headers' => $request->headers->all(),
                'payload' => $payload,
                'process_status' => 'pending',
                'retry_count' => 0,
                'received_at' => now(),
            ]);
        }

        $event->update([
            'headers' => $request->headers->all(),
            'payload' => $payload,
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

    private function markWebhookEventFailed(WhatsAppWebhookEvent $event, string $error, ?bool $signatureValid = null): void
    {
        $updates = [
            'process_status' => 'failed',
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

    private function handleCloudPayload(array $payload, Request $request, WhatsAppWebhookEvent $event): array
    {
        $matchedInstance = false;
        $unmatchedPhoneIds = [];

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $instance = $this->resolveCloudInstance($value);
                if (!$instance) {
                    $candidatePhoneId = (string) Arr::get($value, 'metadata.phone_number_id', '');
                    if ($candidatePhoneId !== '') {
                        $unmatchedPhoneIds[] = $candidatePhoneId;
                    }
                    continue;
                }
                $matchedInstance = true;

                $event->update([
                    'instance_id' => $instance->id,
                    'provider' => 'cloud',
                ]);

                if (!$this->isValidCloudSignature($request, $instance)) {
                    $event->update(['signature_valid' => false]);
                    return [
                        'ok' => false,
                        'status' => 'unauthorized',
                        'reason' => 'Invalid signature',
                        'signature_valid' => false,
                    ];
                }

                $event->update(['signature_valid' => true]);

                $this->handleCloudIncomingMessages($instance, $value);
                $this->handleCloudMessageStatuses($value);
            }
        }

        if (!$matchedInstance) {
            $detail = !empty($unmatchedPhoneIds)
                ? 'phone_number_id: ' . implode(', ', array_unique($unmatchedPhoneIds))
                : 'payload tidak mengandung metadata.phone_number_id yang dikenali';

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

    private function resolveCloudInstance(array $value): ?WhatsAppInstance
    {
        $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id', '');
        if ($phoneNumberId === '') {
            return null;
        }

        return WhatsAppInstance::where('provider', 'cloud')
            ->where('phone_number_id', $phoneNumberId)
            ->first();
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

            $conversation = Conversation::firstOrCreate(
                [
                    'channel' => 'wa_api',
                    'instance_id' => $instance->id,
                    'contact_external_id' => $from,
                ],
                [
                    'contact_name' => Arr::get($contacts->get($from), 'profile.name'),
                    'status' => 'open',
                    'last_message_at' => now(),
                    'last_incoming_at' => now(),
                    'unread_count' => 0,
                ]
            );

            if ($waMessageId !== '') {
                $exists = ConversationMessage::where('conversation_id', $conversation->id)
                    ->where('external_message_id', $waMessageId)
                    ->exists();
                if ($exists) {
                    continue;
                }
            }

            [$type, $body, $mediaUrl, $mediaMime] = $this->extractIncomingMessageData((array) $item);

            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'in',
                'type' => $type,
                'body' => $body,
                'media_url' => $mediaUrl,
                'media_mime' => $mediaMime,
                'status' => 'delivered',
                'external_message_id' => $waMessageId !== '' ? $waMessageId : null,
                'payload' => $item,
            ]);

            $conversation->update([
                'contact_name' => Arr::get($contacts->get($from), 'profile.name', $conversation->contact_name),
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'unread_count' => ($conversation->unread_count ?? 0) + 1,
            ]);

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
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['needs_human'] = true;
        $metadata['auto_reply_paused'] = true;
        $metadata['handoff_reason'] = $reason;
        $metadata['handoff_at'] = now()->toDateTimeString();

        $conversation->update([
            'metadata' => $metadata,
        ]);
    }

    private function sendHumanHandoffAcknowledgement(Conversation $conversation): void
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if ((bool) ($metadata['auto_reply_paused'] ?? false)) {
            return;
        }

        $instance = $conversation->instance_id ? WhatsAppInstance::query()->find($conversation->instance_id) : null;
        $body = $this->humanHandoffAcknowledgementMessage($instance);

        $message = ConversationMessage::create([
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
        $configured = trim((string) Arr::get($instance?->settings ?? [], 'handoff_ack_message', ''));

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
                $updates['status'] = 'error';
                $updates['error_message'] = Arr::get($statusItem, 'errors.0.title')
                    ?? Arr::get($statusItem, 'errors.0.message')
                    ?? json_encode($statusItem);
            } else {
                continue;
            }

            $message->update($updates);

            if ($message->conversation_id) {
                Conversation::whereKey($message->conversation_id)->update([
                    'last_outgoing_at' => $timestamp ?? now(),
                ]);
            }
        }
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
}



