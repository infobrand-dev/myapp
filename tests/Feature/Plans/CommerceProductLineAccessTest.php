<?php

namespace Tests\Feature\Plans;

use App\Models\Module;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\ModuleManager;
use App\Support\PlanFeature;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CommerceProductLineAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);

        Route::middleware(['web', 'auth', 'plan.feature:accounting'])->get('/_feature-test/accounting', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:commerce'])->get('/_feature-test/commerce', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:accounting,commerce'])->get('/_feature-test/shared', fn () => 'ok');
        Route::middleware(['web', 'auth', 'plan.feature:storefront'])->get('/_feature-test/storefront', fn () => 'ok');

        Route::middleware(['web', 'auth'])->get('/products', fn () => 'products')->name('products.index');
        Route::middleware(['web', 'auth'])->get('/finance/transactions', fn () => 'finance')->name('finance.transactions.index');
        Route::middleware(['web', 'auth'])->get('/reports', fn () => 'reports')->name('reports.dashboard');
        Route::middleware(['web', 'auth'])->get('/commerce/orders', fn () => 'orders')->name('sales.commerce.index');
        Route::middleware(['web', 'auth'])->get('/commerce/payments', fn () => 'payments')->name('payments.commerce.index');
        Route::middleware(['web', 'auth'])->get('/storefront', fn () => 'storefront')->name('storefront.index');
        Route::middleware(['web', 'auth'])->get('/shipping', fn () => 'shipping')->name('shipping.index');
        Route::middleware(['web', 'auth'])->get('/fulfillment', fn () => 'fulfillment')->name('fulfillment.index');
    }

    public function test_commerce_plan_is_independent_from_accounting_feature(): void
    {
        [$commerceUser] = $this->makeTenantWithPlan('commerce_starter', 'commerce-only');
        [$accountingUser] = $this->makeTenantWithFeatureOverrides([
            PlanFeature::ACCOUNTING => true,
            PlanFeature::COMMERCE => false,
        ], 'accounting-only');

        $this->actingAs($commerceUser)->get('/_feature-test/commerce')->assertOk();
        $this->actingAs($commerceUser)->get('/_feature-test/storefront')->assertOk();
        $this->actingAs($commerceUser)->get('/_feature-test/shared')->assertOk();
        $this->actingAs($commerceUser)->get('/_feature-test/accounting')->assertForbidden();

        $this->actingAs($accountingUser)->get('/_feature-test/accounting')->assertOk();
        $this->actingAs($accountingUser)->get('/_feature-test/shared')->assertOk();
        $this->actingAs($accountingUser)->get('/_feature-test/commerce')->assertForbidden();
    }

    public function test_dashboard_sidebar_hides_accounting_modules_for_commerce_only_tenant(): void
    {
        config()->set('multitenancy.mode', 'single');

        [$user] = $this->makeTenantWithPlan('commerce_starter', 'commerce-menu');

        foreach ([
            'products' => 'Products',
            'sales' => 'Sales',
            'payments' => 'Payments',
            'finance' => 'Finance',
            'reports' => 'Reports',
            'storefront' => 'Storefront',
            'shipping' => 'Shipping',
            'fulfillment' => 'Fulfillment',
        ] as $slug => $name) {
            Module::query()->create([
                'slug' => $slug,
                'name' => $name,
                'provider' => 'Test\\' . $name,
                'version' => '1.0.0',
                'is_active' => true,
                'installed_at' => now(),
            ]);
        }

        $this->app->forgetInstance(ModuleManager::class);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Storefront')
            ->assertSeeText('Shipping')
            ->assertSeeText('Fulfillment')
            ->assertSeeText('Sales')
            ->assertSeeText('Payments')
            ->assertSeeText('Products')
            ->assertDontSeeText('Finance')
            ->assertDontSeeText('Reports');
    }

    private function makeTenantWithFeatureOverrides(array $features, string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'custom-' . $slug,
            'name' => 'Custom ' . $slug,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => $features,
            'limits' => [],
            'meta' => [
                'product_line' => !empty($features[PlanFeature::COMMERCE]) ? 'commerce' : 'accounting',
            ],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'product_line' => $plan->productLine(),
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'test-' . $slug,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }

    private function makeTenantWithPlan(string $planCode, string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->where('code', $planCode)->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'product_line' => $plan->productLine(),
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'test-' . $slug,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }
}
