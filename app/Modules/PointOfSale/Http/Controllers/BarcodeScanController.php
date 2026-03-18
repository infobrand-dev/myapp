<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Actions\ResolveBarcodeToSellableAction;
use App\Modules\PointOfSale\Http\Requests\ScanBarcodeRequest;
use App\Modules\PointOfSale\Services\PosCartService;
use Illuminate\Http\JsonResponse;

class BarcodeScanController extends Controller
{
    private $resolveBarcode;
    private $cartService;

    public function __construct(ResolveBarcodeToSellableAction $resolveBarcode, PosCartService $cartService)
    {
        $this->resolveBarcode = $resolveBarcode;
        $this->cartService = $cartService;
    }

    public function store(ScanBarcodeRequest $request): JsonResponse
    {
        $resolved = $this->resolveBarcode->execute($request->string('barcode')->toString());
        $cart = $this->cartService->addSellable(
            $request->user(),
            $resolved['product'],
            $resolved['variant'],
            1,
            $resolved['barcode']
        );

        return response()->json([
            'message' => 'Produk hasil scan berhasil masuk ke cart.',
            'data' => $this->cartService->serialize($cart),
        ]);
    }
}
