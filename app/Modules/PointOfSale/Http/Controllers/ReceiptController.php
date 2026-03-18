<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Models\Sale;
use Illuminate\Contracts\View\View;

class ReceiptController extends Controller
{
    public function show(Sale $sale): View
    {
        return $this->renderReceipt($sale, false);
    }

    public function print(Sale $sale): View
    {
        return $this->renderReceipt($sale, true);
    }

    private function renderReceipt(Sale $sale, bool $printMode): View
    {
        $sale->load([
            'items',
            'contact',
            'creator',
            'finalizer',
            'paymentAllocations.payment.method',
        ]);

        $cashPaid = (float) $sale->paymentAllocations
            ->filter(function ($allocation) {
                return $allocation->payment
                    && $allocation->payment->method
                    && $allocation->payment->method->code === 'cash';
            })
            ->sum('amount');

        $changeAmount = round(max(0, $cashPaid - (float) $sale->grand_total), 2);

        return view('pos::receipt', [
            'sale' => $sale,
            'printMode' => $printMode,
            'changeAmount' => $changeAmount,
        ]);
    }
}
