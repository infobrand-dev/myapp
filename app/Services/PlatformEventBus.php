<?php

namespace App\Services;

use App\Models\PlatformEventOutbox;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

class PlatformEventBus
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(
        string $eventName,
        string $subjectType,
        string|int|null $subjectId,
        array $payload = [],
        ?string $idempotencyKey = null,
        int $version = 1
    ): PlatformEventOutbox {
        return PlatformEventOutbox::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'event_name' => $eventName,
            'event_version' => $version,
            'idempotency_key' => $idempotencyKey ?: sha1($eventName . '|' . $subjectType . '|' . (string) $subjectId . '|' . json_encode($payload)),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'payload' => [
                'tenant_scope' => [
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => BranchContext::currentId(),
                ],
                'occurred_at' => now()->toIso8601String(),
                'payload' => $payload,
            ],
            'occurred_at' => now(),
            'status' => 'pending',
        ]);
    }

    public function markDispatched(PlatformEventOutbox $event): void
    {
        $event->forceFill([
            'status' => 'dispatched',
            'dispatched_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();
    }

    public function markFailed(PlatformEventOutbox $event, string $message): void
    {
        $event->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
        ])->save();
    }
}
