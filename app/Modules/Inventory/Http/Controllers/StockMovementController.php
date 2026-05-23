<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Support\AccountingSourceReferenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(
        Request $request,
        StockMovementRepository $movements,
        StockRepository $stocks,
        AccountingSourceReferenceService $sourceReferenceService
    ): View
    {
        $filters = $request->only(['location_id', 'movement_type', 'product_id', 'date_from', 'date_to', 'reference_type']);
        $movementRows = $movements->paginate($filters);

        return view('inventory::movements.index', [
            'movements' => $movementRows,
            'locations' => $stocks->locations(),
            'filters' => $filters,
            'sourceReferences' => $sourceReferenceService->buildForSources($movementRows->getCollection()->map(function ($movement) {
                return [
                    'source_type' => $movement->reference_type,
                    'source_id' => $movement->reference_id,
                ];
            })),
        ]);
    }
}
