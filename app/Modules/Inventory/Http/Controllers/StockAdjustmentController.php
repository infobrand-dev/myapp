<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateStockAdjustmentAction;
use App\Modules\Inventory\Actions\FinalizeStockAdjustmentAction;
use App\Modules\Inventory\Http\Requests\StoreStockAdjustmentRequest;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Products\Models\Product;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{

    public function index(): View
    {
        return view('inventory::adjustments.index', [
            'adjustments' => StockAdjustment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with(['location', 'creator', 'finalizer'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        $products = Product::query()
            ->where('tenant_id', TenantContext::currentId())
            ->with(['variants' => fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('track_stock', true)
                ->orderBy('position')])
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        return view('inventory::adjustments.create', [
            'locations' => $stocks->locations(),
            'products' => $products,
            'productOptions' => $this->productOptions($products),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, CreateStockAdjustmentAction $action): RedirectResponse
    {
        $adjustment = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.adjustments.show', $adjustment)->with('status', "Adjustment {$adjustment->code} berhasil dibuat sebagai draft.");
    }

    public function show(StockAdjustment $adjustment): View
    {
        return view('inventory::adjustments.show', [
            'adjustment' => $adjustment->load([
                'location',
                'creator',
                'finalizer',
                'items.product',
                'items.variant',
                'items.movement',
            ]),
        ]);
    }

    public function finalize(StockAdjustment $adjustment, FinalizeStockAdjustmentAction $action): RedirectResponse
    {
        $user = request()->user();

        abort_unless($user && $user->can('inventory.finalize-stock-adjustment'), 403);

        try {
            $action->execute($adjustment, $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Stock adjustment berhasil difinalisasi dan movement sudah diposting.');
    }

    private function productOptions(Collection $products): array
    {
        return $products->mapWithKeys(function (Product $product) {
            return [$product->id => [
                'label' => trim($product->name . ' (' . $product->sku . ')'),
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'label' => trim($variant->name . ' (' . $variant->sku . ')'),
                    ];
                })->values()->all(),
            ]];
        })->all();
    }
}
