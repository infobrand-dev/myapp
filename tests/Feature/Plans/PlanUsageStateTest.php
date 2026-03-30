<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Modules\Contacts\Models\Contact;
use App\Support\PlanLimit;
use App\Support\TenantPlanManager;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanUsageStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Contacts/Database/Migrations',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_zero_limit_without_usage_is_ok(): void
    {
        [, $tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::CONTACTS => 0,
        ]);

        $state = app(TenantPlanManager::class)->usageState(PlanLimit::CONTACTS, $tenant->id);

        $this->assertSame(0, $state['limit']);
        $this->assertSame(0, $state['usage']);
        $this->assertSame(0, $state['remaining']);
        $this->assertSame('ok', $state['status']);
    }

    public function test_zero_limit_with_existing_usage_is_over_limit(): void
    {
        [, $tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::CONTACTS => 0,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Over Limit Contact',
            'is_active' => true,
        ]);

        $state = app(TenantPlanManager::class)->usageState(PlanLimit::CONTACTS, $tenant->id);

        $this->assertSame(1, $state['usage']);
        $this->assertSame(0, $state['remaining']);
        $this->assertSame('over_limit', $state['status']);
    }

    public function test_usage_state_distinguishes_near_limit_and_at_limit(): void
    {
        [, $tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::CONTACTS => 5,
        ]);

        foreach (range(1, 4) as $i) {
            Contact::query()->create([
                'tenant_id' => $tenant->id,
                'type' => 'individual',
                'name' => 'Contact ' . $i,
                'is_active' => true,
            ]);
        }

        $nearLimit = app(TenantPlanManager::class)->usageState(PlanLimit::CONTACTS, $tenant->id);
        $this->assertSame('near_limit', $nearLimit['status']);
        $this->assertSame(1, $nearLimit['remaining']);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Contact 5',
            'is_active' => true,
        ]);

        $atLimit = app(TenantPlanManager::class)->usageState(PlanLimit::CONTACTS, $tenant->id);
        $this->assertSame('at_limit', $atLimit['status']);
        $this->assertSame(0, $atLimit['remaining']);
    }

    private function makeTenantWithPlanLimits(array $limits): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Usage State Workspace',
            'slug' => 'usage-state-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'usage-state-' . $tenant->id,
            'name' => 'Usage State Plan ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => [],
            'limits' => $limits,
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'usage-state-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$plan, $tenant];
    }
}
