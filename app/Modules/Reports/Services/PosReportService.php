<?php

namespace App\Modules\Reports\Services;

use App\Modules\PointOfSale\Models\PosCashSession;
use Illuminate\Support\Facades\DB;

class PosReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'cashier_user_id' => $this->normalizeInt($filters['cashier_user_id'] ?? null),
            'status' => $this->normalizeString($filters['status'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'shiftSummary' => $this->shiftSummary($filters),
            'cashDifference' => $this->cashDifference($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $query = $this->shiftBaseQuery($filters);

        return [
            'shift_count' => (clone $query)->count('pos_cash_sessions.id'),
            'sales_total' => round((float) (clone $query)->sum(DB::raw('COALESCE(sales_summary.sales_total, 0)')), 2),
            'payment_total' => round((float) (clone $query)->sum(DB::raw('COALESCE(payment_summary.payment_total, 0)')), 2),
            'difference_total' => round((float) (clone $query)->sum('pos_cash_sessions.difference_amount'), 2),
        ];
    }

    public function shiftSummary(array $filters)
    {
        return $this->shiftBaseQuery($filters)
            ->select(
                'pos_cash_sessions.code',
                'pos_cash_sessions.status',
                'pos_cash_sessions.opening_cash_amount',
                'pos_cash_sessions.expected_cash_amount',
                'pos_cash_sessions.closing_cash_amount',
                'pos_cash_sessions.difference_amount',
                'pos_cash_sessions.opened_at',
                'pos_cash_sessions.closed_at',
                'cashiers.name as cashier_name'
            )
            ->selectRaw('COALESCE(sales_summary.sales_count, 0) as sales_count')
            ->selectRaw('COALESCE(sales_summary.sales_total, 0) as sales_total')
            ->selectRaw('COALESCE(payment_summary.payment_total, 0) as payment_total')
            ->selectRaw('COALESCE(payment_summary.cash_total, 0) as cash_total')
            ->selectRaw('COALESCE(payment_summary.non_cash_total, 0) as non_cash_total')
            ->orderByDesc('pos_cash_sessions.opened_at')
            ->limit(20)
            ->get();
    }

    public function cashDifference(array $filters)
    {
        return $this->shiftBaseQuery($filters)
            ->where('pos_cash_sessions.status', PosCashSession::STATUS_CLOSED)
            ->select('pos_cash_sessions.code', 'pos_cash_sessions.difference_amount', 'cashiers.name as cashier_name', 'pos_cash_sessions.closed_at')
            ->orderByRaw('ABS(pos_cash_sessions.difference_amount) DESC')
            ->limit(15)
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function shiftBaseQuery(array $filters)
    {
        $salesSummary = DB::table('sales')
            ->selectRaw('pos_cash_session_id, COUNT(id) as sales_count, SUM(grand_total) as sales_total')
            ->whereNotNull('pos_cash_session_id')
            ->where('status', 'finalized')
            ->groupBy('pos_cash_session_id');

        $paymentSummary = DB::table('payments')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->selectRaw('payments.pos_cash_session_id')
            ->selectRaw('SUM(payments.amount) as payment_total')
            ->selectRaw("SUM(CASE WHEN payment_methods.type = 'cash' OR payment_methods.code = 'cash' THEN payments.amount ELSE 0 END) as cash_total")
            ->selectRaw("SUM(CASE WHEN payment_methods.type = 'cash' OR payment_methods.code = 'cash' THEN 0 ELSE payments.amount END) as non_cash_total")
            ->whereNotNull('payments.pos_cash_session_id')
            ->where('payments.status', 'posted')
            ->groupBy('payments.pos_cash_session_id');

        $query = DB::table('pos_cash_sessions')
            ->leftJoin('users as cashiers', 'cashiers.id', '=', 'pos_cash_sessions.cashier_user_id')
            ->leftJoinSub($salesSummary, 'sales_summary', function ($join) {
                $join->on('sales_summary.pos_cash_session_id', '=', 'pos_cash_sessions.id');
            })
            ->leftJoinSub($paymentSummary, 'payment_summary', function ($join) {
                $join->on('payment_summary.pos_cash_session_id', '=', 'pos_cash_sessions.id');
            });

        $this->applyTenantCompanyBranchScope($query, 'pos_cash_sessions');
        $this->applyDateRange($query, 'pos_cash_sessions.opened_at', $filters);

        return $query
            ->when(!empty($filters['cashier_user_id']), fn ($builder) => $builder->where('pos_cash_sessions.cashier_user_id', $filters['cashier_user_id']))
            ->when(!empty($filters['status']), fn ($builder) => $builder->where('pos_cash_sessions.status', $filters['status']));
    }
}
