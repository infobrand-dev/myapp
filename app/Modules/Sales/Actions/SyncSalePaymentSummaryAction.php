<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SalePaymentSummaryService;

class SyncSalePaymentSummaryAction
{
    private $summaryService;

    public function __construct(SalePaymentSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function execute(Sale $sale, ?string $fallbackStatus = null): Sale
    {
        $previousPaidTotal = round((float) $sale->paid_total, 2);
        $previousBalanceDue = round((float) $sale->balance_due, 2);
        $previousPaymentStatus = (string) $sale->payment_status;
        $summary = $this->summaryService->summarize($sale, $fallbackStatus);

        $sale->update([
            'paid_total' => $summary['paid_total'],
            'balance_due' => $summary['balance_due'],
            'payment_status' => $summary['payment_status'],
        ]);

        if (
            $previousPaymentStatus !== (string) $summary['payment_status']
            || $previousPaidTotal !== round((float) $summary['paid_total'], 2)
            || $previousBalanceDue !== round((float) $summary['balance_due'], 2)
        ) {
            $sale->statusHistories()->create([
                'tenant_id' => $sale->tenant_id,
                'company_id' => $sale->company_id,
                'branch_id' => $sale->branch_id,
                'from_status' => $sale->status,
                'to_status' => $sale->status,
                'event' => 'payment_settlement_updated',
                'reason' => null,
                'actor_id' => null,
                'meta' => [
                    'previous_payment_status' => $previousPaymentStatus,
                    'current_payment_status' => $summary['payment_status'],
                    'previous_paid_total' => $previousPaidTotal,
                    'current_paid_total' => round((float) $summary['paid_total'], 2),
                    'previous_balance_due' => $previousBalanceDue,
                    'current_balance_due' => round((float) $summary['balance_due'], 2),
                ],
            ]);
        }

        return $sale->refresh();
    }
}
