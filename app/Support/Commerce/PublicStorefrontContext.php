<?php

namespace App\Support\Commerce;

use App\Models\Company;
use App\Models\Tenant;
use App\Support\CompanyContext;
use App\Support\TenantContext;

class PublicStorefrontContext
{
    public function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= TenantContext::currentTenant();
        $meta = is_array($tenant?->meta) ? $tenant->meta : [];

        return (bool) ($meta['public_storefront_enabled'] ?? true);
    }

    public function resolveCompany(?Tenant $tenant = null): ?Company
    {
        $tenant ??= TenantContext::currentTenant();

        if (!$tenant) {
            return null;
        }

        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        $companyId = (int) ($meta['default_public_company_id'] ?? 0);

        $company = null;

        if ($companyId > 0) {
            $company = Company::query()
                ->where('tenant_id', (int) $tenant->id)
                ->active()
                ->find($companyId);
        }

        if (!$company) {
            $currentCompany = CompanyContext::currentCompany();

            if ($currentCompany && (int) $currentCompany->tenant_id === (int) $tenant->id && (bool) $currentCompany->is_active) {
                $company = $currentCompany;
            }
        }

        if (!$company) {
            $company = Company::query()
                ->where('tenant_id', (int) $tenant->id)
                ->active()
                ->orderBy('id')
                ->first();
        }

        return $company;
    }

    public function apply(?Tenant $tenant = null): ?Company
    {
        $company = $this->resolveCompany($tenant);
        CompanyContext::setCurrentId($company?->id);

        return $company;
    }
}
