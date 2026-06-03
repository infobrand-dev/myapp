<?php

namespace Tests\Feature\Commerce;

use App\Models\Tenant;
use App\Models\Company;
use App\Models\User;
use App\Modules\Affiliate\AffiliateServiceProvider;
use App\Modules\Affiliate\Models\AffiliateListing;
use App\Modules\Affiliate\Services\TenantAffiliateService;
use App\Modules\Products\Models\Product;
use App\Modules\Storefront\StorefrontServiceProvider;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AffiliateMarketplaceClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(AffiliateServiceProvider::class);
        $this->app->register(StorefrontServiceProvider::class);

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->string('avatar')->nullable();
                $table->string('locale')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
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
            });
        }

        Schema::create('affiliate_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('source_tenant_id')->index();
            $table->unsignedBigInteger('source_product_id')->index();
            $table->string('share_code', 32)->unique();
            $table->string('status', 30)->default('active');
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_rate', 18, 2)->default(0);
            $table->json('landing_page_meta')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'source_product_id']);
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

    public function test_meetra_user_can_claim_affiliate_product_without_duplicating_source_product(): void
    {
        $sellerTenant = Tenant::query()->create([
            'name' => 'Seller',
            'slug' => 'seller',
            'is_active' => true,
        ]);
        $affiliateTenant = Tenant::query()->create([
            'name' => 'Affiliate',
            'slug' => 'affiliate',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'tenant_id' => $affiliateTenant->id,
            'name' => 'Meetra Affiliate',
            'email' => 'meetra-affiliate@example.test',
            'password' => bcrypt('secret'),
        ]);

        $product = Product::query()->create([
            'tenant_id' => $sellerTenant->id,
            'type' => 'service',
            'name' => 'Growth Offer',
            'slug' => 'growth-offer',
            'sku' => 'GO-001',
            'barcode' => null,
            'description' => 'Growth affiliate offer',
            'cost_price' => 0,
            'sell_price' => 250000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
            'featured_image_path' => null,
            'default_supplier_contact_id' => null,
            'meta' => [
                'public_offer' => [
                    'headline' => 'Scale your funnel',
                    'subtitle' => 'Done-for-you sprint',
                    'cta_label' => 'Ambil seat',
                ],
                'affiliate_offer' => [
                    'enabled' => true,
                    'commission_type' => 'percentage',
                    'commission_rate' => 12.5,
                    'allow_landing_copy' => true,
                ],
            ],
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        TenantContext::setCurrentId($affiliateTenant->id);

        $listing = app(TenantAffiliateService::class)->claimProduct($product, $user, [
            'headline' => 'My custom angle',
            'subtitle' => 'Lebih cepat closing',
        ]);

        $this->assertInstanceOf(AffiliateListing::class, $listing);
        $this->assertSame($affiliateTenant->id, (int) $listing->tenant_id);
        $this->assertSame($sellerTenant->id, (int) $listing->source_tenant_id);
        $this->assertSame($product->id, (int) $listing->source_product_id);
        $this->assertSame('My custom angle', data_get($listing->landing_page_meta, 'headline'));
        $this->assertSame(1, Product::query()->count());
        $this->assertSame(1, AffiliateListing::query()->count());
    }

    public function test_affiliate_listing_generates_public_landing_url_on_affiliator_host(): void
    {
        $affiliateTenant = Tenant::query()->create([
            'name' => 'Affiliate',
            'slug' => 'affiliate',
            'is_active' => true,
            'meta' => ['public_storefront_enabled' => true],
        ]);
        $sellerTenant = Tenant::query()->create([
            'name' => 'Seller',
            'slug' => 'seller',
            'is_active' => true,
        ]);

        Company::query()->create([
            'tenant_id' => $affiliateTenant->id,
            'name' => 'Affiliate Company',
            'slug' => 'affiliate-company',
            'code' => 'AFF',
            'is_active' => true,
            'meta' => [],
        ]);

        $user = User::query()->create([
            'tenant_id' => $affiliateTenant->id,
            'name' => 'Meetra Affiliate',
            'email' => 'landing-affiliate@example.test',
            'password' => bcrypt('secret'),
        ]);

        $product = Product::query()->create([
            'tenant_id' => $sellerTenant->id,
            'type' => 'service',
            'name' => 'Growth Offer',
            'slug' => 'growth-offer',
            'sku' => 'GO-001',
            'barcode' => null,
            'description' => 'Growth affiliate offer',
            'cost_price' => 0,
            'sell_price' => 250000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
            'featured_image_path' => null,
            'default_supplier_contact_id' => null,
            'meta' => [
                'public_offer' => [
                    'headline' => 'Scale your funnel',
                    'subtitle' => 'Done-for-you sprint',
                    'visibility' => 'direct',
                    'cta_label' => 'Ambil seat',
                ],
                'affiliate_offer' => [
                    'enabled' => true,
                    'commission_type' => 'percentage',
                    'commission_rate' => 12.5,
                    'allow_landing_copy' => true,
                ],
            ],
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        TenantContext::setCurrentId($affiliateTenant->id);
        $listing = app(TenantAffiliateService::class)->claimProduct($product, $user, [
            'headline' => 'My custom angle',
            'subtitle' => 'Lebih cepat closing',
            'cta_label' => 'Beli via saya',
        ]);

        config()->set('multitenancy.mode', 'saas');
        config()->set('app.url', 'http://myapp.test');
        config()->set('multitenancy.saas_domain', 'myapp.test');

        $publicUrl = app(TenantAffiliateService::class)->publicListingUrl($listing->fresh(['user.tenant']));

        $this->assertNotNull($publicUrl);
        $this->assertStringContainsString('affiliate/products/' . $listing->share_code, $publicUrl);
        $this->assertStringContainsString('account=affiliate', $publicUrl);
    }
}
