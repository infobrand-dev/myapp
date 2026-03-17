<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Repositories\StockRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request, StockMovementRepository $movements, StockRepository $stocks): View
    {
        $filters = $request->only(['location_id', 'movement_type', 'product_id', 'date_from', 'date_to', 'reference_type']);

        return view('inventory::movements.index', [
            'movements' => $movements->paginate($filters),
            'locations' => $stocks->locations(),
            'filters' => $filters,
        ]);
    }
}
