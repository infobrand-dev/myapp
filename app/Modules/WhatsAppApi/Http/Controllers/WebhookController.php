<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
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
                if (!$this->handleCloudPayload($payload, $request, $event)) {
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
        $shouldAutoReply = $chatbot['auto_reply'] && $chatbot['chatbot_account_id'] && $isIncoming;
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

    private function handleCloudPayload(array $payload, Request $request, WhatsAppWebhookEvent $event): bool
    {
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $instance = $this->resolveCloudInstance($value);
                if (!$instance) {
                    continue;
                }

                $event->update([
                    'instance_id' => $instance->id,
                    'provider' => 'cloud',
                ]);

                if (!$this->isValidCloudSignature($request, $instance)) {
                    $event->update(['signature_valid' => false]);
                    return false;
                }

                $event->update(['signature_valid' => true]);

                $this->handleCloudIncomingMessages($instance, $value);
                $this->handleCloudMessageStatuses($value);
            }
        }

        return true;
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
            if ($chatbot['auto_reply'] && $chatbot['chatbot_account_id']) {
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



