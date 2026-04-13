<?php

namespace App\Modules\Reports\Services;

use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

class SalesReportService extends BaseReportService
{
    private const MAX_BREAKDOWN_ROWS = 15;

    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'source' => $this->normalizeString($filters['source'] ?? null),
            'customer' => $this->normalizeString($filters['customer'] ?? null),
            'cashier_user_id' => $this->normalizeInt($filters['cashier_user_id'] ?? null),
            'product' => $this->normalizeString($filters['product'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'byDate' => $this->byDate($filters),
            'byProduct' => $this->byProduct($filters),
            'marginByProduct' => $this->marginByProduct($filters),
            'byCustomer' => $this->byCustomer($filters),
            'byCashier' => $this->byCashier($filters),
            'receivableAging' => $this->receivableAging($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $baseQuery = $this->salesBaseQuery($filters);
        $transactionCount = (clone $baseQuery)->count('sales.id');
        $grossTotal = round((float) (clone $baseQuery)->sum('sales.grand_total'), 2);
        $paidTotal = round((float) (clone $baseQuery)->sum('sales.paid_total'), 2);
        $receivableTotal = round((float) (clone $baseQuery)->sum('sales.balance_due'), 2);
        $itemQty = round((float) $this->saleItemsBaseQuery($filters)->sum('sale_items.qty'), 2);

        return [
            'transaction_count' => $transactionCount,
            'gross_total' => $grossTotal,
            'paid_total' => $paidTotal,
            'receivable_total' => $receivableTotal,
            'item_qty' => $itemQty,
            'average_ticket' => $transactionCount > 0 ? round($grossTotal / $transactionCount, 2) : 0,
        ];
    }

    public function byDate(array $filters)
    {
        return $this->salesBaseQuery($filters)
            ->selectRaw('DATE(sales.transaction_date) as report_date')
            ->selectRaw('COUNT(sales.id) as transaction_count')
            ->selectRaw('SUM(sales.grand_total) as gross_total')
            ->selectRaw('SUM(sales.paid_total) as paid_total')
            ->groupByRaw('DATE(sales.transaction_date)')
            ->orderBy('report_date')
            ->get();
    }

    public function byProduct(array $filters)
    {
        return $this->saleItemsBaseQuery($filters)
            ->selectRaw('sale_items.product_name_snapshot')
            ->selectRaw('sale_items.variant_name_snapshot')
            ->selectRaw('SUM(sale_items.qty) as qty_sold')
            ->selectRaw('SUM(sale_items.line_total) as gross_total')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as transaction_count')
            ->groupBy('sale_items.product_id', 'sale_items.product_variant_id', 'sale_items.product_name_snapshot', 'sale_items.variant_name_snapshot')
            ->orderByDesc('gross_total')
            ->limit(self::MAX_BREAKDOWN_ROWS)
            ->get();
    }

    public function byCustomer(array $filters)
    {
        return $this->salesBaseQuery($filters)
            ->selectRaw("COALESCE(NULLIF(sales.customer_name_snapshot, ''), 'Walk-in / Umum') as customer_name")
            ->selectRaw('COUNT(sales.id) as transaction_count')
            ->selectRaw('SUM(sales.grand_total) as gross_total')
            ->selectRaw('SUM(sales.paid_total) as paid_total')
            ->groupBy('sales.customer_name_snapshot')
            ->orderByDesc('gross_total')
            ->limit(self::MAX_BREAKDOWN_ROWS)
            ->get();
    }

    public function marginByProduct(array $filters)
    {
        return $this->saleItemsBaseQuery($filters)
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->selectRaw('sale_items.product_name_snapshot')
            ->selectRaw('sale_items.variant_name_snapshot')
            ->selectRaw('SUM(sale_items.qty) as qty_sold')
            ->selectRaw('SUM(sale_items.line_total) as revenue_total')
            ->selectRaw('SUM(COALESCE(product_variants.cost_price, products.cost_price, 0) * sale_items.qty) as estimated_cost_total')
            ->selectRaw('SUM(sale_items.line_total - (COALESCE(product_variants.cost_price, products.cost_price, 0) * sale_items.qty)) as estimated_margin_total')
            ->groupBy('sale_items.product_id', 'sale_items.product_variant_id', 'sale_items.product_name_snapshot', 'sale_items.variant_name_snapshot')
            ->orderByDesc('estimated_margin_total')
            ->limit(self::MAX_BREAKDOWN_ROWS)
            ->get();
    }

    public function receivableAging(array $filters)
    {
        $today = now()->toDateString();
        $in7Days = now()->addDays(7)->toDateString();
        $in30Days = now()->addDays(30)->toDateString();

        return $this->salesBaseQuery($filters)
            ->where('sales.balance_due', '>', 0)
            ->selectRaw("
                CASE
                    WHEN sales.due_date IS NULL THEN 'No Due Date'
                    WHEN DATE(sales.due_date) < ? THEN 'Overdue'
                    WHEN DATE(sales.due_date) <= ? THEN 'Due <= 7 days'
                    WHEN DATE(sales.due_date) <= ? THEN 'Due <= 30 days'
                    ELSE 'Due > 30 days'
                END as aging_bucket
            ", [$today, $in7Days, $in30Days])
            ->selectRaw('COUNT(sales.id) as transaction_count')
            ->selectRaw('SUM(sales.balance_due) as balance_due_total')
            ->groupByRaw("
                CASE
                    WHEN sales.due_date IS NULL THEN 'No Due Date'
                    WHEN DATE(sales.due_date) < ? THEN 'Overdue'
                    WHEN DATE(sales.due_date) <= ? THEN 'Due <= 7 days'
                    WHEN DATE(sales.due_date) <= ? THEN 'Due <= 30 days'
                    ELSE 'Due > 30 days'
                END
            ", [$today, $in7Days, $in30Days])
            ->orderByDesc('balance_due_total')
            ->get();
    }

    public function byCashier(array $filters)
    {
        return $this->salesBaseQuery($filters)
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'sales.created_by')
            ->selectRaw("COALESCE(cashiers.name, 'Unknown Cashier') as cashier_name")
            ->selectRaw('COUNT(sales.id) as transaction_count')
            ->selectRaw('SUM(sales.grand_total) as gross_total')
            ->selectRaw('SUM(sales.paid_total) as paid_total')
            ->groupBy('sales.created_by', 'cashiers.name')
            ->orderByDesc('gross_total')
            ->limit(self::MAX_BREAKDOWN_ROWS)
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function salesBaseQuery(array $filters)
    {
        $query = DB::table('sales')->where('sales.status', Sale::STATUS_FINALIZED);

        $this->applyTenantCompanyBranchScope($query, 'sales');
        $this->applyDateRange($query, 'sales.transaction_date', $filters);

        return $query
            ->when(!empty($filters['source']), fn ($builder) => $builder->where('sales.source', $filters['source']))
            ->when(!empty($filters['customer']), fn ($builder) => $builder->where('sales.customer_name_snapshot', 'like', '%' . $filters['customer'] . '%'))
            ->when(!empty($filters['cashier_user_id']), fn ($builder) => $builder->where('sales.created_by', $filters['cashier_user_id']));
    }

    private function saleItemsBaseQuery(array $filters)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_FINALIZED);

        $this->applyTenantCompanyBranchScope($query, 'sales');
        $this->applyDateRange($query, 'sales.transaction_date', $filters);

        return $query
            ->when(!empty($filters['source']), fn ($builder) => $builder->where('sales.source', $filters['source']))
            ->when(!empty($filters['cashier_user_id']), fn ($builder) => $builder->where('sales.created_by', $filters['cashier_user_id']))
            ->when(!empty($filters['customer']), fn ($builder) => $builder->where('sales.customer_name_snapshot', 'like', '%' . $filters['customer'] . '%'))
            ->when(!empty($filters['product']), function ($builder) use ($filters) {
                $builder->where(function ($nested) use ($filters) {
                    $nested
                        ->where('sale_items.product_name_snapshot', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('sale_items.variant_name_snapshot', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('sale_items.sku_snapshot', 'like', '%' . $filters['product'] . '%');
                });
            });
    }
}
