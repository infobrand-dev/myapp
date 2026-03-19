<?php

namespace App\Modules\Reports\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'payment_method_id' => $this->normalizeInt($filters['payment_method_id'] ?? null),
            'source' => $this->normalizeString($filters['source'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'byMethod' => $this->byMethod($filters),
            'cashVsNonCash' => $this->cashVsNonCash($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $query = $this->paymentsBaseQuery($filters);
        $paymentCount = (clone $query)->count('payments.id');
        $totalAmount = round((float) (clone $query)->sum('payments.amount'), 2);

        $cashAmount = round((float) (clone $query)
            ->where(function ($builder) {
                $builder
                    ->where('payment_methods.type', PaymentMethod::TYPE_CASH)
                    ->orWhere('payment_methods.code', PaymentMethod::CODE_CASH);
            })
            ->sum('payments.amount'), 2);

        return [
            'payment_count' => $paymentCount,
            'total_amount' => $totalAmount,
            'cash_amount' => $cashAmount,
            'non_cash_amount' => round($totalAmount - $cashAmount, 2),
            'average_payment' => $paymentCount > 0 ? round($totalAmount / $paymentCount, 2) : 0,
        ];
    }

    public function byMethod(array $filters)
    {
        return $this->paymentsBaseQuery($filters)
            ->selectRaw("COALESCE(payment_methods.name, 'Unknown Method') as method_name")
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('SUM(payments.amount) as total_amount')
            ->groupBy('payments.payment_method_id', 'payment_methods.name')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function cashVsNonCash(array $filters)
    {
        return $this->paymentsBaseQuery($filters)
            ->selectRaw("CASE WHEN payment_methods.type = 'cash' OR payment_methods.code = 'cash' THEN 'Cash' ELSE 'Non-cash' END as payment_bucket")
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('SUM(payments.amount) as total_amount')
            ->groupByRaw("CASE WHEN payment_methods.type = 'cash' OR payment_methods.code = 'cash' THEN 'Cash' ELSE 'Non-cash' END")
            ->orderByDesc('total_amount')
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function paymentsBaseQuery(array $filters)
    {
        $query = DB::table('payments')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('payments.status', Payment::STATUS_POSTED);

        $this->applyDateRange($query, 'payments.paid_at', $filters);
        $this->applyOutlet($query, $filters, 'payments.outlet_id');

        return $query
            ->when(!empty($filters['payment_method_id']), fn ($builder) => $builder->where('payments.payment_method_id', $filters['payment_method_id']))
            ->when(!empty($filters['source']), fn ($builder) => $builder->where('payments.source', $filters['source']));
    }
}
