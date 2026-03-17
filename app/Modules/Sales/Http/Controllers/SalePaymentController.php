<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\RecordSalePaymentAction;
use App\Modules\Sales\Actions\VoidSalePaymentAction;
use App\Modules\Sales\Http\Requests\RecordSalePaymentRequest;
use App\Modules\Sales\Http\Requests\VoidSalePaymentRequest;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SalePayment;
use Illuminate\Http\RedirectResponse;

class SalePaymentController extends Controller
{
    private $recordSalePayment;
    private $voidSalePayment;

    public function __construct(
        RecordSalePaymentAction $recordSalePayment,
        VoidSalePaymentAction $voidSalePayment
    ) {
        $this->recordSalePayment = $recordSalePayment;
        $this->voidSalePayment = $voidSalePayment;
    }

    public function store(RecordSalePaymentRequest $request, Sale $sale): RedirectResponse
    {
        $this->recordSalePayment->execute($sale, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Payment sale berhasil dicatat.');
    }

    public function void(VoidSalePaymentRequest $request, Sale $sale, SalePayment $payment): RedirectResponse
    {
        abort_unless((int) $payment->sale_id === (int) $sale->id, 404);

        $this->voidSalePayment->execute($payment, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Payment sale berhasil di-void.');
    }
}
