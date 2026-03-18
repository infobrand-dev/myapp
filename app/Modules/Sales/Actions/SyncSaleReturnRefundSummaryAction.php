<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\SaleReturn;

class SyncSaleReturnRefundSummaryAction
{
    public function execute(SaleReturn $saleReturn): SaleReturn
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

        $saleReturn->update([
            'refunded_total' => $refundedTotal,
            'refund_balance' => $refundBalance,
            'refund_status' => $refundStatus,
        ]);

        return $saleReturn->refresh();
    }
}
