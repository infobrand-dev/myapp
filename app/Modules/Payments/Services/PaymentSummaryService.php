<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class PaymentSummaryService
{
    public function summarize(Model $payable): array
    {
        if ($payable instanceof Sale) {
            return $this->summarizeSale($payable);
        }

        return [
            'paid_total' => 0,
            'balance_due' => 0,
            'payment_status' => null,
        ];
    }

    private function summarizeSale(Sale $sale): array
    {
        $paidTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', $sale->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $grandTotal = round((float) $sale->grand_total, 2);
        $paidTotal = round($paidTotal, 2);
        $balanceDue = max(0, round($grandTotal - $paidTotal, 2));

        if ($paidTotal <= 0) {
            $paymentStatus = Sale::PAYMENT_UNPAID;
        } elseif ($paidTotal < $grandTotal) {
            $paymentStatus = Sale::PAYMENT_PARTIAL;
        } elseif ($paidTotal > $grandTotal) {
            $paymentStatus = Sale::PAYMENT_OVERPAID;
        } else {
            $paymentStatus = Sale::PAYMENT_PAID;
        }

        return [
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
        ];
    }
}
