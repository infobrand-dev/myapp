<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Http\Requests\HoldPosCartRequest;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\PointOfSale\Services\PosCartService;
use Illuminate\Http\JsonResponse;

class HeldCartController extends Controller
{
    private $cartService;

    public function __construct(PosCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(): JsonResponse
    {
        $carts = $this->cartService->heldCarts(request()->user())
            ->map(fn (PosCart $cart) => $this->cartService->serialize($cart))
            ->values();

        return response()->json([
            'data' => $carts,
        ]);
    }

    public function store(HoldPosCartRequest $request): JsonResponse
    {
        $result = $this->cartService->hold($request->user(), $request->input('label'));

        return response()->json([
            'message' => 'Cart berhasil di-hold.',
            'held' => $this->cartService->serialize($result['held']),
            'active' => $this->cartService->serialize($result['active']),
        ], 201);
    }

    public function resume(PosCart $cart): JsonResponse
    {
        $activeCart = $this->cartService->resume(request()->user(), $cart);

        return response()->json([
            'message' => 'Cart berhasil di-resume.',
            'data' => $this->cartService->serialize($activeCart),
        ]);
    }
}
