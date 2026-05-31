<?php

namespace Tests\Feature\Commerce;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Affiliate\AffiliateServiceProvider;
use App\Modules\Affiliate\Models\AffiliateListing;
use App\Modules\Affiliate\Models\AffiliateReferral;
use App\Modules\Affiliate\Services\TenantAffiliateConversionService;
use App\Modules\Affiliate\Services\TenantAffiliateService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Wallet\WalletServiceProvider;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Services\TenantWalletService;
use App\Modules\Wallet\Services\TenantWalletSettlementService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceCreatorSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.commerce_creator.platform_fee_percentage', 10);
        config()->set('services.commerce_creator.platform_fee_flat', 0);
        config()->set('services.tenant_affiliate.default_commission_rate', 5);

        $this->app->register(AffiliateServiceProvider::class);
        $this->app->register(WalletServiceProvider::class);

        $this->createCommerceTables();
    }

    public function test_paid_commerce_order_creates_affiliate_conversion_and_wallet_ledger(): void
    {
        $sellerTenant = Tenant::query()->create([
            'name' => 'Acme Commerce',
            'slug' => 'acme',
            'is_active' => true,
        ]);
        $affiliateTenant = Tenant::query()->create([
            'name' => 'Promo Lab',
            'slug' => 'promo-lab',
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'tenant_id' => $sellerTenant->id,
            'name' => 'Acme Company',
            'slug' => 'acme-company',
            'code' => 'ACME',
            'is_active' => true,
            'meta' => [],
        ]);

        $affiliateUser = User::query()->create([
            'tenant_id' => $affiliateTenant->id,
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.test',
            'password' => bcrypt('secret'),
        ]);

        $product = Product::query()->create([
            'tenant_id' => $sellerTenant->id,
            'type' => 'service',
            'name' => 'Creator Sprint',
            'slug' => 'creator-sprint',
            'sku' => 'CS-001',
            'barcode' => null,
            'description' => 'Sprint offer',
            'cost_price' => 0,
            'sell_price' => 100000,
            'minimum_stock' => 0,
            'reorder_point' => 0,
            'is_active' => true,
            'track_stock' => false,
            'featured_image_path' => null,
            'default_supplier_contact_id' => null,
            'meta' => [
                'public_offer' => [
                    'headline' => 'Launch Faster',
                    'subtitle' => 'Offer sprint',
                    'cta_label' => 'Ambil sekarang',
                ],
                'affiliate_offer' => [
                    'enabled' => true,
                    'commission_type' => 'percentage',
                    'commission_rate' => 5,
                    'allow_landing_copy' => true,
                ],
            ],
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        TenantContext::setCurrentId($affiliateTenant->id);
        $listing = app(TenantAffiliateService::class)->claimProduct($product, $affiliateUser, [
            'headline' => 'My faster launch angle',
        ]);

        $this->assertInstanceOf(AffiliateListing::class, $listing);
        $this->assertSame($affiliateTenant->id, (int) $listing->tenant_id);
        $this->assertSame($sellerTenant->id, (int) $listing->source_tenant_id);

        TenantContext::setCurrentId($sellerTenant->id);
        CompanyContext::setCurrentId($company->id);

        $sale = Sale::query()->create([
            'tenant_id' => $sellerTenant->id,
            'company_id' => $company->id,
            'sale_number' => 'SO-001',
            'contact_id' => null,
            'customer_name_snapshot' => 'Buyer One',
            'customer_email_snapshot' => 'buyer@example.test',
            'customer_phone_snapshot' => '08123',
            'customer_address_snapshot' => null,
            'customer_snapshot' => null,
            'status' => Sale::STATUS_FINALIZED,
            'payment_status' => Sale::PAYMENT_PAID,
            'source' => Sale::SOURCE_ONLINE,
            'branch_id' => null,
            'pos_cash_session_id' => null,
            'transaction_date' => now(),
            'due_date' => null,
            'finalized_at' => now(),
            'voided_at' => null,
            'cancelled_at' => null,
            'subtotal' => 100000,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100000,
            'paid_total' => 100000,
            'balance_due' => 0,
            'currency_code' => 'IDR',
            'notes' => null,
            'customer_note' => null,
            'attachment_path' => null,
            'void_reason' => null,
            'totals_snapshot' => null,
            'meta' => [
                'commerce' => [
                    'channel' => 'public_brand',
                    'status' => 'paid',
                    'fulfillment_type' => 'service',
                    'affiliate' => [
                        'code' => $listing->share_code,
                        'status' => 'captured',
                    ],
                ],
            ],
            'created_by' => null,
            'updated_by' => null,
            'finalized_by' => null,
            'voided_by' => null,
            'cancelled_by' => null,
        ]);

        $payment = new Payment();

        app(TenantAffiliateConversionService::class)->handle($payment, collect([$sale]));
        app(TenantWalletSettlementService::class)->handle($payment, collect([$sale->fresh()]));

        $sale->refresh();

        $this->assertDatabaseHas('affiliate_referrals', [
            'tenant_id' => $sellerTenant->id,
            'affiliate_tenant_id' => $affiliateTenant->id,
            'sale_id' => $sale->id,
            'status' => 'converted',
        ]);

        $referral = AffiliateReferral::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame(5000.0, (float) $referral->commission_amount);
        $this->assertSame('affiliate_referral', data_get($sale->meta, 'commerce.channel'));
        $this->assertSame('converted', data_get($sale->meta, 'commerce.affiliate.status'));
        $this->assertSame($listing->id, (int) data_get($sale->meta, 'commerce.affiliate.listing_id'));

        $entries = WalletLedgerEntry::query()->where('tenant_id', $sellerTenant->id)->orderBy('id')->get();
        $this->assertCount(3, $entries);
        $this->assertSame(['gross_sale', 'platform_fee', 'affiliate_commission'], $entries->pluck('entry_type')->all());
        $this->assertSame('available', data_get($sale->meta, 'commerce.wallet_settlement.status'));
        $this->assertSame(100000.0, (float) data_get($sale->meta, 'commerce.wallet_settlement.gross'));
        $this->assertSame(10000.0, (float) data_get($sale->meta, 'commerce.wallet_settlement.platform_fee'));
        $this->assertSame(5000.0, (float) data_get($sale->meta, 'commerce.wallet_settlement.affiliate_commission'));

        $walletService = app(TenantWalletService::class);
        $account = $walletService->account($sellerTenant->id);
        $balances = $walletService->balances($account);
        $this->assertSame(90000.0, $balances['available']);
        $this->assertSame(-5000.0, $balances['locked']);

        TenantContext::setCurrentId($sellerTenant->id);
        $payout = $walletService->requestPayout([
            'amount' => 50000,
            'bank_name' => 'BCA',
            'account_name' => 'Acme Owner',
            'account_number' => '1234567890',
        ]);

        $walletService->approve($payout);
        $walletService->markPaid($payout->fresh());

        $this->assertDatabaseHas('wallet_payout_requests', [
            'id' => $payout->id,
            'status' => 'paid',
        ]);
    }

    private function createCommerceTables(): void
    {
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

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('sale_number')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('idempotency_payload_hash', 64)->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_email_snapshot')->nullable();
            $table->string('customer_phone_snapshot')->nullable();
            $table->text('customer_address_snapshot')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 30)->default('unpaid');
            $table->string('source', 30)->default('online');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('pos_cash_session_id')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('customer_note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('void_reason')->nullable();
            $table->json('totals_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
        });

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

        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('affiliate_partner_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_listing_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_tenant_id')->nullable()->index();
            $table->unsignedBigInteger('affiliate_user_id')->nullable()->index();
            $table->unsignedBigInteger('source_product_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->index();
            $table->string('referral_code', 32);
            $table->string('landing_url')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('status', 30)->default('captured');
            $table->string('commission_type', 20)->nullable();
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->decimal('order_gross', 18, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('currency_code', 10)->default('IDR');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wallet_account_id')->index();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('entry_type', 50);
            $table->string('state', 30)->default('available');
            $table->string('direction', 10)->default('credit');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->string('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wallet_account_id')->index();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency_code', 10)->default('IDR');
            $table->string('status', 30)->default('requested');
            $table->json('destination_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
}
