<?php

namespace App\Modules\Purchases\Actions;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Models\PurchasePayableAdjustment;

class SyncPurchasePaymentSummaryAction
{
    public function execute(Purchase $purchase, ?string $fallbackStatus = null): Purchase
    {
        $previousPaidTotal = round((float) $purchase->paid_total, 2);
        $previousBalanceDue = round((float) $purchase->balance_due, 2);
        $previousPaymentStatus = (string) $purchase->payment_status;

        $postedAllocations = PaymentAllocation::query()
            ->where('payable_type', $purchase->getMorphClass())
            ->where('payable_id', $purchase->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->with(['payment' => fn ($query) => $query->select('id', 'paid_at', 'status')])
            ->get();

        $paidTotal = round((float) $postedAllocations->sum('amount'), 2);

        $adjustmentTotal = round((float) PurchasePayableAdjustment::query()
            ->where('purchase_id', $purchase->getKey())
            ->where('tenant_id', $purchase->tenant_id)
            ->where('company_id', $purchase->company_id)
            ->where('status', 'posted')
            ->sum('amount'), 2);

        $grandTotal = round((float) $purchase->grand_total, 2);
        $balanceDue = max(0, round($grandTotal - $paidTotal - $adjustmentTotal, 2));

        if ($paidTotal > $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_OVERPAID;
        } elseif ($balanceDue <= 0.0) {
            $paymentStatus = Purchase::PAYMENT_PAID;
        } elseif ($paidTotal <= 0 && $adjustmentTotal <= 0) {
            $paymentStatus = $fallbackStatus ?: Purchase::PAYMENT_UNPAID;
        } elseif (($paidTotal + $adjustmentTotal) < $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_PARTIAL;
        } else {
            $paymentStatus = Purchase::PAYMENT_PAID;
        }

        $purchase->update([
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
        ]);

        if (
            $previousPaymentStatus !== $paymentStatus
            || $previousPaidTotal !== $paidTotal
            || $previousBalanceDue !== $balanceDue
        ) {
            $lastPayment = $postedAllocations
                ->map(fn ($allocation) => $allocation->payment)
                ->filter()
                ->sortByDesc(fn ($payment) => optional($payment->paid_at)?->timestamp ?? 0)
                ->first();

            $purchase->statusHistories()->create([
                'tenant_id' => $purchase->tenant_id,
                'company_id' => $purchase->company_id,
                'branch_id' => $purchase->branch_id,
                'from_status' => $purchase->status,
                'to_status' => $purchase->status,
                'event' => 'payment_settlement_updated',
                'reason' => null,
                'actor_id' => null,
                'meta' => [
                    'previous_payment_status' => $previousPaymentStatus,
                    'current_payment_status' => $paymentStatus,
                    'previous_paid_total' => $previousPaidTotal,
                    'current_paid_total' => $paidTotal,
                    'previous_balance_due' => $previousBalanceDue,
                    'current_balance_due' => $balanceDue,
                    'adjustment_total' => $adjustmentTotal,
                    'payment_count' => $postedAllocations->pluck('payment_id')->unique()->count(),
                    'last_payment_at' => optional($lastPayment?->paid_at)->toDateTimeString(),
                ],
            ]);
        }

        return $purchase->refresh();
    }
}
