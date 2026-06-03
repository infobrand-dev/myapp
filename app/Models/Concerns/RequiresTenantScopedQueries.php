<?php

namespace App\Models\Concerns;

use App\Multitenancy\QueryContextGuard;
use Illuminate\Database\Eloquent\Builder;

trait RequiresTenantScopedQueries
{
    public function scopeForCurrentTenant(Builder $query, ?string $column = null): Builder
    {
        $tenantId = app(QueryContextGuard::class)->requireTenant('tenant-scoped model query');

        return $query->where($column ?: $this->qualifyColumn('tenant_id'), $tenantId);
    }
}
