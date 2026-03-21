<?php

namespace App\Modules\Reports\Services;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Query\Builder;

abstract class BaseReportService
{
    protected function baseFilters(array $filters): array
    {
        return [
            'date_from' => $this->normalizeDate($filters['date_from'] ?? null) ?? now()->startOfMonth()->toDateString(),
            'date_to' => $this->normalizeDate($filters['date_to'] ?? null) ?? now()->toDateString(),
        ];
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    protected function normalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function applyDateRange(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['date_from']), function (Builder $query) use ($column, $filters) {
                $query->whereDate($column, '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $query) use ($column, $filters) {
                $query->whereDate($column, '<=', $filters['date_to']);
            });
    }

    protected function applyTenantCompanyBranchScope(Builder $query, string $table, string $branchColumn = 'branch_id'): Builder
    {
        $query
            ->where($table . '.tenant_id', TenantContext::currentId())
            ->where($table . '.company_id', CompanyContext::currentId());

        if (BranchContext::currentId() === null) {
            return $query->whereNull($table . '.' . $branchColumn);
        }

        return $query->where($table . '.' . $branchColumn, BranchContext::currentId());
    }
}
