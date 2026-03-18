<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Http\Requests\UpdateActiveCartRequest;
use App\Modules\PointOfSale\Services\PosCartService;
use Illuminate\Http\JsonResponse;

class PosCartController extends Controller
{
    private $cartService;

    public function __construct(PosCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function active(): JsonResponse
    {
        $cart = $this->cartService->activeCartFor(request()->user());

        return response()->json([
            'data' => $this->cartService->serialize($cart),
        ]);
    }

    public function update(UpdateActiveCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->assignCustomer(
            $request->user(),
            $request->input('contact_id'),
            $request->input('customer_label')
        );

        return response()->json([
            'message' => 'Cart aktif berhasil diperbarui.',
            'data' => $this->cartService->serialize($cart),
        ]);
    }

    public function clear(): JsonResponse
    {
        $cart = $this->cartService->clear(request()->user());

        return response()->json([
            'message' => 'Cart aktif berhasil dikosongkan.',
            'data' => $this->cartService->serialize($cart),
        ]);
    }
}
