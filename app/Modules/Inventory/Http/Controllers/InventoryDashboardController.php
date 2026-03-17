<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Inventory\Services\InventoryDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function __invoke(Request $request, InventoryDashboardService $service, StockRepository $stocks): View
    {
        $locationId = $request->integer('location_id') ?: null;

        return view('inventory::dashboard', [
            'locations' => $stocks->locations(),
            'locationId' => $locationId,
            ...$service->data($locationId),
        ]);
    }
}
