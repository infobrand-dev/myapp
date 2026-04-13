<?php

namespace App\Modules\Reports\Services;

use App\Modules\Purchases\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'supplier' => $this->normalizeString($filters['supplier'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'supplierReport' => $this->supplierReport($filters),
            'receivedVsPending' => $this->receivedVsPending($filters),
            'payableAging' => $this->payableAging($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $query = $this->purchaseBaseQuery($filters);

        return [
            'purchase_count' => (clone $query)->count('purchases.id'),
            'grand_total' => round((float) (clone $query)->sum('purchases.grand_total'), 2),
            'received_qty_total' => round((float) (clone $query)->sum('purchases.received_total_qty'), 2),
            'balance_due_total' => round((float) (clone $query)->sum('purchases.balance_due'), 2),
        ];
    }

    public function supplierReport(array $filters)
    {
        return $this->purchaseBaseQuery($filters)
            ->selectRaw("COALESCE(NULLIF(purchases.supplier_name_snapshot, ''), 'Unknown Supplier') as supplier_name")
            ->selectRaw('COUNT(purchases.id) as purchase_count')
            ->selectRaw('SUM(purchases.grand_total) as grand_total')
            ->selectRaw('SUM(purchases.received_total_qty) as received_qty_total')
            ->selectRaw('SUM(purchases.balance_due) as balance_due_total')
            ->groupBy('purchases.supplier_name_snapshot')
            ->orderByDesc('grand_total')
            ->limit(15)
            ->get();
    }

    public function receivedVsPending(array $filters)
    {
        $remainingPerPurchase = DB::table('purchase_items')
            ->selectRaw('purchase_items.purchase_id')
            ->selectRaw('SUM(GREATEST(purchase_items.qty - purchase_items.qty_received, 0)) as remaining_qty')
            ->groupBy('purchase_items.purchase_id');

        $query = DB::table('purchases')
            ->leftJoinSub($remainingPerPurchase, 'remaining_summary', function ($join) {
                $join->on('remaining_summary.purchase_id', '=', 'purchases.id');
            })
            ->whereNotIn('purchases.status', [
                Purchase::STATUS_DRAFT,
                Purchase::STATUS_CANCELLED,
                Purchase::STATUS_VOIDED,
            ]);

        $this->applyTenantCompanyBranchScope($query, 'purchases');
        $this->applyDateRange($query, 'purchases.purchase_date', $filters);

        return $query
            ->when(!empty($filters['supplier']), fn ($builder) => $builder->where('purchases.supplier_name_snapshot', 'like', '%' . $filters['supplier'] . '%'))
            ->selectRaw("CASE WHEN purchases.status = 'received' THEN 'Received' ELSE 'Pending / Partial' END as receipt_bucket")
            ->selectRaw('COUNT(purchases.id) as purchase_count')
            ->selectRaw('SUM(purchases.grand_total) as grand_total')
            ->selectRaw('SUM(COALESCE(remaining_summary.remaining_qty, 0)) as remaining_qty')
            ->groupByRaw("CASE WHEN purchases.status = 'received' THEN 'Received' ELSE 'Pending / Partial' END")
            ->orderByDesc('grand_total')
            ->get();
    }

    public function payableAging(array $filters)
    {
        $today = now()->toDateString();
        $in7Days = now()->addDays(7)->toDateString();
        $in30Days = now()->addDays(30)->toDateString();

        return $this->purchaseBaseQuery($filters)
            ->where('purchases.balance_due', '>', 0)
            ->selectRaw("
                CASE
                    WHEN purchases.due_date IS NULL THEN 'No Due Date'
                    WHEN DATE(purchases.due_date) < ? THEN 'Overdue'
                    WHEN DATE(purchases.due_date) <= ? THEN 'Due <= 7 days'
                    WHEN DATE(purchases.due_date) <= ? THEN 'Due <= 30 days'
                    ELSE 'Due > 30 days'
                END as aging_bucket
            ", [$today, $in7Days, $in30Days])
            ->selectRaw('COUNT(purchases.id) as purchase_count')
            ->selectRaw('SUM(purchases.balance_due) as balance_due_total')
            ->groupByRaw("
                CASE
                    WHEN purchases.due_date IS NULL THEN 'No Due Date'
                    WHEN DATE(purchases.due_date) < ? THEN 'Overdue'
                    WHEN DATE(purchases.due_date) <= ? THEN 'Due <= 7 days'
                    WHEN DATE(purchases.due_date) <= ? THEN 'Due <= 30 days'
                    ELSE 'Due > 30 days'
                END
            ", [$today, $in7Days, $in30Days])
            ->orderByDesc('balance_due_total')
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function purchaseBaseQuery(array $filters)
    {
        $query = DB::table('purchases')->whereNotIn('purchases.status', [Purchase::STATUS_DRAFT, Purchase::STATUS_CANCELLED, Purchase::STATUS_VOIDED]);

        $this->applyTenantCompanyBranchScope($query, 'purchases');
        $this->applyDateRange($query, 'purchases.purchase_date', $filters);

        return $query
            ->when(!empty($filters['supplier']), fn ($builder) => $builder->where('purchases.supplier_name_snapshot', 'like', '%' . $filters['supplier'] . '%'));
    }
}
