<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Tenant;

class CurrencySettingsResolver
{
    private const DEFAULT_CURRENCY = 'IDR';

    public function defaultCurrency(?int $tenantId = null, ?int $companyId = null): string
    {
        $tenantId ??= TenantContext::currentId();
        $companyId ??= CompanyContext::currentId();

        if ($companyId) {
            $companyCurrency = $this->companyCurrency($tenantId, $companyId);
            if ($companyCurrency !== null) {
                return $companyCurrency;
            }
        }

        return $this->tenantCurrency($tenantId) ?? self::DEFAULT_CURRENCY;
    }

    public function tenantCurrency(?int $tenantId = null): ?string
    {
        $tenantId ??= TenantContext::currentId();
        if (!$tenantId) {
            return null;
        }

        $tenant = Tenant::query()->find($tenantId);
        $currency = strtoupper((string) data_get($tenant?->meta, 'default_currency', ''));

        return $currency !== '' ? $currency : null;
    }

    public function companyCurrency(?int $tenantId = null, ?int $companyId = null): ?string
    {
        $tenantId ??= TenantContext::currentId();
        $companyId ??= CompanyContext::currentId();

        if (!$tenantId || !$companyId) {
            return null;
        }

        $company = Company::query()
            ->where('tenant_id', $tenantId)
            ->find($companyId);

        $currency = strtoupper((string) data_get($company?->meta, 'default_currency', ''));

        return $currency !== '' ? $currency : null;
    }

    public function options(): array
    {
        return [
            'IDR' => 'Indonesian Rupiah (IDR)',
            'USD' => 'US Dollar (USD)',
            'SGD' => 'Singapore Dollar (SGD)',
            'EUR' => 'Euro (EUR)',
        ];
    }
}
