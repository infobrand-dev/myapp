<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Http\Requests\CheckoutPosRequest;
use App\Modules\PointOfSale\Services\PosCheckoutOrchestrator;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    private $orchestrator;

    public function __construct(PosCheckoutOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    public function store(CheckoutPosRequest $request): JsonResponse
    {
        $result = $this->orchestrator->execute($request->user(), $request->validated());

        return response()->json([
            'message' => 'Checkout POS berhasil.',
            'data' => [
                'cart_id' => $result['cart']->id,
                'sale_id' => $result['sale']->id,
                'sale_number' => $result['sale']->sale_number,
                'receipt_route' => route('pos.receipts.show', $result['sale']),
                'receipt_print_route' => route('pos.receipts.print', $result['sale']),
                'change_amount' => $result['change_amount'],
                'payments' => collect($result['payments'])->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_number' => $payment->payment_number,
                        'amount' => (float) $payment->amount,
                        'method' => $payment->method ? $payment->method->name : null,
                    ];
                })->values()->all(),
            ],
        ]);
    }
}
