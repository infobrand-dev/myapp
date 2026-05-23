<?php

namespace Tests\Feature\Core;

use App\Models\AccountingJournal;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Finance\Services\ChartOfAccountProvisioner;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Products\Models\Product;
use App\Modules\Purchases\Actions\CreateDraftPurchaseAction;
use App\Modules\Purchases\Actions\FinalizePurchaseAction;
use App\Modules\Purchases\Actions\ReceivePurchaseGoodsAction;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\PurchasesServiceProvider;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\SalesServiceProvider;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class AccountingAutoPostingRegressionTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerModuleProviders([
            FinanceServiceProvider::class,
            PaymentsServiceProvider::class,
            SalesServiceProvider::class,
            PurchasesServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'app/Modules/Contacts/Database/Migrations',
            'app/Modules/Inventory/Database/Migrations',
            'app/Modules/Sales/Database/Migrations',
            'app/Modules/Purchases/Database/Migrations',
        ]);

        foreach ([
            'app/Modules/Products/Database/Migrations/2026_03_17_000000_create_product_categories_table.php',
            'app/Modules/Products/Database/Migrations/2026_03_17_000001_create_product_brands_table.php',
            'app/Modules/Products/Database/Migrations/2026_03_17_000002_create_product_units_table.php',
            'app/Modules/Products/Database/Migrations/2026_03_17_000100_create_products_table.php',
            'app/Modules/Products/Database/Migrations/2026_03_17_000200_create_product_variants_table.php',
        ] as $path) {
            $this->artisan('migrate', [
                '--path' => $path,
                '--realpath' => false,
            ])->run();
        }

        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->string('account_type', 20);
            $table->string('account_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Payments/Database/Migrations',
            '--realpath' => false,
        ])->run();
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_04_12_130000_create_accounting_governance_tables.php',
            '--realpath' => false,
        ])->run();
        $this->artisan('migrate', [
            '--path' => 'app/Modules/Finance/Database/Migrations/2026_04_21_090000_create_chart_of_accounts_table.php',
            '--realpath' => false,
        ])->run();

        Company::query()->firstOrCreate(
            [
                'tenant_id' => 1,
                'slug' => 'default-company',
            ],
            [
                'id' => 1,
                'name' => 'Default Company',
                'code' => 'DEF',
                'is_active' => true,
            ]
        );

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
        app(ChartOfAccountProvisioner::class)->ensureDefaults(1, 1, null);
    }

    public function test_sales_and_customer_payment_auto_post_expected_journals(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $customer = $this->contact('Customer Regression');
        $product = $this->product('Produk Sales Audit', 'SALE-AUDIT-001', 10000, 15000);
        $location = $this->location('SALE-WH');

        app(StockMutationService::class)->record([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'movement_type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 10,
            'unit_cost' => 10000,
        ]);

        $sale = app(CreateDraftSaleAction::class)->execute([
            'contact_id' => $customer->id,
            'inventory_location_id' => $location->id,
            'source' => 'manual',
            'payment_status' => 'unpaid',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'unit_price' => 15000,
                'discount_total' => 0,
                'tax_total' => 0,
            ]],
        ], $user);

        $sale = app(FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => 'unpaid',
        ], $user);

        $saleJournal = $this->journalFor('sale_finalized', Sale::class, $sale->id);
        $cogsJournal = $this->journalFor('sale_cogs', Sale::class, $sale->id);

        $this->assertSame(30000.0, (float) $saleJournal->lines->firstWhere('account_code', 'AR')->debit);
        $this->assertSame(30000.0, (float) $saleJournal->lines->firstWhere('account_code', 'SALES')->credit);
        $this->assertSame(20000.0, (float) $cogsJournal->lines->firstWhere('account_code', 'COGS')->debit);
        $this->assertSame(20000.0, (float) $cogsJournal->lines->firstWhere('account_code', 'INVENTORY')->credit);

        $payment = app(CreatePaymentAction::class)->execute([
            'payment_method_id' => PaymentMethod::query()->where('code', PaymentMethod::CODE_CASH)->value('id'),
            'amount' => 30000,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => 30000,
            ]],
        ], $user);

        $paymentJournal = $this->journalFor('payment_posted', get_class($payment), $payment->id);

        $this->assertSame(30000.0, (float) $paymentJournal->lines->firstWhere('account_code', 'CASH')->debit);
        $this->assertSame(30000.0, (float) $paymentJournal->lines->firstWhere('account_code', 'AR')->credit);
        $this->assertSame('paid', $sale->fresh()->payment_status);
        $this->assertSame(0.0, (float) $sale->fresh()->balance_due);
    }

    public function test_purchase_receipt_and_supplier_payment_auto_post_expected_journals(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $supplier = $this->contact('Supplier Regression');
        $product = $this->product('Produk Purchase Audit', 'PUR-AUDIT-001', 12000, 18000);
        $location = $this->location('PUR-WH');

        $purchase = app(CreateDraftPurchaseAction::class)->execute([
            'contact_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d H:i:s'),
            'currency_code' => 'IDR',
            'items' => [[
                'product_id' => $product->id,
                'qty' => 5,
                'unit_cost' => 12000,
                'discount_total' => 0,
                'tax_total' => 0,
            ]],
        ], $user);

        $purchase = app(FinalizePurchaseAction::class)->execute($purchase, [
            'purchase_date' => now()->format('Y-m-d H:i:s'),
        ], $user);

        $purchaseJournal = $this->journalFor('purchase_finalized', Purchase::class, $purchase->id);

        $this->assertSame(60000.0, (float) $purchaseJournal->lines->firstWhere('account_code', 'PURCHASES')->debit);
        $this->assertSame(60000.0, (float) $purchaseJournal->lines->firstWhere('account_code', 'AP')->credit);

        $purchase = app(ReceivePurchaseGoodsAction::class)->execute($purchase, [
            'inventory_location_id' => $location->id,
            'receipt_date' => now()->format('Y-m-d H:i:s'),
            'items' => [[
                'purchase_item_id' => $purchase->items->first()->id,
                'qty_received' => 5,
            ]],
        ], $user);

        $receipt = $purchase->receipts()->latest('id')->firstOrFail();
        $receiptJournal = $this->journalFor('purchase_receipt_inventory', get_class($receipt), $receipt->id);

        $this->assertSame(60000.0, (float) $receiptJournal->lines->firstWhere('account_code', 'INVENTORY')->debit);
        $this->assertSame(60000.0, (float) $receiptJournal->lines->firstWhere('account_code', 'PURCHASES')->credit);

        $payment = app(CreatePaymentAction::class)->execute([
            'payment_method_id' => PaymentMethod::query()->where('code', PaymentMethod::CODE_CASH)->value('id'),
            'amount' => 60000,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'allocations' => [[
                'payable_type' => 'purchase',
                'payable_id' => $purchase->id,
                'amount' => 60000,
            ]],
        ], $user);

        $paymentJournal = $this->journalFor('payment_posted', get_class($payment), $payment->id);

        $this->assertSame(60000.0, (float) $paymentJournal->lines->firstWhere('account_code', 'AP')->debit);
        $this->assertSame(60000.0, (float) $paymentJournal->lines->firstWhere('account_code', 'CASH')->credit);
        $this->assertSame('paid', $purchase->fresh()->payment_status);
        $this->assertSame(0.0, (float) $purchase->fresh()->balance_due);
    }

    private function contact(string $name): Contact
    {
        return Contact::query()->create([
            'tenant_id' => 1,
            'type' => 'company',
            'name' => $name,
            'mobile' => '628123456700',
            'is_active' => true,
        ]);
    }

    private function product(string $name, string $sku, float $costPrice, float $sellPrice): Product
    {
        return Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => $name,
            'slug' => strtolower($sku),
            'sku' => $sku,
            'cost_price' => $costPrice,
            'sell_price' => $sellPrice,
            'is_active' => true,
            'track_stock' => true,
        ]);
    }

    private function location(string $code): InventoryLocation
    {
        return InventoryLocation::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'code' => $code,
            'name' => $code,
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function journalFor(string $entryType, string $sourceType, int $sourceId): AccountingJournal
    {
        return AccountingJournal::query()
            ->where('entry_type', $entryType)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->with('lines')
            ->firstOrFail();
    }
}
