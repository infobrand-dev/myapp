<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OmnichannelModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'plan.feature:social_media'])->get('/_feature-test/social-media', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:chatbot_ai'])->get('/_feature-test/chatbot-ai', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:live_chat'])->get('/_feature-test/live-chat', fn () => 'ok');
    }

    public function test_tenant_without_feature_is_blocked(): void
    {
        [$user] = $this->makeTenantWithPlan('starter');

        $this->actingAs($user)
            ->get('/_feature-test/social-media')
            ->assertOk();

        $this->actingAs($user)
            ->get('/_feature-test/chatbot-ai')
            ->assertForbidden();
    }

    public function test_tenant_with_plan_feature_can_access_module_route(): void
    {
        [$user] = $this->makeTenantWithPlan('growth');

        $this->actingAs($user)
            ->get('/_feature-test/chatbot-ai')
            ->assertOk();
    }

    public function test_live_chat_follows_plan_entitlement(): void
    {
        [$starterUser] = $this->makeTenantWithPlan('starter');
        [$disabledUser] = $this->makeTenantWithFeatureOverrides([
            PlanFeature::CONVERSATIONS => true,
            PlanFeature::LIVE_CHAT => false,
        ]);

        $this->actingAs($starterUser)
            ->get('/_feature-test/live-chat')
            ->assertOk();

        $this->actingAs($disabledUser)
            ->get('/_feature-test/live-chat')
            ->assertForbidden();
    }

    private function makeTenantWithFeatureOverrides(array $features): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Feature Override Workspace',
            'slug' => 'feature-override-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'custom-' . $tenant->id,
            'name' => 'Custom ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => $features,
            'limits' => [],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'custom-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }

    private function makeTenantWithPlan(string $planCode): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($planCode) . ' Workspace',
            'slug' => $planCode . '-tenant',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->where('code', $planCode)->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'test-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }
}
