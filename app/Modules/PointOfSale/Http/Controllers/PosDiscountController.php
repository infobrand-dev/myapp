<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discounts\Actions\EvaluateDiscountsAction;
use App\Modules\PointOfSale\Http\Requests\EvaluatePosDiscountRequest;
use App\Modules\PointOfSale\Services\PosCartService;
use Illuminate\Http\JsonResponse;

class PosDiscountController extends Controller
{
    private $evaluateDiscounts;
    private $cartService;

    public function __construct(EvaluateDiscountsAction $evaluateDiscounts, PosCartService $cartService)
    {
        $this->evaluateDiscounts = $evaluateDiscounts;
        $this->cartService = $cartService;
    }

    public function evaluate(EvaluatePosDiscountRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['sales_channel'] = 'pos';
        $result = $this->evaluateDiscounts->execute($payload);
        $cart = $this->cartService->applyDiscountEvaluation($request->user(), $result->toArray());

        return response()->json([
            'data' => $result->toArray(),
            'cart' => $this->cartService->serialize($cart),
        ]);
    }
}
