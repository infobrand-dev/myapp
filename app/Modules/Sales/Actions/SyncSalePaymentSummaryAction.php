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
        $summary = $this->summaryService->summarize($sale, $fallbackStatus);

        $sale->update([
            'paid_total' => $summary['paid_total'],
            'balance_due' => $summary['balance_due'],
            'payment_status' => $summary['payment_status'],
        ]);

        return $sale->refresh();
    }
}
