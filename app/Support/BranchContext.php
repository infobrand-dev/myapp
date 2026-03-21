<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BranchContext
{
    private static ?int $currentBranchId = null;

    public static function currentId(): ?int
    {
        return self::$currentBranchId;
    }

    public static function currentBranch(): ?Branch
    {
        if (!Schema::hasTable('branches') || self::$currentBranchId === null) {
            return null;
        }

        return Branch::query()
            ->whereKey(self::$currentBranchId)
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->first();
    }

    public static function setCurrentId(?int $branchId): void
    {
        self::$currentBranchId = $branchId ?: null;
    }

    public static function forget(): void
    {
        self::$currentBranchId = null;
    }

    public static function applyScope($query, string $column = 'branch_id')
    {
        if (self::$currentBranchId === null) {
            return $query->whereNull($column);
        }

        return $query->where($column, self::$currentBranchId);
    }

    public static function resolveIdFromRequest(Request $request): ?int
    {
        if (!Schema::hasTable('branches') || CompanyContext::currentId() === null) {
            return null;
        }

        $tenantId = TenantContext::currentId();
        $companyId = CompanyContext::currentId();
        $allowedBranchIds = self::allowedBranchIds($companyId);
        $session = $request->hasSession() ? $request->session() : null;
        $candidates = array_filter([
            self::normalizeInteger($request->attributes->get('branch_id')),
            self::normalizeInteger($request->header('X-Branch-Id')),
            self::normalizeInteger($request->query('branch_id')),
            self::normalizeInteger($session ? $session->get('branch_id') : null),
        ]);

        foreach ($candidates as $candidate) {
            if (self::branchExists($candidate, $tenantId, $companyId, $allowedBranchIds)) {
                return $candidate;
            }
        }

        $slugCandidates = array_filter([
            self::normalizeSlug($request->attributes->get('branch')),
            self::normalizeSlug($request->header('X-Branch-Slug')),
            self::normalizeSlug($request->query('branch')),
            self::normalizeSlug($session ? $session->get('branch_slug') : null),
        ]);

        foreach ($slugCandidates as $slug) {
            $branchId = Branch::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->when($allowedBranchIds, fn ($query) => $query->whereIn('id', $allowedBranchIds->all()))
                ->where('slug', $slug)
                ->where('is_active', true)
                ->value('id');

            if ($branchId) {
                return (int) $branchId;
            }
        }

        $defaultBranchId = app(UserAccessManager::class)->defaultBranchIdFor(auth()->user(), $companyId);

        if ($defaultBranchId && self::branchExists($defaultBranchId, $tenantId, $companyId, $allowedBranchIds)) {
            return $defaultBranchId;
        }

        return null;
    }

    private static function branchExists(int $branchId, int $tenantId, int $companyId, ?Collection $allowedBranchIds = null): bool
    {
        return Branch::query()
            ->whereKey($branchId)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($allowedBranchIds, fn ($query) => $query->whereIn('id', $allowedBranchIds->all()))
            ->where('is_active', true)
            ->exists();
    }

    private static function allowedBranchIds(?int $companyId): ?Collection
    {
        return app(UserAccessManager::class)->branchIdsFor(auth()->user(), $companyId);
    }

    private static function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $branchId = (int) $value;

        return $branchId > 0 ? $branchId : null;
    }

    private static function normalizeSlug(mixed $value): ?string
    {
        $slug = trim((string) $value);

        return $slug !== '' ? $slug : null;
    }
}
