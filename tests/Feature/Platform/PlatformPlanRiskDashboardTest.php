<?php

namespace Tests\Feature\Platform;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Support\PlanLimit;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformPlanRiskDashboardTest extends TestCase
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

    public function test_platform_tenant_list_can_filter_heavy_contact_usage(): void
    {
        [$platformOwner] = $this->makePlatformOwner();

        [$heavyTenant] = $this->makeTenantWithPlanLimits('Heavy Contacts', [
            PlanLimit::CONTACTS => 5,
        ]);

        foreach (range(1, 4) as $i) {
            Contact::query()->create([
                'tenant_id' => $heavyTenant->id,
                'type' => 'individual',
                'name' => 'Heavy Contact ' . $i,
                'is_active' => true,
            ]);
        }

        [$safeTenant] = $this->makeTenantWithPlanLimits('Safe Contacts', [
            PlanLimit::CONTACTS => 10,
        ]);

        Contact::query()->create([
            'tenant_id' => $safeTenant->id,
            'type' => 'individual',
            'name' => 'Safe Contact',
            'is_active' => true,
        ]);

        $this->actingAs($platformOwner)
            ->get(route('platform.tenants.index', ['risk' => 'heavy_contacts']))
            ->assertOk()
            ->assertSeeText('Heavy Contacts')
            ->assertDontSeeText('Safe Contacts')
            ->assertSeeText('Near limit');
    }

    private function makePlatformOwner(): array
    {
        $platformTenant = Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Platform',
            'slug' => 'platform',
            'is_active' => true,
        ]);

        $platformOwner = User::factory()->create([
            'tenant_id' => $platformTenant->id,
            'email_verified_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        $role = Role::query()->firstOrCreate([
            'name' => 'Super-admin',
            'guard_name' => 'web',
            'tenant_id' => 1,
        ]);

        $platformOwner->assignRole($role);

        return [$platformOwner, $platformTenant];
    }

    private function makeTenantWithPlanLimits(string $name, array $limits): array
    {
        $tenant = Tenant::query()->create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name . '-' . Tenant::query()->count()),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'platform-risk-' . $tenant->id,
            'name' => 'Platform Risk Plan ' . $tenant->id,
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
            'billing_reference' => 'platform-risk-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$tenant, $plan];
    }
}
