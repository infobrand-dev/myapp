<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CancelDraftReturnAction;
use App\Modules\Sales\Actions\CreateSalesReturnAction;
use App\Modules\Sales\Actions\FinalizeSalesReturnAction;
use App\Modules\Sales\Http\Requests\CancelDraftReturnRequest;
use App\Modules\Sales\Http\Requests\FinalizeSaleReturnRequest;
use App\Modules\Sales\Http\Requests\StoreSaleReturnRequest;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Repositories\SaleReturnRepository;
use App\Modules\Sales\Services\SaleReturnLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    private $repository;
    private $lookupService;
    private $createSalesReturn;
    private $finalizeSalesReturn;
    private $cancelDraftReturn;

    public function __construct(
        SaleReturnRepository $repository,
        SaleReturnLookupService $lookupService,
        CreateSalesReturnAction $createSalesReturn,
        FinalizeSalesReturnAction $finalizeSalesReturn,
        CancelDraftReturnAction $cancelDraftReturn
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createSalesReturn = $createSalesReturn;
        $this->finalizeSalesReturn = $finalizeSalesReturn;
        $this->cancelDraftReturn = $cancelDraftReturn;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'sale_id', 'contact_id', 'status', 'refund_status', 'date_from', 'date_to']);

        if ($request->user() && $request->user()->can('sales_return.view_all')) {
            $filters['scope'] = 'all';
        } else {
            $filters['scope'] = 'own';
            $filters['user_id'] = $request->user() ? $request->user()->id : null;
        }

        return view('sales::returns.index', [
            'returns' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'sales' => $this->lookupService->saleOptions(),
            'customers' => $this->lookupService->customerOptions(),
            'statusOptions' => $this->lookupService->statusOptions(),
            'refundStatusOptions' => $this->lookupService->refundStatusOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $sales = $this->lookupService->saleOptions();
        $selectedSale = $request->integer('sale_id')
            ? $sales->firstWhere('id', $request->integer('sale_id'))
            : $sales->first();

        return view('sales::returns.create', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
            'inventoryLocations' => $this->lookupService->inventoryLocations(),
        ]);
    }

    public function store(StoreSaleReturnRequest $request): RedirectResponse
    {
        $saleReturn = $this->createSalesReturn->execute($request->validated(), $request->user());

        return redirect()->route('sales.returns.show', $saleReturn)->with('status', 'Draft retur dibuat.');
    }

    public function show(SaleReturn $saleReturn): View
    {
        if (request()->user() && !request()->user()->can('sales_return.view_all')) {
            abort_unless(
                request()->user()->can('sales_return.view_own') && (int) $saleReturn->created_by === (int) request()->user()->id,
                403
            );
        }

        return view('sales::returns.show', [
            'saleReturn' => $this->repository->findForDetail($saleReturn),
            'statusOptions' => $this->lookupService->statusOptions(),
            'refundStatusOptions' => $this->lookupService->refundStatusOptions(),
            'inventoryStatusOptions' => $this->lookupService->inventoryStatusOptions(),
        ]);
    }

    public function finalize(FinalizeSaleReturnRequest $request, SaleReturn $saleReturn): RedirectResponse
    {
        $saleReturn = $this->finalizeSalesReturn->execute($saleReturn, $request->user());

        return redirect()->route('sales.returns.show', $saleReturn)->with('status', 'Retur difinalisasi.');
    }

    public function cancel(CancelDraftReturnRequest $request, SaleReturn $saleReturn): RedirectResponse
    {
        $saleReturn = $this->cancelDraftReturn->execute($saleReturn, $request->validated()['reason'] ?? null, $request->user());

        return redirect()->route('sales.returns.show', $saleReturn)->with('status', 'Draft retur dibatalkan.');
    }

    public function print(SaleReturn $saleReturn): View
    {
        if (request()->user() && !request()->user()->can('sales_return.view_all')) {
            abort_unless(
                request()->user()->can('sales_return.view_own') && (int) $saleReturn->created_by === (int) request()->user()->id,
                403
            );
        }

        return view('sales::returns.print', [
            'saleReturn' => $this->repository->findForDetail($saleReturn),
        ]);
    }
}
