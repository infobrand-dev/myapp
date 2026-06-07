<?php

namespace App\Support\Entitlements;

use App\Models\Module;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Support\Facades\Schema;

class TenantEntitlementService
{
    public function __construct(
        private readonly TenantPlanManager $plans
    ) {
    }

    public function snapshot(?int $tenantId = null): TenantEntitlementSnapshot
    {
        $tenantId ??= TenantContext::currentId();

        return new TenantEntitlementSnapshot(
            $tenantId,
            $this->installedModules(),
            $this->activeModules(),
            $this->featureMap($tenantId),
            $this->limitMap($tenantId),
            $this->plans->currentSubscription($tenantId)?->status
        );
    }

    public function hasFeature(string $feature, ?int $tenantId = null): bool
    {
        return $this->snapshot($tenantId)->hasFeature($feature);
    }

    /**
     * @return array<int, string>
     */
    private function installedModules(): array
    {
        if (!Schema::hasTable('modules')) {
            return [];
        }

        return Module::query()
            ->where('installed', true)
            ->orderBy('slug')
            ->pluck('slug')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function activeModules(): array
    {
        if (!Schema::hasTable('modules')) {
            return [];
        }

        return Module::query()
            ->where('installed', true)
            ->where('active', true)
            ->orderBy('slug')
            ->pluck('slug')
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    private function featureMap(?int $tenantId): array
    {
        $features = [];

        foreach ((new \ReflectionClass(PlanFeature::class))->getConstants() as $value) {
            if (is_string($value)) {
                $features[$value] = $this->plans->hasFeature($value, $tenantId);
            }
        }

        return $features;
    }

    /**
     * @return array<string, int|null>
     */
    private function limitMap(?int $tenantId): array
    {
        $limits = [];

        foreach ((new \ReflectionClass(PlanLimit::class))->getConstants() as $value) {
            if (is_string($value)) {
                $limits[$value] = $this->plans->limit($value, $tenantId);
            }
        }

        return $limits;
    }
}
