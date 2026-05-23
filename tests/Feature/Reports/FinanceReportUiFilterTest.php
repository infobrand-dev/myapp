<?php

namespace Tests\Feature\Reports;

use App\Models\AccountingJournal;
use App\Models\Company;
use App\Models\User;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Reports\ReportsServiceProvider;
use App\Support\AccountingUiMode;
use App\Support\CompanyContext;
use App\Support\MoneyFormatter;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class FinanceReportUiFilterTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerModuleProviders([
            ReportsServiceProvider::class,
        ]);

        $this->createFinanceTables();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_04_12_130000_create_accounting_governance_tables.php',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Finance Report Filter',
            'slug' => 'pt-finance-report-filter',
            'code' => 'FRF',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['tenant_id' => 1]);

        foreach (['reports.view', 'reports.finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['reports.view', 'reports.finance']);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId($this->company->id);
    }

    private function createFinanceTables(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('transaction_type', 20);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('transaction_number', 50);
            $table->string('transaction_type', 20);
            $table->dateTime('transaction_date');
            $table->decimal('amount', 18, 2);
            $table->unsignedBigInteger('finance_account_id')->nullable();
            $table->unsignedBigInteger('finance_category_id');
            $table->unsignedBigInteger('counterparty_finance_account_id')->nullable();
            $table->string('transfer_group_key')->nullable();
            $table->unsignedBigInteger('transfer_pair_transaction_id')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('pos_cash_session_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('status', 30)->default('draft');
            $table->dateTime('transaction_date');
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->decimal('cost_price', 18, 2)->nullable();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->decimal('cost_price', 18, 2)->nullable();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->decimal('qty', 18, 2)->default(0);
        });
    }

    public function test_finance_report_page_applies_ui_filters_end_to_end(): void
    {
        $targetCategory = FinanceCategory::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Office Expense Filter',
            'slug' => 'office-expense-filter',
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
            'is_active' => true,
        ]);

        $hiddenCategory = FinanceCategory::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Marketing Expense Hidden',
            'slug' => 'marketing-expense-hidden',
            'transaction_type' => FinanceCategory::TYPE_EXPENSE,
            'is_active' => true,
        ]);

        FinanceTransaction::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'transaction_number' => 'FT-KEEP-001',
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'transaction_date' => '2026-05-10 10:00:00',
            'amount' => 25000,
            'finance_category_id' => $targetCategory->id,
            'notes' => 'Visible transaction',
        ]);

        FinanceTransaction::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'transaction_number' => 'FT-HIDE-001',
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'transaction_date' => '2026-05-10 11:00:00',
            'amount' => 77777,
            'finance_category_id' => $hiddenCategory->id,
            'notes' => 'Hidden by category filter',
        ]);

        FinanceTransaction::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'transaction_number' => 'FT-HIDE-002',
            'transaction_type' => FinanceTransaction::TYPE_CASH_IN,
            'transaction_date' => '2026-05-10 12:00:00',
            'amount' => 120000,
            'finance_category_id' => $targetCategory->id,
            'notes' => 'Hidden by type filter',
        ]);

        FinanceTransaction::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'transaction_number' => 'FT-HIDE-003',
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'transaction_date' => '2026-05-12 09:00:00',
            'amount' => 88888,
            'finance_category_id' => $targetCategory->id,
            'notes' => 'Hidden by date filter',
        ]);

        $visibleJournal = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 101,
            'journal_number' => 'JRNL-FILTER-001',
            'entry_date' => '2026-05-10 13:00:00',
            'status' => 'posted',
            'description' => 'Ledger cash target visible',
            'meta' => ['manual' => true],
        ]);

        $visibleJournal->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 1,
                'account_code' => 'CASH_FILTER',
                'account_name' => 'Cash Filter Account',
                'debit' => 25000,
                'credit' => 0,
            ],
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 2,
                'account_code' => 'EQUITY_FILTER',
                'account_name' => 'Equity Filter Account',
                'debit' => 0,
                'credit' => 25000,
            ],
        ]);

        $hiddenJournalSameDate = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 102,
            'journal_number' => 'JRNL-FILTER-002',
            'entry_date' => '2026-05-10 14:00:00',
            'status' => 'posted',
            'description' => 'Ledger receivable hidden',
            'meta' => ['manual' => true],
        ]);

        $hiddenJournalSameDate->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 1,
                'account_code' => 'AR_HIDDEN',
                'account_name' => 'Receivable Hidden Account',
                'debit' => 9999,
                'credit' => 0,
            ],
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 2,
                'account_code' => 'SALES_HIDDEN',
                'account_name' => 'Sales Hidden Account',
                'debit' => 0,
                'credit' => 9999,
            ],
        ]);

        $hiddenJournalOutsideDate = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => $this->company->id,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 103,
            'journal_number' => 'JRNL-FILTER-003',
            'entry_date' => '2026-05-12 08:00:00',
            'status' => 'posted',
            'description' => 'Ledger outside date hidden',
            'meta' => ['manual' => true],
        ]);

        $hiddenJournalOutsideDate->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 1,
                'account_code' => 'CASH_FILTER',
                'account_name' => 'Cash Filter Account',
                'debit' => 5000,
                'credit' => 0,
            ],
            [
                'tenant_id' => 1,
                'company_id' => $this->company->id,
                'line_no' => 2,
                'account_code' => 'EQUITY_FILTER',
                'account_name' => 'Equity Filter Account',
                'debit' => 0,
                'credit' => 5000,
            ],
        ]);

        $filters = [
            'date_from' => '2026-05-10',
            'date_to' => '2026-05-10',
            'finance_category_id' => $targetCategory->id,
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'account_code' => 'CASH_FILTER',
        ];

        $response = $this->actingAs($this->user)
            ->withSession([
                'company_id' => $this->company->id,
                'company_slug' => $this->company->slug,
                AccountingUiMode::SESSION_KEY => AccountingUiMode::ADVANCED,
            ])
            ->get(route('reports.finance', $filters));

        $money = app(MoneyFormatter::class);

        $response->assertOk();
        $response->assertSeeText('Office Expense Filter');
        $response->assertSeeText($money->format(25000, 'IDR'));
        $response->assertDontSeeText($money->format(77777, 'IDR'));
        $response->assertDontSeeText($money->format(88888, 'IDR'));
        $response->assertSee('value="CASH_FILTER" selected', false);
    }
}
