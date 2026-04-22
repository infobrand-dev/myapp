<?php

namespace App\Modules\Reports\Services;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Inventory\Models\StockMovement;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'finance_category_id' => $this->normalizeInt($filters['finance_category_id'] ?? null),
            'transaction_type' => $this->normalizeString($filters['transaction_type'] ?? null),
            'account_code' => $this->normalizeString($filters['account_code'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'cashFlowSummary' => $this->cashFlowSummary($filters),
            'cashInOut' => $this->cashInOut($filters),
            'expenseByCategory' => $this->expenseByCategory($filters),
            'profitLoss' => $this->profitLoss($filters),
            'trialBalance' => $this->trialBalance($filters),
            'generalLedger' => $this->generalLedger($filters),
            'ledgerSummary' => $this->ledgerSummary($filters),
            'accountOptions' => $this->accountOptions($filters),
            'balanceSheet' => $this->balanceSheet($filters),
            'inventoryGlReconciliation' => $this->inventoryGlReconciliation($filters),
            'inventoryGlReconciliationDetails' => $this->inventoryGlReconciliationDetails($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $query = $this->financeBaseQuery($filters);

        $cashIn = round((float) (clone $query)
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_CASH_IN)
            ->sum('finance_transactions.amount'), 2);

        $cashOut = round((float) (clone $query)
            ->whereIn('finance_transactions.transaction_type', [FinanceTransaction::TYPE_CASH_OUT, FinanceTransaction::TYPE_EXPENSE])
            ->sum('finance_transactions.amount'), 2);

        return [
            'cash_in_total' => $cashIn,
            'cash_out_total' => $cashOut,
            'net_total' => round($cashIn - $cashOut, 2),
        ];
    }

    public function cashInOut(array $filters)
    {
        return $this->financeBaseQuery($filters)
            ->selectRaw('DATE(finance_transactions.transaction_date) as report_date')
            ->selectRaw("SUM(CASE WHEN finance_transactions.transaction_type = 'cash_in' THEN finance_transactions.amount ELSE 0 END) as cash_in_total")
            ->selectRaw("SUM(CASE WHEN finance_transactions.transaction_type IN ('cash_out', 'expense') THEN finance_transactions.amount ELSE 0 END) as cash_out_total")
            ->groupByRaw('DATE(finance_transactions.transaction_date)')
            ->orderBy('report_date')
            ->get();
    }

    public function cashFlowSummary(array $filters): array
    {
        $query = $this->financeBaseQuery($filters);

        $cashIn = round((float) (clone $query)
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_CASH_IN)
            ->sum('finance_transactions.amount'), 2);
        $cashOut = round((float) (clone $query)
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_CASH_OUT)
            ->sum('finance_transactions.amount'), 2);
        $expenses = round((float) (clone $query)
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_EXPENSE)
            ->sum('finance_transactions.amount'), 2);

        return [
            'operating_inflow' => $cashIn,
            'operating_outflow' => $cashOut,
            'expense_outflow' => $expenses,
            'net_cash_flow' => round($cashIn - $cashOut - $expenses, 2),
        ];
    }

    public function expenseByCategory(array $filters)
    {
        return $this->financeBaseQuery($filters)
            ->leftJoin('finance_categories', 'finance_categories.id', '=', 'finance_transactions.finance_category_id')
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_EXPENSE)
            ->selectRaw("COALESCE(finance_categories.name, 'Tanpa Kategori') as category_name")
            ->selectRaw('COUNT(finance_transactions.id) as transaction_count')
            ->selectRaw('SUM(finance_transactions.amount) as total_amount')
            ->groupBy('finance_transactions.finance_category_id', 'finance_categories.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function profitLoss(array $filters): array
    {
        $salesQuery = DB::table('sales')->where('status', 'finalized');
        $this->applyTenantCompanyBranchScope($salesQuery, 'sales');
        $this->applyDateRange($salesQuery, 'sales.transaction_date', $filters);

        $saleItemsQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->where('sales.status', 'finalized');
        $this->applyTenantCompanyBranchScope($saleItemsQuery, 'sales');
        $this->applyDateRange($saleItemsQuery, 'sales.transaction_date', $filters);

        $expenseQuery = $this->financeBaseQuery($filters)
            ->where('finance_transactions.transaction_type', FinanceTransaction::TYPE_EXPENSE);
        $actualCogs = $this->journalLineBaseQuery($filters)
            ->where('accounting_journal_lines.account_code', 'COGS')
            ->sum('accounting_journal_lines.debit');

        $revenue = round((float) (clone $salesQuery)->sum('sales.grand_total'), 2);
        $estimatedCogs = round((float) (clone $saleItemsQuery)
            ->selectRaw('SUM(COALESCE(product_variants.cost_price, products.cost_price, 0) * sale_items.qty) as estimated_cogs')
            ->value('estimated_cogs'), 2);
        $actualCogs = round((float) $actualCogs, 2);
        $recognizedCogs = $actualCogs > 0 ? $actualCogs : $estimatedCogs;
        $operatingExpenses = round((float) (clone $expenseQuery)->sum('finance_transactions.amount'), 2);
        $grossProfit = round($revenue - $recognizedCogs, 2);

        return [
            'revenue' => $revenue,
            'cogs' => $recognizedCogs,
            'cogs_basis' => $actualCogs > 0 ? 'actual_gl' : 'estimated_snapshot',
            'actual_cogs' => $actualCogs,
            'estimated_cogs' => $estimatedCogs,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $operatingExpenses,
            'net_profit' => round($grossProfit - $operatingExpenses, 2),
        ];
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    public function trialBalance(array $filters)
    {
        return $this->journalLineBaseQuery($filters)
            ->selectRaw('accounting_journal_lines.account_code')
            ->selectRaw('MAX(accounting_journal_lines.account_name) as account_name')
            ->selectRaw('SUM(accounting_journal_lines.debit) as debit_total')
            ->selectRaw('SUM(accounting_journal_lines.credit) as credit_total')
            ->groupBy('accounting_journal_lines.account_code')
            ->orderBy('accounting_journal_lines.account_code')
            ->get();
    }

    public function ledgerSummary(array $filters)
    {
        return $this->journalLineBaseQuery($filters)
            ->selectRaw('accounting_journal_lines.account_code')
            ->selectRaw('MAX(accounting_journal_lines.account_name) as account_name')
            ->selectRaw('COUNT(accounting_journal_lines.id) as line_count')
            ->selectRaw('SUM(accounting_journal_lines.debit) as debit_total')
            ->selectRaw('SUM(accounting_journal_lines.credit) as credit_total')
            ->groupBy('accounting_journal_lines.account_code')
            ->orderBy('accounting_journal_lines.account_code')
            ->get();
    }

    public function generalLedger(array $filters)
    {
        return $this->journalLineBaseQuery($filters)
            ->when(!empty($filters['account_code']), fn ($query) => $query->where('accounting_journal_lines.account_code', $filters['account_code']))
            ->select([
                'accounting_journals.id',
                'accounting_journal_lines.account_code',
                'accounting_journal_lines.account_name',
                'accounting_journal_lines.debit',
                'accounting_journal_lines.credit',
                'accounting_journal_lines.meta as line_meta',
                'accounting_journals.journal_number',
                'accounting_journals.entry_type',
                'accounting_journals.entry_date',
                'accounting_journals.description',
                'accounting_journals.status',
                'accounting_journals.source_type',
                'accounting_journals.source_id',
            ])
            ->orderBy('accounting_journal_lines.account_code')
            ->orderBy('accounting_journals.entry_date')
            ->orderBy('accounting_journal_lines.line_no')
            ->get()
            ->map(function ($row) {
                $row->line_meta = is_string($row->line_meta) ? (json_decode($row->line_meta, true) ?: null) : $row->line_meta;

                return $row;
            })
            ->groupBy('account_code');
    }

    public function accountOptions(array $filters)
    {
        return $this->journalLineBaseQuery($filters)
            ->selectRaw('accounting_journal_lines.account_code')
            ->selectRaw('MAX(accounting_journal_lines.account_name) as account_name')
            ->groupBy('accounting_journal_lines.account_code')
            ->orderBy('accounting_journal_lines.account_code')
            ->get();
    }

    public function balanceSheet(array $filters): array
    {
        $coaMap = $this->chartOfAccountsByCode();
        $rows = $this->trialBalance($filters)
            ->map(function ($row) use ($coaMap) {
                $classification = $this->classifyBalanceSheetAccount(
                    (string) $row->account_code,
                    (string) $row->account_name,
                    $coaMap[strtoupper((string) $row->account_code)] ?? null
                );

                $debit = round((float) $row->debit_total, 2);
                $credit = round((float) $row->credit_total, 2);
                if ($classification['section'] === 'asset') {
                    $balance = round($debit - $credit, 2);
                } elseif (in_array($classification['section'], ['liability', 'equity'], true)) {
                    $balance = round($credit - $debit, 2);
                } else {
                    $balance = 0.0;
                }

                return [
                    'account_code' => (string) $row->account_code,
                    'account_name' => (string) $row->account_name,
                    'section' => $classification['section'],
                    'group' => $classification['group'],
                    'balance' => $balance,
                ];
            })
            ->filter(fn (array $row) => $row['section'] !== null && round($row['balance'], 2) !== 0.0)
            ->values();

        $retainedEarningsBalance = $this->retainedEarningsBalance($filters, $rows, $coaMap);
        if (round($retainedEarningsBalance, 2) !== 0.0) {
            $rows->push([
                'account_code' => 'CURRENT_EARNINGS',
                'account_name' => 'Current Earnings',
                'section' => 'equity',
                'group' => 'Retained Earnings',
                'balance' => round($retainedEarningsBalance, 2),
            ]);
        }

        $assets = $rows->where('section', 'asset')->groupBy('group');
        $liabilities = $rows->where('section', 'liability')->groupBy('group');
        $equity = $rows->where('section', 'equity')->groupBy('group');

        $assetTotal = round((float) $rows->where('section', 'asset')->sum('balance'), 2);
        $liabilityTotal = round((float) $rows->where('section', 'liability')->sum('balance'), 2);
        $equityTotal = round((float) $rows->where('section', 'equity')->sum('balance'), 2);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'asset_total' => $assetTotal,
            'liability_total' => $liabilityTotal,
            'equity_total' => $equityTotal,
            'liability_and_equity_total' => round($liabilityTotal + $equityTotal, 2),
            'is_balanced' => round($assetTotal, 2) === round($liabilityTotal + $equityTotal, 2),
            'basis' => empty($coaMap)
                ? 'Provisional account classification based on journal account_code/account_name until formal COA is configured. Current earnings juga dibawa ke equity sementara.'
                : 'Balance sheet classification uses Chart of Accounts metadata and parent grouping when available, with fallback heuristic for unmapped journal accounts. Current earnings juga dibawa ke equity sementara.',
        ];
    }

    public function inventoryGlReconciliation(array $filters): array
    {
        if (!Schema::hasTable('inventory_stocks')) {
            return [
                'inventory_stock_value' => 0.0,
                'inventory_gl_balance' => 0.0,
                'difference' => 0.0,
                'difference_status' => 'unavailable',
                'basis' => 'Inventory module belum tersedia.',
            ];
        }

        $inventoryStockQuery = DB::table('inventory_stocks');
        $this->applyTenantCompanyBranchScope($inventoryStockQuery, 'inventory_stocks');

        $inventoryStockValue = round((float) $inventoryStockQuery->sum('inventory_stocks.inventory_value'), 2);

        $inventoryTrialBalanceRow = $this->journalLineBaseQuery($filters)
            ->where('accounting_journal_lines.account_code', 'INVENTORY')
            ->selectRaw('SUM(accounting_journal_lines.debit) as debit_total')
            ->selectRaw('SUM(accounting_journal_lines.credit) as credit_total')
            ->first();

        $inventoryGlBalance = round(
            (float) data_get($inventoryTrialBalanceRow, 'debit_total', 0)
            - (float) data_get($inventoryTrialBalanceRow, 'credit_total', 0),
            2
        );
        $difference = round($inventoryStockValue - $inventoryGlBalance, 2);

        return [
            'inventory_stock_value' => $inventoryStockValue,
            'inventory_gl_balance' => $inventoryGlBalance,
            'difference' => $difference,
            'difference_status' => abs($difference) < 0.01 ? 'balanced' : 'gap',
            'basis' => 'Membandingkan inventory valuation terkini pada stock balance dengan saldo akun INVENTORY dari posted journal sesuai filter periode/report aktif.',
        ];
    }

    public function inventoryGlReconciliationDetails(array $filters)
    {
        if (!Schema::hasTable('inventory_stock_movements')) {
            return collect();
        }

        $movementRows = $this->inventoryMovementReconciliationBaseQuery($filters)
            ->selectRaw('inventory_stock_movements.reference_type as source_type')
            ->selectRaw('inventory_stock_movements.reference_id as source_id')
            ->selectRaw('COUNT(inventory_stock_movements.id) as movement_count')
            ->selectRaw('MAX(inventory_stock_movements.occurred_at) as last_occurred_at')
            ->selectRaw("SUM(CASE WHEN inventory_stock_movements.direction = 'in' THEN inventory_stock_movements.movement_value ELSE -inventory_stock_movements.movement_value END) as inventory_effect")
            ->selectRaw('SUM(inventory_stock_movements.movement_value) as inventory_movement_total')
            ->groupBy('inventory_stock_movements.reference_type', 'inventory_stock_movements.reference_id')
            ->orderByRaw('ABS(SUM(CASE WHEN inventory_stock_movements.direction = \'in\' THEN inventory_stock_movements.movement_value ELSE -inventory_stock_movements.movement_value END)) DESC')
            ->limit(25)
            ->get();

        if ($movementRows->isEmpty()) {
            return collect();
        }

        $journalImpactRows = $this->inventoryJournalImpactBySource($filters)
            ->keyBy(function ($row) {
                return (string) $row->source_type . ':' . (int) $row->source_id;
            });

        return $movementRows->map(function ($row) use ($journalImpactRows) {
            $key = (string) $row->source_type . ':' . (int) $row->source_id;
            $journalImpact = $journalImpactRows->get($key);
            $inventoryEffect = round((float) $row->inventory_effect, 2);
            $glInventoryEffect = round((float) data_get($journalImpact, 'gl_inventory_effect', 0), 2);
            $difference = round($inventoryEffect - $glInventoryEffect, 2);

            if (abs($difference) < 0.01) {
                $status = 'balanced';
            } elseif (abs($glInventoryEffect) < 0.01) {
                $status = 'missing_gl';
            } else {
                $status = 'gap';
            }

            return [
                'source_type' => (string) $row->source_type,
                'source_id' => (int) $row->source_id,
                'movement_count' => (int) $row->movement_count,
                'inventory_effect' => $inventoryEffect,
                'inventory_movement_total' => round((float) $row->inventory_movement_total, 2),
                'gl_inventory_effect' => $glInventoryEffect,
                'difference' => $difference,
                'status' => $status,
                'last_occurred_at' => $row->last_occurred_at,
            ];
        });
    }

    private function chartOfAccountsByCode(): array
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            return [];
        }

        $companyId = CompanyContext::currentId();

        if (!$companyId) {
            return [];
        }

        return ChartOfAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', $companyId)
            ->with('parent')
            ->get()
            ->keyBy(fn (ChartOfAccount $account) => strtoupper((string) $account->code))
            ->all();
    }

    private function financeBaseQuery(array $filters)
    {
        $query = DB::table('finance_transactions');

        $this->applyTenantCompanyBranchScope($query, 'finance_transactions');
        $this->applyDateRange($query, 'finance_transactions.transaction_date', $filters);

        return $query
            ->whereNull('finance_transactions.transfer_group_key')
            ->when(!empty($filters['finance_category_id']), fn ($builder) => $builder->where('finance_transactions.finance_category_id', $filters['finance_category_id']))
            ->when(!empty($filters['transaction_type']), fn ($builder) => $builder->where('finance_transactions.transaction_type', $filters['transaction_type']));
    }

    private function journalLineBaseQuery(array $filters)
    {
        $query = DB::table('accounting_journal_lines')
            ->join('accounting_journals', 'accounting_journals.id', '=', 'accounting_journal_lines.journal_id');

        $this->applyTenantCompanyBranchScope($query, 'accounting_journals');
        $this->applyDateRange($query, 'accounting_journals.entry_date', $filters);

        return $query
            ->where('accounting_journals.status', 'posted');
    }

    private function inventoryMovementReconciliationBaseQuery(array $filters)
    {
        $query = DB::table('inventory_stock_movements');

        $this->applyTenantCompanyBranchScope($query, 'inventory_stock_movements');
        $this->applyDateRange($query, 'inventory_stock_movements.occurred_at', $filters);

        return $query
            ->whereNotNull('inventory_stock_movements.reference_type')
            ->whereNotNull('inventory_stock_movements.reference_id')
            ->where('inventory_stock_movements.movement_value', '!=', 0)
            ->whereIn('inventory_stock_movements.direction', ['in', 'out'])
            ->whereIn('inventory_stock_movements.movement_type', [
                'opening_stock',
                'purchase_receipt',
                'sale_finalized',
                'sale_return',
                'sale_void_restore',
                'stock_adjustment',
            ]);
    }

    private function inventoryJournalImpactBySource(array $filters)
    {
        return $this->journalLineBaseQuery($filters)
            ->where('accounting_journal_lines.account_code', 'INVENTORY')
            ->selectRaw('accounting_journals.source_type')
            ->selectRaw('accounting_journals.source_id')
            ->selectRaw('SUM(accounting_journal_lines.debit - accounting_journal_lines.credit) as gl_inventory_effect')
            ->groupBy('accounting_journals.source_type', 'accounting_journals.source_id')
            ->get();
    }

    private function classifyBalanceSheetAccount(string $accountCode, string $accountName, ?ChartOfAccount $chartOfAccount = null): array
    {
        $code = strtoupper(trim($accountCode));
        $name = strtoupper(trim($accountName));

        if ($chartOfAccount && $chartOfAccount->report_section === ChartOfAccount::SECTION_BALANCE_SHEET) {
            if ($chartOfAccount->account_type === ChartOfAccount::TYPE_ASSET) {
                return [
                    'section' => 'asset',
                    'group' => $this->accountGroup($chartOfAccount, 'asset', $code, $name),
                ];
            }

            if ($chartOfAccount->account_type === ChartOfAccount::TYPE_LIABILITY) {
                return [
                    'section' => 'liability',
                    'group' => $this->accountGroup($chartOfAccount, 'liability', $code, $name),
                ];
            }

            if ($chartOfAccount->account_type === ChartOfAccount::TYPE_EQUITY) {
                return [
                    'section' => 'equity',
                    'group' => $this->accountGroup($chartOfAccount, 'equity', $code, $name),
                ];
            }

            return [
                'section' => null,
                'group' => null,
            ];
        }

        if (in_array($code, ['CASH', 'BANK', 'AR', 'INVENTORY', 'PREPAID', 'FIXED_ASSET'], true)
            || str_starts_with($code, 'ASSET')
            || str_starts_with($code, 'INV')
            || str_contains($name, 'CASH')
            || str_contains($name, 'BANK')
            || str_contains($name, 'RECEIVABLE')
            || str_contains($name, 'INVENTORY')
            || str_contains($name, 'PREPAID')
            || str_contains($name, 'ASSET')) {
            return [
                'section' => 'asset',
                'group' => $this->assetGroup($code, $name),
            ];
        }

        if (in_array($code, ['AP', 'SALES_TAX', 'PURCHASE_TAX', 'TAX_PAYABLE'], true)
            || str_starts_with($code, 'LIAB')
            || str_contains($name, 'PAYABLE')
            || str_contains($name, 'TAX PAYABLE')
            || str_contains($name, 'LIABILITY')) {
            return [
                'section' => 'liability',
                'group' => $this->liabilityGroup($code, $name),
            ];
        }

        if (in_array($code, ['EQUITY', 'RETAINED_EARNINGS'], true)
            || str_starts_with($code, 'EQ')
            || str_contains($name, 'EQUITY')
            || str_contains($name, 'CAPITAL')
            || str_contains($name, 'RETAINED')) {
            return [
                'section' => 'equity',
                'group' => $this->equityGroup($code, $name),
            ];
        }

        return [
            'section' => null,
            'group' => null,
        ];
    }

    private function assetGroup(string $code, string $name): string
    {
        if ($code === 'CASH' || $code === 'BANK' || str_contains($name, 'CASH') || str_contains($name, 'BANK')) {
            return 'Cash & Bank';
        }

        if ($code === 'AR' || str_contains($name, 'RECEIVABLE')) {
            return 'Receivables';
        }

        if ($code === 'INVENTORY' || str_contains($name, 'INVENTORY')) {
            return 'Inventory';
        }

        if ($code === 'PREPAID' || str_contains($name, 'PREPAID')) {
            return 'Prepaid Expenses';
        }

        if ($code === 'FIXED_ASSET' || str_contains($name, 'FIXED') || str_contains($name, 'ASSET')) {
            return 'Fixed Assets';
        }

        return 'Other Assets';
    }

    private function liabilityGroup(string $code, string $name): string
    {
        if ($code === 'AP' || str_contains($name, 'PAYABLE')) {
            return 'Payables';
        }

        if (str_contains($name, 'TAX') || str_contains($code, 'TAX')) {
            return 'Tax Payables';
        }

        return 'Other Liabilities';
    }

    private function equityGroup(string $code, string $name): string
    {
        if (str_contains($name, 'RETAINED')) {
            return 'Retained Earnings';
        }

        if (str_contains($name, 'CAPITAL') || str_contains($name, 'EQUITY') || $code === 'EQUITY') {
            return 'Owner Equity';
        }

        return 'Other Equity';
    }

    private function retainedEarningsBalance(array $filters, $balanceSheetRows, array $coaMap): float
    {
        $profitLoss = $this->profitLoss($filters);
        $netProfit = round((float) ($profitLoss['net_profit'] ?? 0), 2);

        if ($netProfit === 0.0) {
            return 0.0;
        }

        $hasRetainedEarningsAccount = $balanceSheetRows->contains(function (array $row) {
            $code = strtoupper((string) $row['account_code']);
            $name = strtoupper((string) $row['account_name']);

            return $code === 'RETAINED_EARNINGS' || str_contains($name, 'RETAINED');
        });

        if ($hasRetainedEarningsAccount) {
            return 0.0;
        }

        foreach ($coaMap as $account) {
            if (!$account instanceof ChartOfAccount) {
                continue;
            }

            if ($account->report_section !== ChartOfAccount::SECTION_BALANCE_SHEET) {
                continue;
            }

            if ($account->account_type !== ChartOfAccount::TYPE_EQUITY) {
                continue;
            }

            $name = strtoupper((string) $account->name);
            $code = strtoupper((string) $account->code);

            if (str_contains($name, 'RETAINED') || $code === 'RETAINED_EARNINGS') {
                return 0.0;
            }
        }

        return $netProfit;
    }

    private function accountGroup(?ChartOfAccount $chartOfAccount, string $section, string $code, string $name): string
    {
        if ($chartOfAccount && $chartOfAccount->relationLoaded('parent') && $chartOfAccount->parent) {
            return (string) $chartOfAccount->parent->name;
        }

        if ($section === 'asset') {
            return $this->assetGroup($code, $name);
        }

        if ($section === 'liability') {
            return $this->liabilityGroup($code, $name);
        }

        return $this->equityGroup($code, $name);
    }
}
