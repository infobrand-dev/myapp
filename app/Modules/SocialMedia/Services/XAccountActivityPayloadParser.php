<?php

namespace App\Modules\SocialMedia\Services;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class XAccountActivityPayloadParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function parse(array $payload): array
    {
        $events = collect((array) data_get($payload, 'direct_message_events', []))
            ->filter(fn ($event) => is_array($event))
            ->map(fn (array $event) => $this->normalizeDirectMessageEvent($event, $payload))
            ->filter()
            ->values()
            ->all();

        if ($events === []) {
            throw ValidationException::withMessages([
                'payload' => 'X webhook payload does not contain supported DM events.',
            ]);
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function normalizeDirectMessageEvent(array $event, array $payload): ?array
    {
        $eventType = trim((string) ($event['event_type'] ?? ''));
        if ($eventType !== 'MessageCreate') {
            return null;
        }

        $senderId = trim((string) ($event['sender_id'] ?? ''));
        $text = trim((string) ($event['text'] ?? ''));
        $eventId = trim((string) ($event['id'] ?? ''));
        $conversationId = trim((string) ($event['dm_conversation_id'] ?? ''));
        $forUserId = trim((string) ($payload['for_user_id'] ?? ''));

        if ($senderId === '' || $eventId === '' || $conversationId === '') {
            return null;
        }

        $attachments = (array) data_get($event, 'attachments.media_keys', []);
        $direction = $forUserId !== '' && $senderId === $forUserId ? 'out' : 'in';
        $participantId = $this->resolveParticipantId($event, $forUserId, $senderId, $conversationId);

        return [
            'provider' => 'x',
            'event_type' => $eventType,
            'event_id' => $eventId,
            'conversation_id' => $conversationId,
            'for_user_id' => $forUserId !== '' ? $forUserId : null,
            'sender_id' => $senderId,
            'contact_id' => $participantId,
            'direction' => $direction,
            'text' => $text !== '' ? $text : null,
            'attachment_media_keys' => array_values(array_filter(array_map('strval', $attachments))),
            'raw_event' => $event,
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function resolveParticipantId(array $event, string $forUserId, string $senderId, string $conversationId): string
    {
        $participantIds = collect((array) ($event['participant_ids'] ?? []))
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values();

        if ($participantIds->isNotEmpty()) {
            $other = $participantIds->first(fn (string $id) => $id !== $forUserId);
            if (is_string($other) && $other !== '') {
                return $other;
            }
        }

        $parts = collect(explode('-', $conversationId))
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->values();

        $otherConversationParticipant = $parts->first(fn (string $id) => $id !== $forUserId);
        if (is_string($otherConversationParticipant) && $otherConversationParticipant !== '') {
            return $otherConversationParticipant;
        }

        return $forUserId !== '' && $senderId === $forUserId
            ? (string) ($participantIds->first() ?? $conversationId)
            : $senderId;
    }
}
