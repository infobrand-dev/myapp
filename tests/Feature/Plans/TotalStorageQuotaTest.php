<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class TotalStorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_storage_usage_state_counts_tenant_owned_files_in_bytes(): void
    {
        Storage::fake('public');

        [$tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::TOTAL_STORAGE_BYTES => 1024,
        ]);

        TenantContext::setCurrentId($tenant->id);

        Storage::disk('public')->put('avatars/test-avatar.png', str_repeat('a', 256));

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'avatar' => 'avatars/test-avatar.png',
        ]);

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Storage Workspace',
            'slug' => 'other-storage-workspace-' . Tenant::query()->count(),
            'is_active' => true,
        ]);
        User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'avatar' => 'avatars/other-tenant.png',
        ]);
        Storage::disk('public')->put('avatars/other-tenant.png', str_repeat('b', 512));

        $state = app(TenantPlanManager::class)->usageState(PlanLimit::TOTAL_STORAGE_BYTES, $tenant->id);

        $this->assertSame(1024, $state['limit']);
        $this->assertSame(256, $state['usage']);
        $this->assertSame(768, $state['remaining']);
        $this->assertSame('ok', $state['status']);
    }

    public function test_subscription_settings_displays_total_storage_quota(): void
    {
        [$tenant, $user] = $this->makeTenantWithPlanLimits([
            PlanLimit::USERS => 5,
            PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
        ]);

        TenantContext::setCurrentId($tenant->id);

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('settings.subscription'))
            ->assertOk()
            ->assertSee('Total Storage');
    }

    private function makeTenantWithPlanLimits(array $limits): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Storage Workspace',
            'slug' => 'storage-workspace-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'storage-plan-' . $tenant->id,
            'name' => 'Storage Plan ' . $tenant->id,
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
            'billing_reference' => 'storage-plan-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$tenant, $user, $plan];
    }
}
