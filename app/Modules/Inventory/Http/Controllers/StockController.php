<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Repositories\StockRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockRepository $stocks)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'location_id', 'status', 'product_id']);

        return view('inventory::stocks.index', [
            'stocks' => $this->stocks->paginate($filters),
            'summary' => $this->stocks->summary($filters),
            'locations' => $this->stocks->locations(),
            'filters' => $filters,
        ]);
    }

    public function show(int $stock): View
    {
        return view('inventory::stocks.show', [
            'stock' => $this->stocks->findOrFail($stock),
        ]);
    }
}
