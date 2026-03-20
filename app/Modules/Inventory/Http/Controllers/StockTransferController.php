<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\ApproveStockTransferAction;
use App\Modules\Inventory\Actions\CreateStockTransferAction;
use App\Modules\Inventory\Actions\ReceiveStockTransferAction;
use App\Modules\Inventory\Actions\SendStockTransferAction;
use App\Modules\Inventory\Http\Requests\StoreStockTransferRequest;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Products\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    private const TENANT_ID = 1;

    public function index(): View
    {
        return view('inventory::transfers.index', [
            'transfers' => StockTransfer::query()
                ->where('tenant_id', self::TENANT_ID)
                ->with(['sourceLocation', 'destinationLocation', 'creator'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        return view('inventory::transfers.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()
                ->where('tenant_id', self::TENANT_ID)
                ->where('track_stock', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreStockTransferRequest $request, CreateStockTransferAction $action): RedirectResponse
    {
        $transfer = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.transfers.show', $transfer)->with('status', "Transfer {$transfer->code} berhasil dibuat.");
    }

    public function show(StockTransfer $transfer): View
    {
        return view('inventory::transfers.show', [
            'transfer' => $transfer->load(['sourceLocation', 'destinationLocation', 'creator', 'items.product', 'items.variant']),
        ]);
    }

    public function approve(StockTransfer $transfer, ApproveStockTransferAction $action): RedirectResponse
    {
        $action->execute($transfer, request()->user());

        return back()->with('status', 'Transfer berhasil di-approve.');
    }

    public function send(StockTransfer $transfer, SendStockTransferAction $action): RedirectResponse
    {
        $action->execute($transfer, request()->user());

        return back()->with('status', 'Transfer berhasil dikirim dan stok asal dikurangi.');
    }

    public function receive(StockTransfer $transfer, ReceiveStockTransferAction $action): RedirectResponse
    {
        $action->execute($transfer, request()->user());

        return back()->with('status', 'Transfer berhasil diterima dan stok tujuan ditambah.');
    }
}
