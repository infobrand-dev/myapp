<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Http\Requests\StorePosCartItemRequest;
use App\Modules\PointOfSale\Http\Requests\UpdatePosCartItemRequest;
use App\Modules\PointOfSale\Models\PosCartItem;
use App\Modules\PointOfSale\Services\PosCartService;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BooleanQuery;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

class PosCartItemController extends Controller
{
    private $cartService;

    public function __construct(PosCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function store(StorePosCartItemRequest $request): JsonResponse
    {
        $product = BooleanQuery::apply(
            Product::query()
                ->with('unit')
                ->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->findOrFail($request->integer('product_id'));
        $variant = $request->filled('product_variant_id')
            ? BooleanQuery::apply(
                ProductVariant::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('product_id', $product->id),
                'is_active'
            )
                ->findOrFail($request->integer('product_variant_id'))
            : null;

        $cart = $this->cartService->addSellable(
            $request->user(),
            $product,
            $variant,
            (float) ($request->input('qty', 1)),
            $request->input('barcode_scanned')
        );

        return response()->json([
            'message' => 'Item berhasil ditambahkan ke cart.',
            'data' => $this->cartService->serialize($cart),
        ], 201);
    }

    public function update(UpdatePosCartItemRequest $request, PosCartItem $item): JsonResponse
    {
        $cart = $this->cartService->updateItem($request->user(), $item, $request->validated());

        return response()->json([
            'message' => 'Item cart berhasil diperbarui.',
            'data' => $this->cartService->serialize($cart),
        ]);
    }

    public function destroy(PosCartItem $item): JsonResponse
    {
        $cart = $this->cartService->removeItem(request()->user(), $item);

        return response()->json([
            'message' => 'Item cart berhasil dihapus.',
            'data' => $this->cartService->serialize($cart),
        ]);
    }
}
