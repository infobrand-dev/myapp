<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use App\Models\TenantRuntimeTopology;
use App\Support\TenantContext;

class TenantRuntimeTopologyResolver
{
    public function __construct(
        private readonly TenantTopologyFingerprint $fingerprint
    ) {
    }

    public function resolveCurrent(): ?TenantRuntimeTopology
    {
        return $this->resolveForTenant(TenantContext::currentTenant());
    }

    public function resolveForTenant(Tenant|int|null $tenant): ?TenantRuntimeTopology
    {
        $tenantModel = $tenant instanceof Tenant
            ? $tenant
            : (is_int($tenant) && $tenant > 0 ? Tenant::query()->with('runtimeTopology.appServer')->find($tenant) : null);

        if (!$tenantModel) {
            return null;
        }

        $tenantModel->loadMissing('runtimeTopology.appServer');

        return $tenantModel->runtimeTopology;
    }

    public function fingerprintForTenant(Tenant|int|null $tenant): string
    {
        $tenantModel = $tenant instanceof Tenant
            ? $tenant
            : (is_int($tenant) && $tenant > 0 ? Tenant::query()->with('runtimeTopology.appServer')->find($tenant) : null);

        return $tenantModel ? $this->fingerprint->runtime($tenantModel) : 'missing-runtime-topology';
    }
}
