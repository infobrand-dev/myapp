<?php

namespace App\Modules\Reports\Services;

use App\Modules\Finance\Models\FinanceTransaction;
use Illuminate\Support\Facades\DB;

class FinanceReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'finance_category_id' => $this->normalizeInt($filters['finance_category_id'] ?? null),
            'transaction_type' => $this->normalizeString($filters['transaction_type'] ?? null),
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

        $revenue = round((float) (clone $salesQuery)->sum('sales.grand_total'), 2);
        $estimatedCogs = round((float) (clone $saleItemsQuery)
            ->selectRaw('SUM(COALESCE(product_variants.cost_price, products.cost_price, 0) * sale_items.qty) as estimated_cogs')
            ->value('estimated_cogs'), 2);
        $operatingExpenses = round((float) (clone $expenseQuery)->sum('finance_transactions.amount'), 2);
        $grossProfit = round($revenue - $estimatedCogs, 2);

        return [
            'revenue' => $revenue,
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
}
