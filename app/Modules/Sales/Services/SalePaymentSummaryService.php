<?php

namespace App\Modules\Sales\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReceivableAdjustment;

class SalePaymentSummaryService
{
    public function summarize(Sale $sale, ?string $fallbackStatus = null): array
    {
        $paidTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', $sale->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $adjustmentTotal = (float) SaleReceivableAdjustment::query()
            ->where('sale_id', $sale->getKey())
            ->where('tenant_id', $sale->tenant_id)
            ->where('company_id', $sale->company_id)
            ->where('status', 'posted')
            ->sum('amount');

        $grandTotal = (float) $sale->grand_total;
        $balanceDue = max(0, round($grandTotal - $paidTotal - $adjustmentTotal, 2));

        if ($paidTotal > $grandTotal) {
            $paymentStatus = Sale::PAYMENT_OVERPAID;
        } elseif ($balanceDue <= 0.0) {
            $paymentStatus = Sale::PAYMENT_PAID;
        } elseif ($paidTotal <= 0 && $adjustmentTotal <= 0) {
            $paymentStatus = $fallbackStatus ?: Sale::PAYMENT_UNPAID;
        } elseif (($paidTotal + $adjustmentTotal) < $grandTotal) {
            $paymentStatus = Sale::PAYMENT_PARTIAL;
        } else {
            $paymentStatus = Sale::PAYMENT_PAID;
        }

        return [
            'paid_total' => round($paidTotal, 2),
            'balance_due' => round($balanceDue, 2),
            'payment_status' => $paymentStatus,
        ];
    }
}
