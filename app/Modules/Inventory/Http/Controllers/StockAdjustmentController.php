<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateStockAdjustmentAction;
use App\Modules\Inventory\Http\Requests\StoreStockAdjustmentRequest;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Products\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        return view('inventory::adjustments.index', [
            'adjustments' => StockAdjustment::query()->with(['location', 'creator'])->latest()->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        return view('inventory::adjustments.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()->where('track_stock', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, CreateStockAdjustmentAction $action): RedirectResponse
    {
        $adjustment = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.adjustments.index')->with('status', "Adjustment {$adjustment->code} berhasil diposting.");
    }
}
