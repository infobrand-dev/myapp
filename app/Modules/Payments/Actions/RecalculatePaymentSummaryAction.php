<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Services\PaymentSummaryService;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class RecalculatePaymentSummaryAction
{
    private $summaryService;

    public function __construct(PaymentSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function execute(Model $payable): Model
    {
        $summary = $this->summaryService->summarize($payable);

        if ($payable instanceof Sale) {
            $payable->update([
                'paid_total' => $summary['paid_total'],
                'balance_due' => $summary['balance_due'],
                'payment_status' => $summary['payment_status'],
            ]);

            return $payable->refresh();
        }

        return $payable;
    }
}
