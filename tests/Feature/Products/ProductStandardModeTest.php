<?php

namespace Tests\Feature\Products;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Products\ProductsServiceProvider;
use App\Support\FeatureMode;
use App\Support\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class ProductStandardModeTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('ProductStandardModeTest harus dijalankan di PostgreSQL atau database non-SQLite yang setara dengan runtime aplikasi.');
        }

        $this->registerModuleProviders([
            ProductsServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'app/Modules/Products/database/migrations',
        ]);

        $this->bootstrapDefaultOperationalContext();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_standard_mode_rejects_variant_product_creation(): void
    {
        $user = $this->userWithPermissions(['products.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('products.store'), [
                'type' => 'variant',
                'name' => 'Produk Variant Standard',
                'cost_price' => 1000,
                'sell_price' => 2000,
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_standard_mode_can_create_simple_product(): void
    {
        $user = $this->userWithPermissions(['products.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('products.store'), [
                'type' => 'simple',
                'name' => 'Produk Simple Standard',
                'sku' => 'STD-SIMPLE-001',
                'cost_price' => 1000,
                'sell_price' => 2500,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'tenant_id' => 1,
            'name' => 'Produk Simple Standard',
            'type' => 'simple',
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function attachCompanyAccess(User $user): Company
    {
        $company = Company::query()->findOrFail(1);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company;
    }

    private function activateStarterPlan(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'code' => 'accounting-starter-products',
            'name' => 'Starter Products',
            'price' => 100000,
            'currency' => 'IDR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'features' => [
                PlanFeature::ACCOUNTING => true,
                PlanFeature::ADVANCED_REPORTS => false,
            ],
            'limits' => [],
            'meta' => ['product_line' => 'accounting'],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => 1,
            'subscription_plan_id' => $plan->id,
            'product_line' => 'accounting',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
