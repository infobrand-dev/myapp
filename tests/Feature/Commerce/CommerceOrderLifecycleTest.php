<?php

namespace Tests\Feature\Commerce;

use App\Models\Company;
use App\Models\Tenant;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Storefront\Exceptions\StorefrontCheckoutException;
use App\Modules\Storefront\Services\StorefrontCheckoutService;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\CompanyContext;
use App\Support\Shipping\CheckoutShippingQuoteService;
use App\Support\Shipping\Contracts\ShippingProviderDriver;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class CommerceOrderLifecycleTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModulePaths([
            'app/Modules/Contacts/Database/migrations',
            'app/Modules/Finance/Database/migrations',
            'app/Modules/Payments/Database/migrations',
            'app/Modules/Products/Database/migrations',
            'app/Modules/Sales/Database/migrations',
        ]);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_05_27_180000_create_tenant_payment_gateways_table.php',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_05_27_190000_create_tenant_shipping_providers_table.php',
            '--realpath' => false,
        ])->run();
    }

    public function test_storefront_checkout_reuses_existing_pending_order_for_same_payload_window(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'meta' => [],
            'is_active' => true,
        ]);

        $tenant->update([
            'meta' => [
                'public_storefront_enabled' => true,
                'default_public_company_id' => $company->id,
            ],
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Bottle',
            'slug' => 'acme-bottle',
            'sku' => 'ACME-BTL-001',
            'description' => 'Reusable bottle',
            'cost_price' => 10000,
            'sell_price' => 25000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
        ]);

        $payload = [
            'qty' => 1,
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.test',
            'customer_phone' => '08123456789',
            'customer_address' => 'Jakarta',
            'customer_note' => 'Tolong kirim sore.',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'manual',
        ];

        $service = app(StorefrontCheckoutService::class);

        $first = $service->createOrder($product, $payload);
        $second = $service->createOrder($product, $payload);

        $this->assertSame($first['sale']->id, $second['sale']->id);
        $this->assertSame('pending_payment', data_get($second['sale']->meta, 'commerce.status'));
        $this->assertSame($company->id, (int) data_get($second['sale']->meta, 'commerce.public_company_id'));
        $this->assertNotEmpty(data_get($second['sale']->meta, 'commerce.timeline'));
    }

    public function test_commerce_expire_pending_orders_command_marks_only_elapsed_orders(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'is_active' => true,
            'meta' => [],
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $expiredCandidate = Sale::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'sale_number' => 'SO-EXP-001',
            'external_reference' => 'storefront-expired',
            'status' => Sale::STATUS_DRAFT,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'source' => Sale::SOURCE_ONLINE,
            'transaction_date' => now(),
            'subtotal' => 100000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100000,
            'paid_total' => 0,
            'balance_due' => 100000,
            'currency_code' => 'IDR',
            'meta' => [
                'commerce' => [
                    'channel' => 'public_storefront',
                    'status' => 'pending_payment',
                    'expires_at' => now()->subMinutes(5)->toIso8601String(),
                ],
            ],
        ]);

        $freshCandidate = Sale::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'sale_number' => 'SO-EXP-002',
            'external_reference' => 'storefront-fresh',
            'status' => Sale::STATUS_DRAFT,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'source' => Sale::SOURCE_ONLINE,
            'transaction_date' => now(),
            'subtotal' => 50000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 50000,
            'paid_total' => 0,
            'balance_due' => 50000,
            'currency_code' => 'IDR',
            'meta' => [
                'commerce' => [
                    'channel' => 'public_storefront',
                    'status' => 'pending_payment',
                    'expires_at' => now()->addMinutes(30)->toIso8601String(),
                ],
            ],
        ]);

        $this->artisan('commerce:expire-pending-orders')
            ->assertExitCode(0);

        $this->assertSame('expired', data_get($expiredCandidate->fresh()->meta, 'commerce.status'));
        $this->assertSame('pending_payment', data_get($freshCandidate->fresh()->meta, 'commerce.status'));
    }

    public function test_delivery_checkout_persists_selected_shipping_rate_when_quote_is_available(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'meta' => [],
            'is_active' => true,
        ]);

        $tenant->update([
            'meta' => [
                'public_storefront_enabled' => true,
                'default_public_company_id' => $company->id,
            ],
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Hoodie',
            'slug' => 'acme-hoodie',
            'sku' => 'ACME-HOD-001',
            'description' => 'Premium hoodie',
            'cost_price' => 50000,
            'sell_price' => 125000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => true,
        ]);

        $quoteService = Mockery::mock(CheckoutShippingQuoteService::class);
        $quoteService->shouldReceive('quoteForItems')
            ->once()
            ->andReturn([
                'provider' => 'biteship',
                'request' => [
                    'origin_postal_code' => '12440',
                    'destination_postal_code' => '12240',
                ],
                'selected_rate' => [
                    'provider' => 'biteship',
                    'courier_name' => 'SiCepat',
                    'service_name' => 'BEST',
                    'price' => 18000,
                    'currency' => 'IDR',
                    'etd' => '1-2 hari',
                    'selected_at' => now()->toIso8601String(),
                ],
                'options' => [],
            ]);
        $this->app->instance(CheckoutShippingQuoteService::class, $quoteService);

        $sale = app(StorefrontCheckoutService::class)->createOrder($product, [
            'qty' => 1,
            'customer_name' => 'Sari',
            'customer_email' => 'sari@example.test',
            'customer_phone' => '08111111111',
            'customer_address' => 'Bandung',
            'destination_postal_code' => '12240',
            'customer_note' => null,
            'fulfillment_method' => 'delivery',
            'payment_method' => 'manual',
        ])['sale']->fresh();

        $this->assertSame('SiCepat', data_get($sale->meta, 'commerce.shipping.selected_rate.courier_name'));
        $this->assertSame(18000.0, (float) data_get($sale->meta, 'commerce.shipping.selected_rate.price'));
        $this->assertSame('ready', data_get($sale->meta, 'commerce.shipping.status'));
        $this->assertSame(18000.0, (float) data_get($sale->totals_snapshot, 'shipping_total'));
        $this->assertSame(143000.0, (float) $sale->grand_total);
    }

    public function test_storefront_cart_checkout_creates_multi_item_order(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'meta' => [],
            'is_active' => true,
        ]);

        $tenant->update([
            'meta' => [
                'public_storefront_enabled' => true,
                'default_public_company_id' => $company->id,
            ],
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $first = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Bottle',
            'slug' => 'acme-bottle-2',
            'sku' => 'ACME-BTL-002',
            'description' => 'Reusable bottle',
            'cost_price' => 10000,
            'sell_price' => 25000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
        ]);

        $second = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'service',
            'name' => 'Acme Design Session',
            'slug' => 'acme-design-session',
            'sku' => 'ACME-SVC-001',
            'description' => 'Design consultation',
            'cost_price' => 0,
            'sell_price' => 150000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
        ]);

        $result = app(StorefrontCheckoutService::class)->createOrderFromItems(collect([
            ['product' => $first, 'qty' => 2],
            ['product' => $second, 'qty' => 1],
        ]), [
            'customer_name' => 'Rina',
            'customer_email' => 'rina@example.test',
            'customer_phone' => '08122222222',
            'customer_address' => 'Jakarta',
            'customer_note' => 'Mohon konfirmasi via WA.',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'manual',
        ]);

        $sale = $result['sale']->fresh('items');

        $this->assertCount(2, $sale->items);
        $this->assertSame(200000.0, (float) $sale->subtotal);
        $this->assertSame(200000.0, (float) $sale->grand_total);
        $this->assertSame('pending_payment', data_get($sale->meta, 'commerce.status'));
    }

    public function test_delivery_quote_requires_product_weight_for_physical_items(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'meta' => ['shipping_origin_postal_code' => '12440'],
            'is_active' => true,
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Hoodie',
            'slug' => 'acme-hoodie',
            'sku' => 'ACME-HOD-001',
            'cost_price' => 50000,
            'sell_price' => 125000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => true,
            'meta' => [],
        ]);

        $driver = Mockery::mock(ShippingProviderDriver::class);
        $driver->shouldReceive('provider')->andReturn('biteship');

        $manager = Mockery::mock(ShippingProviderManager::class);
        $manager->shouldReceive('assertQuoteReady')->once()->andReturn($driver);
        $manager->shouldNotReceive('quoteRates');

        $service = new CheckoutShippingQuoteService($manager);

        $this->expectException(StorefrontCheckoutException::class);
        $this->expectExceptionMessage('Berat produk belum diatur untuk pengiriman.');

        $service->quoteForCheckout($product, [
            'qty' => 1,
            'fulfillment_method' => 'delivery',
            'destination_postal_code' => '12240',
        ]);
    }

    public function test_delivery_quote_requires_explicit_rate_selection_when_multiple_options_exist(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'meta' => ['shipping_origin_postal_code' => '12440'],
            'is_active' => true,
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'simple',
            'name' => 'Acme Hoodie',
            'slug' => 'acme-hoodie-2',
            'sku' => 'ACME-HOD-002',
            'cost_price' => 50000,
            'sell_price' => 125000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => true,
            'meta' => ['shipping' => ['weight_grams' => 900]],
        ]);

        $driver = Mockery::mock(ShippingProviderDriver::class);
        $driver->shouldReceive('provider')->andReturn('biteship');

        $manager = Mockery::mock(ShippingProviderManager::class);
        $manager->shouldReceive('assertQuoteReady')->once()->andReturn($driver);
        $manager->shouldReceive('quoteRates')->once()->andReturn([
            'provider' => 'biteship',
            'options' => [
                ['courier_code' => 'jne', 'courier_name' => 'JNE', 'service_code' => 'REG', 'service_name' => 'REG', 'price' => 20000, 'currency' => 'IDR', 'etd' => '2-3 hari'],
                ['courier_code' => 'sicepat', 'courier_name' => 'SiCepat', 'service_code' => 'BEST', 'service_name' => 'BEST', 'price' => 18000, 'currency' => 'IDR', 'etd' => '1-2 hari'],
            ],
            'raw' => [],
        ]);

        $service = new CheckoutShippingQuoteService($manager);

        try {
            $service->quoteForCheckout($product, [
                'qty' => 1,
                'fulfillment_method' => 'delivery',
                'destination_postal_code' => '12240',
            ]);
            $this->fail('Expected StorefrontCheckoutException was not thrown.');
        } catch (StorefrontCheckoutException $exception) {
            $this->assertArrayHasKey('storefront.shipping_options', $exception->flash());
            $this->assertCount(2, $exception->flash()['storefront.shipping_options']);
            $this->assertArrayHasKey('selected_shipping_rate', $exception->errors());
        }
    }

    public function test_commerce_payment_failure_and_cancellation_states_are_distinct(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
            'meta' => [],
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Commerce Company',
            'slug' => 'acme-commerce-company',
            'code' => 'ACME',
            'is_active' => true,
            'meta' => [],
        ]);

        TenantContext::setCurrentId($tenant->id);
        CompanyContext::setCurrentId($company->id);

        $sale = Sale::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'sale_number' => 'SO-PAY-001',
            'external_reference' => 'storefront-pay-001',
            'status' => Sale::STATUS_DRAFT,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'source' => Sale::SOURCE_ONLINE,
            'transaction_date' => now(),
            'subtotal' => 100000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100000,
            'paid_total' => 0,
            'balance_due' => 100000,
            'currency_code' => 'IDR',
            'meta' => [
                'commerce' => [
                    'channel' => 'public_storefront',
                    'status' => 'pending_payment',
                    'payment' => [
                        'status' => 'checkout_created',
                    ],
                ],
            ],
        ]);

        $lifecycle = app(CommerceOrderLifecycleService::class);
        $failed = $lifecycle->markPaymentFailed($sale, 'Gateway timeout');
        $this->assertSame('failed', data_get($failed->meta, 'commerce.payment.status'));
        $this->assertSame('Gateway timeout', data_get($failed->meta, 'commerce.payment.failure_reason'));

        $cancelled = $lifecycle->markPaymentCancelled($failed, 'Customer cancelled checkout');

        $this->assertSame('cancelled', data_get($cancelled->meta, 'commerce.payment.status'));
        $this->assertSame('Customer cancelled checkout', data_get($cancelled->meta, 'commerce.payment.cancel_reason'));
        $this->assertSame('pending_payment', data_get($cancelled->meta, 'commerce.status'));
    }
}
