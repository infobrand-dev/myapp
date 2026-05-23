<?php

namespace Tests\Feature\Reports;

use App\Models\AccountingJournal;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Reports\ReportsServiceProvider;
use App\Modules\Reports\Services\FinanceReportService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceBalanceSheetAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ReportsServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_04_12_130000_create_accounting_governance_tables.php',
            '--realpath' => false,
        ])->run();
        $this->artisan('migrate', [
            '--path' => 'app/Modules/Finance/Database/Migrations/2026_04_21_090000_create_chart_of_accounts_table.php',
            '--realpath' => false,
        ])->run();

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('transaction_type', 50);
            $table->decimal('amount', 18, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->unsignedBigInteger('finance_category_id')->nullable();
            $table->string('transfer_group_key')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('status', 50)->default('draft');
            $table->dateTime('transaction_date');
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('qty', 18, 4)->default(0);
            $table->timestamps();
        });

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);

        ChartOfAccount::query()->insert([
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'code' => 'CASH',
                'name' => 'Cash',
                'account_type' => ChartOfAccount::TYPE_ASSET,
                'normal_balance' => ChartOfAccount::NORMAL_DEBIT,
                'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'code' => 'AP',
                'name' => 'Accounts Payable',
                'account_type' => ChartOfAccount::TYPE_LIABILITY,
                'normal_balance' => ChartOfAccount::NORMAL_CREDIT,
                'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'code' => 'INVENTORY',
                'name' => 'Inventory',
                'account_type' => ChartOfAccount::TYPE_ASSET,
                'normal_balance' => ChartOfAccount::NORMAL_DEBIT,
                'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 25,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'code' => 'CAPITAL',
                'name' => 'Owner Capital',
                'account_type' => ChartOfAccount::TYPE_EQUITY,
                'normal_balance' => ChartOfAccount::NORMAL_CREDIT,
                'report_section' => ChartOfAccount::SECTION_BALANCE_SHEET,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'code' => 'SALES',
                'name' => 'Sales Revenue',
                'account_type' => ChartOfAccount::TYPE_REVENUE,
                'normal_balance' => ChartOfAccount::NORMAL_CREDIT,
                'report_section' => ChartOfAccount::SECTION_PROFIT_LOSS,
                'is_postable' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
        ]);
    }

    public function test_balance_sheet_handles_asset_liability_equity_and_current_earnings_combination(): void
    {
        $opening = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 1,
            'journal_number' => 'JRNL-BS-001',
            'entry_date' => now()->startOfDay(),
            'status' => 'posted',
            'description' => 'Opening capital',
        ]);

        $opening->lines()->createMany([
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 1, 'account_code' => 'CASH', 'account_name' => 'Cash', 'debit' => 1000000, 'credit' => 0],
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 2, 'account_code' => 'CAPITAL', 'account_name' => 'Owner Capital', 'debit' => 0, 'credit' => 1000000],
        ]);

        $purchase = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'purchase_finalized',
            'source_type' => 'purchase-test',
            'source_id' => 2,
            'journal_number' => 'JRNL-BS-002',
            'entry_date' => now()->startOfDay()->addHour(),
            'status' => 'posted',
            'description' => 'Purchase on credit',
        ]);

        $purchase->lines()->createMany([
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 1, 'account_code' => 'INVENTORY', 'account_name' => 'Inventory', 'debit' => 250000, 'credit' => 0],
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 2, 'account_code' => 'AP', 'account_name' => 'Accounts Payable', 'debit' => 0, 'credit' => 250000],
        ]);

        $revenue = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'sale_finalized',
            'source_type' => 'sale-test',
            'source_id' => 3,
            'journal_number' => 'JRNL-BS-003',
            'entry_date' => now()->startOfDay()->addHours(2),
            'status' => 'posted',
            'description' => 'Revenue recognition',
        ]);

        $revenue->lines()->createMany([
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 1, 'account_code' => 'CASH', 'account_name' => 'Cash', 'debit' => 400000, 'credit' => 0],
            ['tenant_id' => 1, 'company_id' => 1, 'line_no' => 2, 'account_code' => 'SALES', 'account_name' => 'Sales Revenue', 'debit' => 0, 'credit' => 400000],
        ]);

        \Illuminate\Support\Facades\DB::table('sales')->insert([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'status' => 'finalized',
            'transaction_date' => now()->startOfDay()->addHours(2),
            'grand_total' => 400000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(FinanceReportService::class);
        $filters = $service->filters([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $balanceSheet = $service->balanceSheet($filters);

        $this->assertSame(1650000.0, (float) $balanceSheet['asset_total']);
        $this->assertSame(250000.0, (float) $balanceSheet['liability_total']);
        $this->assertSame(1400000.0, (float) $balanceSheet['equity_total']);
        $this->assertTrue($balanceSheet['is_balanced']);
        $this->assertArrayHasKey('Retained Earnings', $balanceSheet['equity']);
    }
}
