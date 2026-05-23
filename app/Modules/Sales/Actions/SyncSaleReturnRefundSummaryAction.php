<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\SaleReturn;

class SyncSaleReturnRefundSummaryAction
{
    public function execute(SaleReturn $saleReturn): SaleReturn
    {
        $previousRefundedTotal = round((float) $saleReturn->refunded_total, 2);
        $previousRefundBalance = round((float) $saleReturn->refund_balance, 2);
        $previousRefundStatus = (string) $saleReturn->refund_status;

        $refundedTotal = (float) PaymentAllocation::query()
            ->where('payable_type', $saleReturn->getMorphClass())
            ->where('payable_id', $saleReturn->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->sum('amount');

        $grandTotal = round((float) $saleReturn->grand_total, 2);
        $refundedTotal = round($refundedTotal, 2);
        $refundBalance = max(0, round($grandTotal - $refundedTotal, 2));

        $manualOverride = (string) data_get($saleReturn->meta, 'refund_status_override', '');

        if (!$saleReturn->refund_required) {
            $refundStatus = SaleReturn::REFUND_NOT_REQUIRED;
        } elseif ($manualOverride === SaleReturn::REFUND_SKIPPED && $refundedTotal <= 0) {
            $refundStatus = SaleReturn::REFUND_SKIPPED;
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

        if (
            $previousRefundStatus !== $refundStatus
            || $previousRefundedTotal !== $refundedTotal
            || $previousRefundBalance !== $refundBalance
        ) {
            $saleReturn->statusLogs()->create([
                'tenant_id' => $saleReturn->tenant_id,
                'company_id' => $saleReturn->company_id,
                'from_status' => $previousRefundStatus,
                'to_status' => $refundStatus,
                'event' => 'refund_settlement_updated',
                'reason' => null,
                'meta' => [
                    'previous_refunded_total' => $previousRefundedTotal,
                    'current_refunded_total' => $refundedTotal,
                    'previous_refund_balance' => $previousRefundBalance,
                    'current_refund_balance' => $refundBalance,
                ],
                'actor_id' => null,
            ]);
        }

        return $saleReturn->refresh();
    }
}
