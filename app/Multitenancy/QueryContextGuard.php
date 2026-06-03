<?php

namespace App\Multitenancy;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use RuntimeException;

class QueryContextGuard
{
    public function requireTenant(string $reason = 'tenant-scoped query'): int
    {
        $tenantId = TenantContext::currentId();

        if ($tenantId <= 0) {
            throw new RuntimeException('Tenant context is required for ' . $reason . '.');
        }

        return $tenantId;
    }

    public function requireCompany(string $reason = 'company-scoped query'): int
    {
        $companyId = CompanyContext::currentId();

        if ($companyId === null || $companyId <= 0) {
            throw new RuntimeException('Company context is required for ' . $reason . '.');
        }

        return $companyId;
    }

    public function allowsCentralBypass(string $table): bool
    {
        return app(TenantOwnershipManifest::class)->isCentralTable($table);
    }

    public function requireCentralTable(string $table, string $reason = 'central orchestration query'): void
    {
        if (!$this->allowsCentralBypass($table)) {
            throw new RuntimeException('Central table classification is required for ' . $reason . '.');
        }
    }
}
