<?php

namespace App\Services;

use App\Models\PlatformActivityEvent;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

class PlatformActivityRecorder
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<int, array<string, mixed>>|null  $actions
     */
    public function record(
        string $sourceModule,
        string $eventType,
        string $subjectType,
        string|int|null $subjectId,
        string $summary,
        ?array $payload = null,
        ?array $actions = null
    ): PlatformActivityEvent {
        $user = auth()->user();

        return PlatformActivityEvent::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'source_module' => $sourceModule,
            'event_type' => $eventType,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'actor_type' => $user ? get_class($user) : null,
            'actor_id' => $user?->getAuthIdentifier(),
            'summary' => $summary,
            'payload' => $payload,
            'actions' => $actions,
            'occurred_at' => now(),
        ]);
    }
}
