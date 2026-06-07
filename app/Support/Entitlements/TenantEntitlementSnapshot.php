<?php

namespace App\Support\Entitlements;

class TenantEntitlementSnapshot
{
    /**
     * @param  array<int, string>  $installedModules
     * @param  array<int, string>  $activeModules
     * @param  array<string, bool>  $features
     * @param  array<string, int|null>  $limits
     */
    public function __construct(
        public readonly ?int $tenantId,
        public readonly array $installedModules,
        public readonly array $activeModules,
        public readonly array $features,
        public readonly array $limits,
        public readonly ?string $billingState
    ) {
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }
}
