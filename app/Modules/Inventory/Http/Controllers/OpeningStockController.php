<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateOpeningStockAction;
use App\Modules\Inventory\Http\Requests\StoreOpeningStockRequest;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Products\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OpeningStockController extends Controller
{
    private const TENANT_ID = 1;

    public function index(): View
    {
        return view('inventory::openings.index', [
            'openings' => StockOpening::query()
                ->where('tenant_id', self::TENANT_ID)
                ->with(['location', 'creator'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        return view('inventory::openings.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()
                ->where('tenant_id', self::TENANT_ID)
                ->where('track_stock', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreOpeningStockRequest $request, CreateOpeningStockAction $action): RedirectResponse
    {
        $opening = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.openings.index')->with('status', "Opening stock {$opening->code} berhasil diposting.");
    }
}
