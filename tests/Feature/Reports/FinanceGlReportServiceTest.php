<?php

namespace Tests\Feature\Reports;

use App\Models\AccountingJournal;
use App\Modules\Reports\ReportsServiceProvider;
use App\Modules\Reports\Services\FinanceReportService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceGlReportServiceTest extends TestCase
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

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
    }

    public function test_trial_balance_and_general_ledger_are_built_from_posted_journal_lines(): void
    {
        $journal = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 1,
            'journal_number' => 'JRNL-MANUAL-001',
            'entry_date' => now()->startOfDay()->addHour(),
            'status' => 'posted',
            'description' => 'Initial posted manual journal',
            'meta' => ['manual' => true],
        ]);

        $journal->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 1,
                'account_code' => 'CASH',
                'account_name' => 'Cash',
                'debit' => 100000,
                'credit' => 0,
                'meta' => ['notes' => 'Opening cash'],
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 2,
                'account_code' => 'EQUITY',
                'account_name' => 'Owner Equity',
                'debit' => 0,
                'credit' => 100000,
                'meta' => ['notes' => 'Opening capital'],
            ],
        ]);

        AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 2,
            'journal_number' => 'JRNL-MANUAL-002',
            'entry_date' => now()->startOfDay()->addHours(2),
            'status' => 'draft',
            'description' => 'Draft journal should be ignored',
            'meta' => ['manual' => true],
        ])->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 1,
                'account_code' => 'CASH',
                'account_name' => 'Cash',
                'debit' => 50000,
                'credit' => 0,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 2,
                'account_code' => 'SALES',
                'account_name' => 'Sales Revenue',
                'debit' => 0,
                'credit' => 50000,
            ],
        ]);

        $service = app(FinanceReportService::class);
        $filters = $service->filters([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $trialBalance = $service->trialBalance($filters);
        $generalLedger = $service->generalLedger($filters);

        $this->assertCount(2, $trialBalance);
        $this->assertSame(100000.0, (float) $trialBalance->firstWhere('account_code', 'CASH')->debit_total);
        $this->assertSame(100000.0, (float) $trialBalance->firstWhere('account_code', 'EQUITY')->credit_total);

        $this->assertTrue($generalLedger->has('CASH'));
        $this->assertCount(1, $generalLedger->get('CASH'));
        $this->assertSame('Opening cash', data_get($generalLedger->get('CASH')->first(), 'line_meta.notes'));
    }
}
