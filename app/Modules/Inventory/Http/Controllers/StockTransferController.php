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
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockTransferController extends Controller
{

    public function index(): View
    {
        return view('inventory::transfers.index', [
            'transfers' => StockTransfer::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['sourceLocation', 'destinationLocation', 'creator'])
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        return view('inventory::transfers.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()
                ->where('tenant_id', TenantContext::currentId())
                ->trackingStock()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreStockTransferRequest $request, CreateStockTransferAction $action): RedirectResponse
    {
        $transfer = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.transfers.show', $transfer)->with('status', "Transfer {$transfer->code} dibuat.");
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

        return back()->with('status', 'Transfer disetujui.');
    }

    public function send(StockTransfer $transfer, SendStockTransferAction $action): RedirectResponse
    {
        $action->execute($transfer, request()->user());

        return back()->with('status', 'Transfer dikirim dan stok dikurangi.');
    }

    public function receive(StockTransfer $transfer, ReceiveStockTransferAction $action): RedirectResponse
    {
        $action->execute($transfer, request()->user());

        return back()->with('status', 'Transfer diterima dan stok bertambah.');
    }
}
