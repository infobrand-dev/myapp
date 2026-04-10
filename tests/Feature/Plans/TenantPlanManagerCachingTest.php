<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\PlanFeature;
use App\Support\TenantPlanManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantPlanManagerCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_has_feature_calls_reuse_cached_subscription_state_per_tenant(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Plan Cache Workspace',
            'slug' => 'plan-cache-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'plan-cache-' . $tenant->id,
            'name' => 'Plan Cache ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => [
                PlanFeature::ACCOUNTING => true,
            ],
            'limits' => [],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'plan-cache-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        $manager = app(TenantPlanManager::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue($manager->hasFeature(PlanFeature::ACCOUNTING, $tenant->id));
        $firstCount = $this->tenantSubscriptionQueryCount();

        $this->assertTrue($manager->hasFeature(PlanFeature::ACCOUNTING, $tenant->id));
        $secondCount = $this->tenantSubscriptionQueryCount();

        $this->assertGreaterThan(0, $firstCount);
        $this->assertSame($firstCount, $secondCount);
    }

    private function tenantSubscriptionQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(function (array $entry): bool {
                $query = strtolower((string) ($entry['query'] ?? ''));

                return str_contains($query, 'tenant_subscriptions');
            })
            ->count();
    }
}
