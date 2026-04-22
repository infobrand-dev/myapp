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
use Illuminate\Support\Collection;
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
            'taxScopeOptions' => FinanceTaxRate::taxScopeOptions(),
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
            'taxScopeOptions' => FinanceTaxRate::taxScopeOptions(),
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
            'tax_scope' => $request->input('tax_scope') ?: FinanceTaxRate::SCOPE_GENERAL,
            'jurisdiction_code' => strtoupper((string) ($request->input('jurisdiction_code') ?: 'ID')),
            'legal_basis' => $request->input('legal_basis'),
            'document_label' => $request->input('document_label'),
            'requires_tax_number' => $request->boolean('requires_tax_number'),
            'requires_counterparty_tax_id' => $request->boolean('requires_counterparty_tax_id'),
            'rate_percent' => $request->input('rate_percent'),
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'sales_account_code' => $request->input('sales_account_code'),
            'purchase_account_code' => $request->input('purchase_account_code'),
            'withholding_account_code' => $request->input('withholding_account_code'),
            'description' => $request->input('description'),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return redirect()->route('finance.taxes.index')->with('status', 'Master pajak ditambahkan.');
    }

    public function update(FinanceTaxRate $taxRate, UpdateFinanceTaxRateRequest $request): RedirectResponse
    {
        $taxRate->update([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'tax_type' => $request->input('tax_type'),
            'tax_scope' => $request->input('tax_scope') ?: FinanceTaxRate::SCOPE_GENERAL,
            'jurisdiction_code' => strtoupper((string) ($request->input('jurisdiction_code') ?: 'ID')),
            'legal_basis' => $request->input('legal_basis'),
            'document_label' => $request->input('document_label'),
            'requires_tax_number' => $request->boolean('requires_tax_number'),
            'requires_counterparty_tax_id' => $request->boolean('requires_counterparty_tax_id'),
            'rate_percent' => $request->input('rate_percent'),
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'sales_account_code' => $request->input('sales_account_code'),
            'purchase_account_code' => $request->input('purchase_account_code'),
            'withholding_account_code' => $request->input('withholding_account_code'),
            'description' => $request->input('description'),
            'updated_by' => optional($request->user())->id,
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
        $taxBreakdown = $this->taxBreakdown($filters);

        return [
            'sales_tax_total' => $salesTaxTotal,
            'purchase_tax_total' => $purchaseTaxTotal,
            'net_vat_payable' => round($salesTaxTotal - $purchaseTaxTotal, 2),
            'sales_tax_journal_credit' => round((float) data_get($journalBalances->get('SALES_TAX'), 'credit_total', 0), 2),
            'purchase_tax_journal_debit' => round((float) data_get($journalBalances->get('PURCHASE_TAX'), 'debit_total', 0), 2),
            'by_tax_code' => $taxBreakdown,
        ];
    }

    private function taxBreakdown(array $filters): Collection
    {
        $rows = collect();

        if (($filters['tax_type'] ?? null) !== FinanceTaxRate::TYPE_PURCHASE) {
            $salesRows = \App\Modules\Sales\Models\Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('status', 'finalized')
                ->when(BranchContext::currentId() !== null, fn ($query) => $query->where('branch_id', BranchContext::currentId()))
                ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('transaction_date', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('transaction_date', '<=', $filters['date_to']))
                ->where('tax_total', '>', 0)
                ->get(['id', 'sale_number', 'tax_total', 'meta']);

            $rows = $rows->merge($salesRows->map(fn ($sale) => $this->normalizeTaxBreakdownRow(
                'sales',
                (float) $sale->tax_total,
                $sale->meta ?? [],
                $sale->sale_number
            )));
        }

        if (($filters['tax_type'] ?? null) !== FinanceTaxRate::TYPE_SALES) {
            $purchaseRows = \App\Modules\Purchases\Models\Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->whereNotNull('confirmed_at')
                ->when(BranchContext::currentId() !== null, fn ($query) => $query->where('branch_id', BranchContext::currentId()))
                ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('purchase_date', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('purchase_date', '<=', $filters['date_to']))
                ->where('tax_total', '>', 0)
                ->get(['id', 'purchase_number', 'tax_total', 'meta']);

            $rows = $rows->merge($purchaseRows->map(fn ($purchase) => $this->normalizeTaxBreakdownRow(
                'purchase',
                (float) $purchase->tax_total,
                $purchase->meta ?? [],
                $purchase->purchase_number
            )));
        }

        return $rows
            ->groupBy('code')
            ->map(function (Collection $group, string $code) {
                $first = $group->first();

                return [
                    'code' => $code,
                    'name' => $first['name'],
                    'rate_percent' => $first['rate_percent'],
                    'sales_tax_total' => round((float) $group->sum('sales_tax_total'), 2),
                    'purchase_tax_total' => round((float) $group->sum('purchase_tax_total'), 2),
                    'net_tax_total' => round((float) $group->sum('sales_tax_total') - (float) $group->sum('purchase_tax_total'), 2),
                    'transaction_count' => $group->count(),
                    'source_documents' => $group->pluck('document_number')->filter()->take(3)->values()->all(),
                    'is_unmapped' => (bool) $first['is_unmapped'],
                ];
            })
            ->sortBy('code')
            ->values();
    }

    private function normalizeTaxBreakdownRow(string $direction, float $taxTotal, array $meta, ?string $documentNumber = null): array
    {
        $snapshot = data_get($meta, 'tax.tax_snapshot');
        $snapshot = is_array($snapshot) ? $snapshot : (is_array(data_get($meta, 'tax')) ? data_get($meta, 'tax') : []);

        $code = trim((string) ($snapshot['code'] ?? ''));
        $name = trim((string) ($snapshot['name'] ?? ''));

        if ($code === '') {
            $code = 'UNMAPPED';
            $name = $name !== '' ? $name : 'Manual / Legacy Tax';
        }

        return [
            'code' => $code,
            'name' => $name !== '' ? $name : $code,
            'rate_percent' => isset($snapshot['rate_percent']) ? round((float) $snapshot['rate_percent'], 4) : null,
            'sales_tax_total' => $direction === 'sales' ? round($taxTotal, 2) : 0.0,
            'purchase_tax_total' => $direction === 'purchase' ? round($taxTotal, 2) : 0.0,
            'document_number' => $documentNumber,
            'is_unmapped' => $code === 'UNMAPPED',
        ];
    }
}
