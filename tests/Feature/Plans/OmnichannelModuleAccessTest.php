<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\PlanFeature;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OmnichannelModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);

        Route::middleware(['web', 'auth', 'plan.feature:social_media'])->get('/_feature-test/social-media', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:chatbot_ai'])->get('/_feature-test/chatbot-ai', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:live_chat'])->get('/_feature-test/live-chat', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:crm'])->get('/_feature-test/crm', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:whatsapp_api'])->get('/_feature-test/whatsapp-api', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:whatsapp_web'])->get('/_feature-test/whatsapp-web', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:commerce'])->get('/_feature-test/commerce', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:project_management'])->get('/_feature-test/project-management', fn () => 'ok');
    }

    public function test_starter_v2_is_limited_to_social_live_chat_and_crm(): void
    {
        [$user] = $this->makeTenantWithPlan('starter-v2');

        $this->actingAs($user)
            ->get('/_feature-test/social-media')
            ->assertOk();

        $this->actingAs($user)
            ->get('/_feature-test/chatbot-ai')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/_feature-test/whatsapp-api')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/_feature-test/whatsapp-web')
            ->assertForbidden();
    }

    public function test_growth_v2_grants_ai_whatsapp_api_and_whatsapp_web(): void
    {
        [$user] = $this->makeTenantWithPlan('growth-v2');

        $this->actingAs($user)
            ->get('/_feature-test/chatbot-ai')
            ->assertOk();

        $this->actingAs($user)
            ->get('/_feature-test/whatsapp-api')
            ->assertOk();

        $this->actingAs($user)
            ->get('/_feature-test/whatsapp-web')
            ->assertOk();
    }

    public function test_live_chat_follows_plan_entitlement(): void
    {
        [$starterUser] = $this->makeTenantWithPlan('starter-v2');
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

    public function test_crm_follows_plan_entitlement(): void
    {
        [$starterUser] = $this->makeTenantWithPlan('starter-v2');
        [$freeUser] = $this->makeTenantWithPlan('free');

        $this->actingAs($starterUser)
            ->get('/_feature-test/crm')
            ->assertOk();

        $this->actingAs($freeUser)
            ->get('/_feature-test/crm')
            ->assertForbidden();
    }

    public function test_commerce_bundle_is_dynamic_and_opt_in(): void
    {
        [$starterUser] = $this->makeTenantWithPlan('starter-v2');
        [$commerceUser] = $this->makeTenantWithFeatureOverrides([
            PlanFeature::CRM => true,
            PlanFeature::COMMERCE => true,
        ]);

        $this->actingAs($starterUser)
            ->get('/_feature-test/commerce')
            ->assertForbidden();

        $this->actingAs($commerceUser)
            ->get('/_feature-test/commerce')
            ->assertOk();
    }

    public function test_project_management_bundle_is_dynamic_and_opt_in(): void
    {
        [$growthUser] = $this->makeTenantWithPlan('growth-v2');
        [$projectUser] = $this->makeTenantWithFeatureOverrides([
            PlanFeature::PROJECT_MANAGEMENT => true,
        ]);

        $this->actingAs($growthUser)
            ->get('/_feature-test/project-management')
            ->assertForbidden();

        $this->actingAs($projectUser)
            ->get('/_feature-test/project-management')
            ->assertOk();
    }

    public function test_saas_tenant_without_active_subscription_is_blocked_from_feature_routes(): void
    {
        config()->set('multitenancy.mode', 'saas');

        $tenant = Tenant::query()->create([
            'name' => 'No Plan Workspace',
            'slug' => 'no-plan-workspace',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user)
            ->get('/_feature-test/social-media')
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
