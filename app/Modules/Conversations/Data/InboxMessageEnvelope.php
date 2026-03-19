<?php

namespace App\Modules\Conversations\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class InboxMessageEnvelope
{
    public const MODE_REALTIME = 'realtime';
    public const MODE_HISTORY = 'history';
    public const MODE_BACKFILL = 'backfill';

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $conversationMetadata
     */
    public function __construct(
        public readonly string $channel,
        public readonly ?int $instanceId,
        public readonly ?string $conversationExternalId,
        public readonly string $contactExternalId,
        public readonly ?string $contactName,
        public readonly string $direction,
        public readonly string $type,
        public readonly ?string $body,
        public readonly ?string $externalMessageId,
        public readonly array $payload = [],
        public readonly array $conversationMetadata = [],
        public readonly ?int $actorUserId = null,
        public readonly ?int $ownerUserId = null,
        public readonly ?string $messageStatus = null,
        public readonly ?string $mediaUrl = null,
        public readonly ?string $mediaMime = null,
        public readonly ?CarbonInterface $occurredAt = null,
        public readonly ?CarbonInterface $sentAt = null,
        public readonly ?CarbonInterface $deliveredAt = null,
        public readonly ?CarbonInterface $readAt = null,
        public readonly string $ingestionMode = self::MODE_REALTIME,
        public readonly bool $incrementUnread = true,
        public readonly bool $writeActivityLog = true,
        public readonly bool $broadcast = true,
        public readonly ?string $activityLogAction = null,
        public readonly ?string $activityLogDetail = null,
    ) {
    }

    public function occurredAtOrNow(): CarbonImmutable
    {
        if ($this->occurredAt instanceof CarbonImmutable) {
            return $this->occurredAt;
        }

        if ($this->occurredAt instanceof CarbonInterface) {
            return CarbonImmutable::instance($this->occurredAt);
        }

        return CarbonImmutable::now();
    }
}
