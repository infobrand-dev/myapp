<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\ConversationIngestionResult;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationActivityLog;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConversationInboxIngester implements InboxMessageIngester
{
    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    public function ingest(InboxMessageEnvelope $envelope): ConversationIngestionResult
    {
        $occurredAt = $envelope->occurredAtOrNow();
        $direction = $this->normalizeDirection($envelope->direction);
        $type = $this->normalizeType($envelope->type);

        $conversation = Conversation::query()
            ->where('tenant_id', $this->tenantId())
            ->where('channel', $envelope->channel)
            ->where('contact_external_id', $envelope->contactExternalId)
            ->where(function ($query) use ($envelope): void {
                if ($envelope->instanceId === null) {
                    $query->whereNull('instance_id');
                    return;
                }

                $query->where('instance_id', $envelope->instanceId);
            })
            ->first();

        if ($conversation && $envelope->externalMessageId) {
            $existingMessage = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $envelope->externalMessageId)
                ->first();

            if ($existingMessage) {
                return new ConversationIngestionResult(
                    conversation: $conversation,
                    message: $existingMessage,
                    conversationWasCreated: false,
                    messageWasCreated: false,
                    deduplicated: true,
                );
            }
        }

        $conversationWasCreated = false;
        $metadata = $this->mergeMetadata($conversation?->metadata, $envelope->conversationMetadata);

        if (!$conversation) {
            $conversationWasCreated = true;
            $conversation = Conversation::query()->create([
                'tenant_id' => $this->tenantId(),
                'channel' => $envelope->channel,
                'instance_id' => $envelope->instanceId,
                'external_id' => $envelope->conversationExternalId,
                'contact_external_id' => $envelope->contactExternalId,
                'contact_name' => $envelope->contactName,
                'status' => 'open',
                'owner_id' => $envelope->ownerUserId,
                'claimed_at' => $envelope->ownerUserId ? $occurredAt : null,
                'locked_until' => $envelope->ownerUserId
                    ? $occurredAt->addMinutes((int) config('conversations.lock_minutes', 30))
                    : null,
                'last_message_at' => $occurredAt,
                'last_incoming_at' => $direction === 'in' ? $occurredAt : null,
                'last_outgoing_at' => $direction === 'out' ? $occurredAt : null,
                'unread_count' => $direction === 'in' && $envelope->incrementUnread ? 1 : 0,
                'metadata' => $metadata ?: null,
            ]);

            if ($envelope->ownerUserId) {
                ConversationParticipant::query()->updateOrCreate(
                    ['tenant_id' => $this->tenantId(), 'conversation_id' => $conversation->id, 'user_id' => $envelope->ownerUserId],
                    [
                        'role' => 'owner',
                        'unread_count' => 0,
                        'invited_at' => $occurredAt,
                        'invited_by' => $envelope->actorUserId ?: $envelope->ownerUserId,
                    ]
                );
            }
        } else {
            $updates = [
                'external_id' => $envelope->conversationExternalId ?: $conversation->external_id,
                'contact_name' => $envelope->contactName ?: $conversation->contact_name,
                'metadata' => $metadata ?: null,
                'last_message_at' => $this->laterOf($conversation->last_message_at, $occurredAt),
            ];

            if ($direction === 'in') {
                $updates['last_incoming_at'] = $this->laterOf($conversation->last_incoming_at, $occurredAt);
                if ($envelope->incrementUnread) {
                    $updates['unread_count'] = (int) ($conversation->unread_count ?? 0) + 1;
                }
            } else {
                $updates['last_outgoing_at'] = $this->laterOf($conversation->last_outgoing_at, $occurredAt);
            }

            $conversation->update($updates);
        }

        if ($direction === 'in' && $envelope->incrementUnread) {
            $participantQuery = ConversationParticipant::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->whereNull('left_at');

            if ($envelope->actorUserId) {
                $participantQuery->where('user_id', '!=', $envelope->actorUserId);
            }

            $participantQuery->increment('unread_count');
        }

        $message = new ConversationMessage([
            'tenant_id' => $this->tenantId(),
            'conversation_id' => $conversation->id,
            'user_id' => $direction === 'out' ? $envelope->actorUserId : null,
            'direction' => $direction,
            'type' => $type,
            'body' => $envelope->body,
            'media_url' => $envelope->mediaUrl,
            'media_mime' => $envelope->mediaMime,
            'status' => $envelope->messageStatus ?? ($direction === 'out' ? 'sent' : 'delivered'),
            'external_message_id' => $envelope->externalMessageId,
            'payload' => ($payload = $this->sanitizePayload($envelope->payload)) ? $payload : null,
            'sent_at' => $envelope->sentAt ?? ($direction === 'out' ? $occurredAt : null),
            'delivered_at' => $envelope->deliveredAt ?? ($direction === 'in' ? $occurredAt : null),
            'read_at' => $envelope->readAt,
        ]);
        $message->created_at = $occurredAt;
        $message->updated_at = $occurredAt;
        $message->save();

        if ($envelope->writeActivityLog && $envelope->activityLogAction) {
            ConversationActivityLog::query()->create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $envelope->actorUserId,
                'action' => $envelope->activityLogAction,
                'detail' => $envelope->activityLogDetail,
            ]);
        }

        if ($envelope->broadcast) {
            try {
                broadcast(new ConversationMessageCreated($message))->toOthers();
            } catch (Throwable $e) {
                Log::warning('Conversation inbox broadcast skipped', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                    'channel' => $envelope->channel,
                    'ingestion_mode' => $envelope->ingestionMode,
                ]);
            }
        }

        return new ConversationIngestionResult(
            conversation: $conversation,
            message: $message,
            conversationWasCreated: $conversationWasCreated,
            messageWasCreated: true,
            deduplicated: false,
        );
    }

    /**
     * @param array<string, mixed>|null $current
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    private function mergeMetadata(?array $current, array $incoming): array
    {
        return array_merge(
            $this->sanitizePayload($current ?? []),
            $this->sanitizePayload($incoming),
        );
    }

    private function laterOf(mixed $current, CarbonImmutable $candidate): CarbonImmutable
    {
        if ($current instanceof CarbonImmutable) {
            return $current->greaterThan($candidate) ? $current : $candidate;
        }

        if ($current instanceof \Carbon\CarbonInterface) {
            $current = CarbonImmutable::instance($current);
            return $current->greaterThan($candidate) ? $current : $candidate;
        }

        return $candidate;
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower(trim($direction)) === 'out' ? 'out' : 'in';
    }

    private function normalizeType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return match ($normalized) {
            '', 'chat' => 'text',
            default => $normalized,
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'token',
            'api_token',
            'access_token',
            'cloud_token',
            'visitor_token',
            'session_token',
            'session_token_plain',
            'authorization',
            'cookie',
            'secret',
            'app_secret',
            'signature',
            'signature_key',
            'x-bridge-token',
            'x-webhook-secret',
        ];

        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (in_array($normalizedKey, ['ip', 'ip_address', 'client_ip'], true)) {
                $sanitized[$key] = $value ? hash('sha256', (string) $value) : null;
                continue;
            }

            if ($normalizedKey === 'origin' || $normalizedKey === 'referer') {
                $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                $sanitized[$key] = $host !== '' ? $host : $value;
                continue;
            }

            if ($normalizedKey === 'user_agent') {
                $sanitized[$key] = Str::limit((string) $value, 255, '');
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
