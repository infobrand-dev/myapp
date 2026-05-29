<?php

namespace Tests\Feature\Storefront;

use App\Models\SubscriptionPlan;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Modules\Storefront\StorefrontServiceProvider;
use App\Support\TenantContext;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class PublicStorefrontTenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'meetra.id');
        config()->set('app.url', 'https://myapp.test');

        $this->seed(SubscriptionPlanSeeder::class);
        $this->createStorefrontTables();
        $this->app->register(StorefrontServiceProvider::class);

        Route::middleware(['web', 'plan.feature:commerce', 'plan.feature:storefront'])
            ->get('/_storefront-probe', fn () => response()->json([
                'tenant_id' => TenantContext::currentId(),
                'tenant_slug' => optional(TenantContext::currentTenant())->slug,
            ]));
    }

    public function test_storefront_style_web_route_resolves_tenant_from_local_app_url_subdomain(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Store',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()->where('code', 'commerce_starter')->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'product_line' => $plan->productLine(),
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'storefront-acme',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Company',
            'slug' => 'acme-company',
            'code' => 'ACME',
            'is_active' => true,
            'meta' => [],
        ]);

        $tenant->update([
            'meta' => [
                'public_storefront_enabled' => true,
                'default_public_company_id' => $company->id,
            ],
        ]);

        $this->get('http://acme.myapp.test/_storefront-probe')
            ->assertOk()
            ->assertJson([
                'tenant_id' => $tenant->id,
                'tenant_slug' => 'acme',
            ]);
    }

    public function test_root_route_on_tenant_subdomain_renders_public_storefront_instead_of_login_redirect(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Store',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()->where('code', 'commerce_starter')->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'product_line' => $plan->productLine(),
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'storefront-root-acme',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Company',
            'slug' => 'acme-company',
            'code' => 'ACME',
            'is_active' => true,
            'meta' => [],
        ]);

        $tenant->update([
            'meta' => [
                'public_storefront_enabled' => true,
                'default_public_company_id' => $company->id,
            ],
        ]);

        \DB::table('products')->insert([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Demo Product',
            'slug' => 'acme-demo-product',
            'sku' => 'ACME-DEMO-001',
            'description' => 'Demo storefront product',
            'cost_price' => 10000,
            'sell_price' => 25000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
            'featured_image_path' => null,
            'meta' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        TenantContext::setCurrentId($tenant->id);

        $this->get('http://acme.myapp.test/')
            ->assertOk()
            ->assertSeeText('Acme Store')
            ->assertSeeText('Acme Demo Product')
            ->assertDontSeeText('Masuk ke workspace');
    }

    private function createStorefrontTables(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('type', 50)->default('simple');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->decimal('minimum_stock', 18, 4)->default(0);
            $table->decimal('reorder_point', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->string('featured_image_path')->nullable();
            $table->unsignedBigInteger('default_supplier_contact_id')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'sku']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('collection_name', 30)->default('gallery');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
