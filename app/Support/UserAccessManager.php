<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\UserBranch;
use App\Models\UserCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserAccessManager
{
    public function sync(User $user, array $companyIds, array $branchIds, ?int $defaultCompanyId = null, ?int $defaultBranchId = null): void
    {
        $tenantId = (int) $user->tenant_id;
        $allowedCompanies = $this->allowedCompanyIds($tenantId, $companyIds);
        $allowedBranches = $this->allowedBranchIds($tenantId, $allowedCompanies, $branchIds);

        if ($allowedCompanies->isEmpty()) {
            $allowedCompanies = Company::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(1)
                ->pluck('id');
        }

        $defaultCompanyId = $allowedCompanies->contains($defaultCompanyId) ? $defaultCompanyId : $allowedCompanies->first();
        $defaultBranchId = $allowedBranches->contains($defaultBranchId) ? $defaultBranchId : null;

        DB::transaction(function () use ($user, $tenantId, $allowedCompanies, $allowedBranches, $defaultCompanyId, $defaultBranchId) {
            UserCompany::query()->where('tenant_id', $tenantId)->where('user_id', $user->id)->delete();
            UserBranch::query()->where('tenant_id', $tenantId)->where('user_id', $user->id)->delete();

            foreach ($allowedCompanies as $companyId) {
                UserCompany::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'is_default' => (int) $companyId === (int) $defaultCompanyId,
                ]);
            }

            foreach ($allowedBranches as $branchId) {
                $branchCompanyId = (int) Branch::query()->whereKey($branchId)->value('company_id');

                UserBranch::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'company_id' => $branchCompanyId,
                    'branch_id' => $branchId,
                    'is_default' => (int) $branchId === (int) $defaultBranchId,
                ]);
            }
        });
    }

    public function companyIdsFor(?User $user): ?Collection
    {
        if (!$user || !Schema::hasTable('user_companies')) {
            return null;
        }

        $ids = UserCompany::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->pluck('company_id');

        return $ids->isEmpty() ? null : $ids->map(fn ($id) => (int) $id)->values();
    }

    public function branchIdsFor(?User $user, ?int $companyId = null): ?Collection
    {
        if (!$user || !Schema::hasTable('user_branches')) {
            return null;
        }

        $query = UserBranch::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $ids = $query->pluck('branch_id');

        return $ids->isEmpty() ? null : $ids->map(fn ($id) => (int) $id)->values();
    }

    public function defaultCompanyIdFor(?User $user): ?int
    {
        if (!$user || !Schema::hasTable('user_companies')) {
            return null;
        }

        return $this->applyTrueConstraint(
            UserCompany::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
        , 'is_default')->value('company_id');
    }

    public function defaultBranchIdFor(?User $user, ?int $companyId = null): ?int
    {
        if (!$user || !Schema::hasTable('user_branches')) {
            return null;
        }

        return $this->applyTrueConstraint(
            UserBranch::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
        , 'is_default')->value('branch_id');
    }

    private function applyTrueConstraint($query, string $column)
    {
        $driver = $query->getModel()->getConnection()->getDriverName();
        $qualifiedColumn = $query->qualifyColumn($column);

        if ($driver === 'pgsql') {
            return $query->whereRaw($qualifiedColumn . ' is true');
        }

        return $query->where($column, true);
    }

    private function allowedCompanyIds(int $tenantId, array $companyIds): Collection
    {
        return Company::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $this->normalizeIds($companyIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function allowedBranchIds(int $tenantId, Collection $companyIds, array $branchIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return Branch::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('company_id', $companyIds->all())
            ->whereIn('id', $this->normalizeIds($branchIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
