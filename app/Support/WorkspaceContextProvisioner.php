<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class WorkspaceContextProvisioner
{
    public function ensureForTenant(int $tenantId, ?User $user = null): array
    {
        $tenant = Tenant::query()->find($tenantId);
        $company = $this->ensureCompany($tenantId, $tenant?->name);
        $branch = $this->ensureBranch($tenantId, $company);

        if ($user && app(UserAccessManager::class)->companyIdsFor($user) === null) {
            app(UserAccessManager::class)->sync($user, [$company->id], [$branch->id], $company->id, $branch->id);
        }

        return [$company, $branch];
    }

    private function ensureCompany(int $tenantId, ?string $tenantName = null): Company
    {
        $activeCompany = Company::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('id')
            ->first();

        if ($activeCompany) {
            return $activeCompany;
        }

        $existingCompany = Company::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->first();

        if ($existingCompany) {
            $existingCompany->update(['is_active' => true]);

            return $existingCompany->fresh();
        }

        $baseName = trim((string) ($tenantName ?: 'Default Company'));
        $name = $baseName !== '' ? $baseName : 'Default Company';

        return Company::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $this->uniqueCompanySlug($tenantId, Str::slug($name) ?: 'default-company'),
            'code' => $this->uniqueCompanyCode($tenantId, 'DEFAULT'),
            'is_active' => true,
            'meta' => ['bootstrap' => true, 'source' => 'workspace_context_provisioner'],
        ]);
    }

    private function ensureBranch(int $tenantId, Company $company): Branch
    {
        $activeBranch = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $company->id)
            ->active()
            ->orderBy('id')
            ->first();

        if ($activeBranch) {
            return $activeBranch;
        }

        $existingBranch = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->first();

        if ($existingBranch) {
            $existingBranch->update(['is_active' => true]);

            return $existingBranch->fresh();
        }

        return Branch::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'slug' => $this->uniqueBranchSlug($tenantId, $company->id, 'main-branch'),
            'code' => $this->uniqueBranchCode($tenantId, $company->id, 'MAIN'),
            'is_active' => true,
            'meta' => ['bootstrap' => true, 'source' => 'workspace_context_provisioner'],
        ]);
    }

    private function uniqueCompanySlug(int $tenantId, string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (Company::query()->where('tenant_id', $tenantId)->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueCompanyCode(int $tenantId, string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (Company::query()->where('tenant_id', $tenantId)->where('code', $candidate)->exists()) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueBranchSlug(int $tenantId, int $companyId, string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (Branch::query()->where('tenant_id', $tenantId)->where('company_id', $companyId)->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueBranchCode(int $tenantId, int $companyId, string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (Branch::query()->where('tenant_id', $tenantId)->where('company_id', $companyId)->where('code', $candidate)->exists()) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
