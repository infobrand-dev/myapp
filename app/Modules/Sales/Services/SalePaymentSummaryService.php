<?php

namespace App\Modules\Sales\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\Sale;

class SalePaymentSummaryService
{
    public function summarize(Sale $sale, ?string $fallbackStatus = null): array
    {
        $paidTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', $sale->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $grandTotal = (float) $sale->grand_total;
        $balanceDue = max(0, round($grandTotal - $paidTotal, 2));

        if ($paidTotal <= 0) {
            $paymentStatus = $fallbackStatus ?: Sale::PAYMENT_UNPAID;
        } elseif ($paidTotal < $grandTotal) {
            $paymentStatus = Sale::PAYMENT_PARTIAL;
        } elseif ($paidTotal > $grandTotal) {
            $paymentStatus = Sale::PAYMENT_OVERPAID;
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
