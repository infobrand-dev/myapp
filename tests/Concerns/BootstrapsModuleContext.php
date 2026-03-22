<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;

trait BootstrapsModuleContext
{
    protected function registerModuleProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }

    protected function migrateModulePaths(array $paths): void
    {
        foreach ($paths as $path) {
            $this->artisan('migrate', [
                '--path' => $path,
                '--realpath' => false,
            ])->run();
        }
    }

    protected function bootstrapDefaultOperationalContext(
        int $tenantId = 1,
        int $companyId = 1,
        ?int $branchId = null,
        array $companyAttributes = []
    ): Company {
        $company = Company::query()->firstOrCreate(
            ['id' => $companyId],
            array_merge([
                'tenant_id' => $tenantId,
                'name' => 'Default Company',
                'slug' => 'default-company',
                'code' => 'DEF',
                'is_active' => true,
            ], $companyAttributes)
        );

        TenantContext::setCurrentId($tenantId);
        CompanyContext::setCurrentId($company->id);
        BranchContext::setCurrentId($branchId);

        return $company;
    }
}
