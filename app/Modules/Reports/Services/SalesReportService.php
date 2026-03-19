<?php

namespace App\Modules\Reports\Services;

use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

class SalesReportService extends BaseReportService
{
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
            'byCustomer' => $this->byCustomer($filters),
            'byCashier' => $this->byCashier($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $baseQuery = $this->salesBaseQuery($filters);
        $transactionCount = (clone $baseQuery)->count('sales.id');
        $grossTotal = round((float) (clone $baseQuery)->sum('sales.grand_total'), 2);
        $paidTotal = round((float) (clone $baseQuery)->sum('sales.paid_total'), 2);

        $itemQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_FINALIZED);

        $this->applyDateRange($itemQuery, 'sales.transaction_date', $filters);
        $this->applyOutlet($itemQuery, $filters, 'sales.outlet_id');

        $itemQuery
            ->when(!empty($filters['source']), fn ($query) => $query->where('sales.source', $filters['source']))
            ->when(!empty($filters['cashier_user_id']), fn ($query) => $query->where('sales.created_by', $filters['cashier_user_id']))
            ->when(!empty($filters['customer']), fn ($query) => $query->where('sales.customer_name_snapshot', 'like', '%' . $filters['customer'] . '%'))
            ->when(!empty($filters['product']), function ($query) use ($filters) {
                $query->where(function ($nested) use ($filters) {
                    $nested
                        ->where('sale_items.product_name_snapshot', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('sale_items.variant_name_snapshot', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('sale_items.sku_snapshot', 'like', '%' . $filters['product'] . '%');
                });
            });

        $itemQty = round((float) (clone $itemQuery)->sum('sale_items.qty'), 2);

        return [
            'transaction_count' => $transactionCount,
            'gross_total' => $grossTotal,
            'paid_total' => $paidTotal,
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
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_FINALIZED);

        $this->applyDateRange($query, 'sales.transaction_date', $filters);
        $this->applyOutlet($query, $filters, 'sales.outlet_id');

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
            })
            ->selectRaw('sale_items.product_name_snapshot')
            ->selectRaw('sale_items.variant_name_snapshot')
            ->selectRaw('SUM(sale_items.qty) as qty_sold')
            ->selectRaw('SUM(sale_items.line_total) as gross_total')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as transaction_count')
            ->groupBy('sale_items.product_id', 'sale_items.product_variant_id', 'sale_items.product_name_snapshot', 'sale_items.variant_name_snapshot')
            ->orderByDesc('gross_total')
            ->limit(15)
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
            ->limit(15)
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
            ->limit(15)
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function salesBaseQuery(array $filters)
    {
        $query = DB::table('sales')->where('sales.status', Sale::STATUS_FINALIZED);

        $this->applyDateRange($query, 'sales.transaction_date', $filters);
        $this->applyOutlet($query, $filters, 'sales.outlet_id');

        return $query
            ->when(!empty($filters['source']), fn ($builder) => $builder->where('sales.source', $filters['source']))
            ->when(!empty($filters['customer']), fn ($builder) => $builder->where('sales.customer_name_snapshot', 'like', '%' . $filters['customer'] . '%'))
            ->when(!empty($filters['cashier_user_id']), fn ($builder) => $builder->where('sales.created_by', $filters['cashier_user_id']));
    }
}
