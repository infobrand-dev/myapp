<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\StoreFinanceTaxRateRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceTaxRateRequest;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\FinanceTaxRate;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceTaxRateController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'tax_type' => $request->input('tax_type'),
        ];

        return view('finance::taxes.index', [
            'taxRates' => FinanceTaxRate::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->when($filters['tax_type'], fn ($query) => $query->where('tax_type', $filters['tax_type']))
                ->orderBy('tax_type')
                ->orderBy('code')
                ->get(),
            'taxTypeOptions' => FinanceTaxRate::taxTypeOptions(),
            'chartOfAccountOptions' => $this->chartOfAccountOptions(),
            'summary' => $this->summary($filters),
            'filters' => $filters,
        ]);
    }

    public function edit(FinanceTaxRate $taxRate): View
    {
        return view('finance::taxes.edit', [
            'taxRate' => $taxRate,
            'taxTypeOptions' => FinanceTaxRate::taxTypeOptions(),
            'chartOfAccountOptions' => $this->chartOfAccountOptions(),
        ]);
    }

    public function store(StoreFinanceTaxRateRequest $request): RedirectResponse
    {
        FinanceTaxRate::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'tax_type' => $request->input('tax_type'),
            'rate_percent' => $request->input('rate_percent'),
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'sales_account_code' => $request->input('sales_account_code'),
            'purchase_account_code' => $request->input('purchase_account_code'),
            'description' => $request->input('description'),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('finance.taxes.index')->with('status', 'Master pajak ditambahkan.');
    }

    public function update(FinanceTaxRate $taxRate, UpdateFinanceTaxRateRequest $request): RedirectResponse
    {
        $taxRate->update([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'tax_type' => $request->input('tax_type'),
            'rate_percent' => $request->input('rate_percent'),
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'sales_account_code' => $request->input('sales_account_code'),
            'purchase_account_code' => $request->input('purchase_account_code'),
            'description' => $request->input('description'),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('finance.taxes.index')->with('status', 'Master pajak diperbarui.');
    }

    public function destroy(FinanceTaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('finance.taxes.index')->with('status', 'Master pajak dihapus.');
    }

    private function chartOfAccountOptions()
    {
        return ChartOfAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('is_postable', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name']);
    }

    private function summary(array $filters): array
    {
        $salesQuery = DB::table('sales')
            ->where('status', 'finalized')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());

        $purchaseQuery = DB::table('purchases')
            ->whereNotNull('confirmed_at')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());

        $journalQuery = DB::table('accounting_journal_lines')
            ->join('accounting_journals', 'accounting_journals.id', '=', 'accounting_journal_lines.journal_id')
            ->where('accounting_journals.tenant_id', TenantContext::currentId())
            ->where('accounting_journals.company_id', CompanyContext::currentId())
            ->where('accounting_journals.status', 'posted')
            ->whereIn('accounting_journal_lines.account_code', ['SALES_TAX', 'PURCHASE_TAX']);

        if (BranchContext::currentId() !== null) {
            $salesQuery->where('branch_id', BranchContext::currentId());
            $purchaseQuery->where('branch_id', BranchContext::currentId());
            $journalQuery->where('accounting_journals.branch_id', BranchContext::currentId());
        }

        if (!empty($filters['date_from'])) {
            $salesQuery->whereDate('transaction_date', '>=', $filters['date_from']);
            $purchaseQuery->whereDate('purchase_date', '>=', $filters['date_from']);
            $journalQuery->whereDate('accounting_journals.entry_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $salesQuery->whereDate('transaction_date', '<=', $filters['date_to']);
            $purchaseQuery->whereDate('purchase_date', '<=', $filters['date_to']);
            $journalQuery->whereDate('accounting_journals.entry_date', '<=', $filters['date_to']);
        }

        $journalBalances = $journalQuery
            ->selectRaw('accounting_journal_lines.account_code')
            ->selectRaw('SUM(accounting_journal_lines.debit) as debit_total')
            ->selectRaw('SUM(accounting_journal_lines.credit) as credit_total')
            ->groupBy('accounting_journal_lines.account_code')
            ->get()
            ->keyBy('account_code');

        $salesTaxTotal = round((float) $salesQuery->sum('tax_total'), 2);
        $purchaseTaxTotal = round((float) $purchaseQuery->sum('tax_total'), 2);

        return [
            'sales_tax_total' => $salesTaxTotal,
            'purchase_tax_total' => $purchaseTaxTotal,
            'net_vat_payable' => round($salesTaxTotal - $purchaseTaxTotal, 2),
            'sales_tax_journal_credit' => round((float) data_get($journalBalances->get('SALES_TAX'), 'credit_total', 0), 2),
            'purchase_tax_journal_debit' => round((float) data_get($journalBalances->get('PURCHASE_TAX'), 'debit_total', 0), 2),
        ];
    }
}
