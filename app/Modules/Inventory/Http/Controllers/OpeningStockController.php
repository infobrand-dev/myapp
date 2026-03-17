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
    public function index(): View
    {
        return view('inventory::openings.index', [
            'openings' => StockOpening::query()->with(['location', 'creator'])->latest()->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        return view('inventory::openings.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()->where('track_stock', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOpeningStockRequest $request, CreateOpeningStockAction $action): RedirectResponse
    {
        $opening = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.openings.index')->with('status', "Opening stock {$opening->code} berhasil diposting.");
    }
}
