<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateStockOpnameAction;
use App\Modules\Inventory\Actions\FinalizeStockOpnameAction;
use App\Modules\Inventory\Actions\UpdateStockOpnameAction;
use App\Modules\Inventory\Http\Requests\StoreStockOpnameRequest;
use App\Modules\Inventory\Http\Requests\UpdateStockOpnameRequest;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Repositories\StockRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    private $stocks;

    public function __construct(StockRepository $stocks)
    {
        $this->stocks = $stocks;
    }

    public function index(): View
    {
        return view('inventory::opnames.index', [
            'opnames' => StockOpname::query()
                ->with(['location', 'creator', 'finalizer', 'adjustment'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $locationId = $request->query('location_id');

        return view('inventory::opnames.create', [
            'locations' => $this->stocks->locations(),
            'selectedLocationId' => $locationId,
            'previewStocks' => $locationId ? $this->stocks->snapshotByLocation((int) $locationId) : collect(),
        ]);
    }

    public function store(StoreStockOpnameRequest $request, CreateStockOpnameAction $action): RedirectResponse
    {
        try {
            $opname = $action->execute($request->validated(), $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['opname' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('inventory.opnames.show', $opname)->with('status', 'Sesi stock opname berhasil dibuat sebagai draft.');
    }

    public function show(StockOpname $opname): View
    {
        return view('inventory::opnames.show', [
            'opname' => $opname->load([
                'location',
                'creator',
                'finalizer',
                'adjustment',
                'items.product',
                'items.variant',
            ]),
        ]);
    }

    public function update(StockOpname $opname, UpdateStockOpnameRequest $request, UpdateStockOpnameAction $action): RedirectResponse
    {
        try {
            $action->execute($opname, $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors(['opname' => $exception->getMessage()]);
        }

        return back()->with('status', 'Draft stock opname berhasil diperbarui.');
    }

    public function finalize(StockOpname $opname, FinalizeStockOpnameAction $action): RedirectResponse
    {
        $user = request()->user();

        abort_unless($user && $user->can('inventory.finalize-stock-opname'), 403);

        try {
            $action->execute($opname, $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['opname' => $exception->getMessage()]);
        }

        return back()->with('status', 'Stock opname berhasil difinalisasi. Selisih sudah dibuatkan adjustment dan movement.');
    }
}
