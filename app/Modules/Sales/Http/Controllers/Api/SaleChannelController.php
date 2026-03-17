<?php

namespace App\Modules\Sales\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Http\Requests\FinalizeChannelSaleRequest;
use App\Modules\Sales\Http\Requests\StoreChannelSaleRequest;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleIntegrationPayloadBuilder;
use Illuminate\Http\JsonResponse;

class SaleChannelController extends Controller
{
    private $createDraftSale;
    private $finalizeSale;
    private $payloadBuilder;

    public function __construct(
        CreateDraftSaleAction $createDraftSale,
        FinalizeSaleAction $finalizeSale,
        SaleIntegrationPayloadBuilder $payloadBuilder
    ) {
        $this->createDraftSale = $createDraftSale;
        $this->finalizeSale = $finalizeSale;
        $this->payloadBuilder = $payloadBuilder;
    }

    public function store(StoreChannelSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $autoFinalize = !empty($validated['auto_finalize']);
        $payments = $validated['payments'] ?? [];
        unset($validated['auto_finalize'], $validated['finalize_reason'], $validated['payments']);

        $sale = $this->createDraftSale->execute($validated, $request->user());
        $statusCode = 201;

        if ($autoFinalize && $sale->isDraft()) {
            $sale = $this->finalizeSale->execute($sale, [
                'payment_status' => $validated['payment_status'],
                'reason' => $request->input('finalize_reason'),
                'payments' => $payments,
            ], $request->user());
        } elseif (!$sale->wasRecentlyCreated) {
            $statusCode = 200;
        }

        return response()->json([
            'message' => $autoFinalize ? 'Sale channel transaction accepted and finalized.' : 'Sale draft accepted.',
            'data' => $this->payloadBuilder->build($sale),
        ], $statusCode);
    }

    public function finalize(FinalizeChannelSaleRequest $request, Sale $sale): JsonResponse
    {
        $sale = $this->finalizeSale->execute($sale, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Sale finalized.',
            'data' => $this->payloadBuilder->build($sale),
        ]);
    }
}
