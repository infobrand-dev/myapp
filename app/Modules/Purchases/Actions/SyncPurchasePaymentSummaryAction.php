<?php

namespace App\Modules\Purchases\Actions;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchases\Models\Purchase;

class SyncPurchasePaymentSummaryAction
{
    public function execute(Purchase $purchase, ?string $fallbackStatus = null): Purchase
    {
        $paidTotal = round((float) PaymentAllocation::query()
            ->where('payable_type', $purchase->getMorphClass())
            ->where('payable_id', $purchase->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount'), 2);

        $grandTotal = round((float) $purchase->grand_total, 2);
        $balanceDue = max(0, round($grandTotal - $paidTotal, 2));

        if ($paidTotal <= 0) {
            $paymentStatus = $fallbackStatus ?: Purchase::PAYMENT_UNPAID;
        } elseif ($paidTotal < $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_PARTIAL;
        } elseif ($paidTotal > $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_OVERPAID;
        } else {
            $paymentStatus = Purchase::PAYMENT_PAID;
        }

        $purchase->update([
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
        ]);

        return $purchase->refresh();
    }
}
