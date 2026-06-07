<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class PlatformAuditLogger
{
    /**
     * @param  array<int, string>  $changedFields
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        array $changedFields = [],
        ?array $before = null,
        ?array $after = null,
        array $context = []
    ): PlatformAuditLog {
        $user = auth()->user();

        return PlatformAuditLog::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'actor_type' => $user ? get_class($user) : null,
            'actor_id' => $user?->getAuthIdentifier(),
            'impersonator_type' => data_get($context, 'impersonator_type'),
            'impersonator_id' => data_get($context, 'impersonator_id'),
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'action' => $action,
            'changed_fields' => array_values($changedFields),
            'before' => $before,
            'after' => $after,
            'context' => array_merge([
                'request_id' => request()?->headers->get('X-Request-Id'),
                'path' => request()?->path(),
                'method' => request()?->method(),
            ], $context),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<int, string>  $changedFields
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $context
     */
    public function logModel(
        string $action,
        Model $model,
        array $changedFields = [],
        ?array $before = null,
        ?array $after = null,
        array $context = []
    ): PlatformAuditLog {
        return $this->log(
            $action,
            get_class($model),
            $model->getKey(),
            $changedFields,
            $before,
            $after,
            $context
        );
    }
}
