<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Repositories\StockRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LowStockReportController extends Controller
{
    public function __invoke(Request $request, StockRepository $stocks): View
    {
        $filters = $request->only(['search', 'location_id']);
        $filters['status'] = 'low_stock';

        return view('inventory::reports.low-stock', [
            'stocks' => $stocks->paginate($filters),
            'locations' => $stocks->locations(),
            'filters' => $filters,
        ]);
    }
}
