<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Database\Eloquent\Model;

class PaymentSummaryService
{
    public function summarize(Model $payable): array
    {
        if ($payable instanceof Sale) {
            return $this->summarizeSale($payable);
        }

        if ($payable instanceof SaleReturn) {
            return $this->summarizeSaleReturn($payable);
        }

        if ($payable instanceof Purchase) {
            return $this->summarizePurchase($payable);
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

    private function summarizeSaleReturn(SaleReturn $saleReturn): array
    {
        $refundedTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $saleReturn->getMorphClass())
            ->where('payable_id', $saleReturn->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $grandTotal = round((float) $saleReturn->grand_total, 2);
        $refundedTotal = round($refundedTotal, 2);
        $refundBalance = max(0, round($grandTotal - $refundedTotal, 2));

        if (!$saleReturn->refund_required) {
            $refundStatus = SaleReturn::REFUND_NOT_REQUIRED;
        } elseif ($refundedTotal <= 0) {
            $refundStatus = SaleReturn::REFUND_PENDING;
        } elseif ($refundedTotal < $grandTotal) {
            $refundStatus = SaleReturn::REFUND_PARTIAL;
        } else {
            $refundStatus = SaleReturn::REFUND_REFUNDED;
        }

        return [
            'paid_total' => $refundedTotal,
            'balance_due' => $refundBalance,
            'payment_status' => $refundStatus,
        ];
    }

    private function summarizePurchase(Purchase $purchase): array
    {
        $paidTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $purchase->getMorphClass())
            ->where('payable_id', $purchase->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $grandTotal = round((float) $purchase->grand_total, 2);
        $paidTotal = round($paidTotal, 2);
        $balanceDue = max(0, round($grandTotal - $paidTotal, 2));

        if ($paidTotal <= 0) {
            $paymentStatus = Purchase::PAYMENT_UNPAID;
        } elseif ($paidTotal < $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_PARTIAL;
        } elseif ($paidTotal > $grandTotal) {
            $paymentStatus = Purchase::PAYMENT_OVERPAID;
        } else {
            $paymentStatus = Purchase::PAYMENT_PAID;
        }

        return [
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
        ];
    }
}
