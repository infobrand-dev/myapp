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
        $dateFrom = !empty($filters['date_from']) ? $filters['date_from'] . ' 00:00:00' : null;
        $dateTo = !empty($filters['date_to']) ? $filters['date_to'] . ' 23:59:59' : null;

        return $query
            ->when($dateFrom !== null, function (Builder $query) use ($column, $dateFrom) {
                $query->where($column, '>=', $dateFrom);
            })
            ->when($dateTo !== null, function (Builder $query) use ($column, $dateTo) {
                $query->where($column, '<=', $dateTo);
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
