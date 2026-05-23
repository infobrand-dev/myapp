<?php

namespace App\Support\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class NotificationMessage
{
    public function __construct(
        public readonly string $module,
        public readonly string $type,
        public readonly ?string $severity = null,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?int $tenantId = null,
        public readonly ?int $companyId = null,
        public readonly ?int $branchId = null,
        public readonly ?string $resourceType = null,
        public readonly ?int $resourceId = null,
        public readonly ?string $dedupeKey = null,
        public readonly array $actions = [],
        public readonly array $meta = [],
        public readonly ?CarbonInterface $occurredAt = null,
        public readonly array $recipientUserIds = [],
        public readonly array $recipientRoles = [],
    ) {
        if ($this->module === '' || $this->type === '') {
            throw new InvalidArgumentException('Notification module dan type wajib diisi.');
        }
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            module: (string) ($payload['module'] ?? ''),
            type: (string) ($payload['type'] ?? ''),
            severity: isset($payload['severity']) ? (string) $payload['severity'] : null,
            title: isset($payload['title']) ? (string) $payload['title'] : null,
            body: isset($payload['body']) ? (string) $payload['body'] : null,
            tenantId: isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            companyId: isset($payload['company_id']) ? (int) $payload['company_id'] : null,
            branchId: isset($payload['branch_id']) ? (int) $payload['branch_id'] : null,
            resourceType: isset($payload['resource_type']) ? (string) $payload['resource_type'] : null,
            resourceId: isset($payload['resource_id']) ? (int) $payload['resource_id'] : null,
            dedupeKey: isset($payload['dedupe_key']) ? (string) $payload['dedupe_key'] : null,
            actions: is_array($payload['actions'] ?? null) ? $payload['actions'] : [],
            meta: is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            occurredAt: !empty($payload['occurred_at']) ? Carbon::parse($payload['occurred_at']) : null,
            recipientUserIds: collect($payload['recipient_user_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all(),
            recipientRoles: collect($payload['recipient_roles'] ?? [])->map(fn ($role) => trim((string) $role))->filter()->unique()->values()->all(),
        );
    }
}
