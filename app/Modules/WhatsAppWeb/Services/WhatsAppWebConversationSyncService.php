<?php

namespace App\Modules\WhatsAppWeb\Services;

use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationActivityLog;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;

class WhatsAppWebConversationSyncService
{
    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    public function __construct(private readonly InboxMessageIngester $ingester)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function syncMessage(array $payload, ?int $actorUserId = null): ConversationMessage
    {
        $direction = strtolower((string) ($payload['direction'] ?? 'in')) === 'out' ? 'out' : 'in';
        $chatId = trim((string) ($payload['contact_id'] ?? ''));
        $clientId = $this->nullableString($payload['client_id'] ?? null) ?? 'default';
        $contactName = $this->nullableString($payload['contact_name'] ?? null);
        $occurredAt = $this->resolveOccurredAt($payload['occurred_at'] ?? null) ?? now();
        $suppressUnreadIncrement = (bool) ($payload['suppress_unread_increment'] ?? false);
        $suppressActivityLog = (bool) ($payload['suppress_activity_log'] ?? false);
        $suppressBroadcast = (bool) ($payload['suppress_broadcast'] ?? false);
        $isGroup = (bool) ($payload['is_group'] ?? false);
        $author = $this->nullableString($payload['author'] ?? null);

        $result = $this->ingester->ingest(new InboxMessageEnvelope(
            channel: 'wa_web',
            instanceId: 0,
            conversationExternalId: null,
            contactExternalId: $chatId,
            contactName: $contactName,
            direction: $direction,
            type: $this->nullableString($payload['type'] ?? null) ?? 'text',
            body: (string) ($payload['message'] ?? ''),
            externalMessageId: $this->nullableString($payload['external_message_id'] ?? null),
            payload: array_merge($payload, ['client_id' => $clientId, 'author' => $author]),
            conversationMetadata: [
                'client_id' => $clientId,
                'is_group' => $isGroup,
            ],
            actorUserId: $actorUserId,
            ownerUserId: $actorUserId ?: $this->defaultOwnerId(),
            messageStatus: $direction === 'out' ? 'sent' : 'delivered',
            occurredAt: $occurredAt,
            sentAt: $direction === 'out' ? $occurredAt : null,
            deliveredAt: $direction === 'in' ? $occurredAt : null,
            ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
            incrementUnread: !$suppressUnreadIncrement,
            writeActivityLog: !$suppressActivityLog,
            broadcast: !$suppressBroadcast,
            activityLogAction: $suppressActivityLog ? null : ($direction === 'out' ? 'wa_web_outbound' : 'wa_web_inbound'),
            activityLogDetail: $suppressActivityLog ? null : ('WhatsApp Web ' . $direction . ' sync'),
        ));

        return $result->message;
    }

    /**
     * @param array<string, mixed> $chat
     * @param array<int, array<string, mixed>> $messages
     * @return array<string, mixed>
     */
    public function syncHistory(array $chat, array $messages, ?int $actorUserId = null): array
    {
        $chatId = trim((string) ($chat['id'] ?? ''));
        $clientId = $this->nullableString($chat['client_id'] ?? null) ?? 'default';
        $contactName = $this->nullableString($chat['name'] ?? null) ?? $chatId;
        $unreadCount = max(0, (int) ($chat['unreadCount'] ?? 0));
        $isGroup = (bool) ($chat['isGroup'] ?? false);

        if ($chatId === '') {
            return [
                'chat_id' => null,
                'conversation_id' => null,
                'imported_count' => 0,
                'deduplicated_count' => 0,
            ];
        }

        $messages = collect($messages)
            ->filter(fn ($message) => is_array($message) && trim((string) ($message['id'] ?? '')) !== '')
            ->sortBy(function (array $message) {
                return [
                    (string) ($message['timestampIso'] ?? ''),
                    (string) ($message['id'] ?? ''),
                ];
            })
            ->values();

        $importedCount = 0;
        $deduplicatedCount = 0;

        foreach ($messages as $message) {
            $existing = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('external_message_id', (string) $message['id'])
                ->whereHas('conversation', function ($query) use ($chatId): void {
                    $query->where('tenant_id', $this->tenantId())
                        ->whereIn('channel', ['wa_web', 'wa_bro'])
                        ->where('contact_external_id', $chatId)
                        ->where('instance_id', 0);
                })
                ->exists();

            if ($existing) {
                $deduplicatedCount++;
                continue;
            }

            $this->syncMessage([
                'client_id' => $clientId,
                'contact_id' => $chatId,
                'contact_name' => $contactName,
                'message' => (string) ($message['body'] ?? ''),
                'external_message_id' => (string) $message['id'],
                'direction' => !empty($message['fromMe']) ? 'out' : 'in',
                'type' => $this->normalizeMessageType((string) ($message['type'] ?? 'text')),
                'occurred_at' => $message['timestampIso'] ?? null,
                'author' => $message['author'] ?? null,
                'is_group' => $isGroup,
                'suppress_unread_increment' => true,
                'suppress_activity_log' => true,
                'suppress_broadcast' => true,
            ], $actorUserId);

            $importedCount++;
        }

        $conversation = Conversation::query()
            ->where('tenant_id', $this->tenantId())
            ->where('channel', 'wa_web')
            ->where('contact_external_id', $chatId)
            ->where('instance_id', 0)
            ->first();

        if ($conversation) {
            $aggregate = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->selectRaw('
                    MAX(created_at) as last_message_at,
                    MAX(CASE WHEN direction = "in" THEN created_at END) as last_incoming_at,
                    MAX(CASE WHEN direction = "out" THEN created_at END) as last_outgoing_at
                ')
                ->first();

            $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
            $metadata['client_id'] = $clientId;
            $metadata['is_group'] = $isGroup;
            $metadata['last_history_sync_at'] = now()->toIso8601String();

            $conversation->update([
                'contact_name' => $contactName ?: $conversation->contact_name,
                'unread_count' => $unreadCount,
                'last_message_at' => $aggregate->last_message_at ?? $conversation->last_message_at,
                'last_incoming_at' => $aggregate->last_incoming_at ?? $conversation->last_incoming_at,
                'last_outgoing_at' => $aggregate->last_outgoing_at ?? $conversation->last_outgoing_at,
                'metadata' => $metadata,
            ]);

            ConversationActivityLog::query()->create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $actorUserId,
                'action' => 'wa_web_history_sync',
                'detail' => sprintf(
                    'WhatsApp Web history sync: %d imported, %d skipped',
                    $importedCount,
                    $deduplicatedCount
                ),
            ]);
        }

        return [
            'chat_id' => $chatId,
            'conversation_id' => $conversation ? $conversation->id : null,
            'imported_count' => $importedCount,
            'deduplicated_count' => $deduplicatedCount,
        ];
    }

    private function defaultOwnerId(): ?int
    {
        $owner = User::query()
            ->where('tenant_id', TenantContext::currentId())
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Super-admin', 'Admin', 'Customer Service', 'Sales']))
            ->orderBy('id')
            ->first();

        return $owner?->id;
    }

    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolveOccurredAt(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        $string = $this->nullableString($value);
        if (!$string) {
            return null;
        }

        try {
            return Carbon::parse($string);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeMessageType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            '', 'chat' => 'text',
            default => $normalized,
        };
    }
}
