<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CancelDraftSaleAction;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\UpdateDraftSaleAction;
use App\Modules\Sales\Actions\VoidSaleAction;
use App\Modules\Sales\Http\Requests\CancelDraftSaleRequest;
use App\Modules\Sales\Http\Requests\FinalizeSaleRequest;
use App\Modules\Sales\Http\Requests\StoreDraftSaleRequest;
use App\Modules\Sales\Http\Requests\UpdateDraftSaleRequest;
use App\Modules\Sales\Http\Requests\VoidSaleRequest;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Repositories\SaleRepository;
use App\Modules\Sales\Services\SaleLookupService;
use App\Support\DocumentSettingsResolver;
use App\Support\CurrencySettingsResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    private $repository;
    private $lookupService;
    private $createDraftSale;
    private $updateDraftSale;
    private $finalizeSale;
    private $voidSale;
    private $cancelDraftSale;
    private $documentSettings;
    private $currencySettings;

    public function __construct(
        SaleRepository $repository,
        SaleLookupService $lookupService,
        CreateDraftSaleAction $createDraftSale,
        UpdateDraftSaleAction $updateDraftSale,
        FinalizeSaleAction $finalizeSale,
        VoidSaleAction $voidSale,
        CancelDraftSaleAction $cancelDraftSale,
        DocumentSettingsResolver $documentSettings,
        CurrencySettingsResolver $currencySettings
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createDraftSale = $createDraftSale;
        $this->updateDraftSale = $updateDraftSale;
        $this->finalizeSale = $finalizeSale;
        $this->voidSale = $voidSale;
        $this->cancelDraftSale = $cancelDraftSale;
        $this->documentSettings = $documentSettings;
        $this->currencySettings = $currencySettings;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'payment_status', 'source', 'contact_id', 'date_from', 'date_to']);

        return view('sales::index', [
            'sales' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'customers' => $this->lookupService->customers(),
            'statusOptions' => $this->lookupService->statusOptions(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'sourceOptions' => $this->lookupService->sourceOptions(),
            'dependencies' => $this->lookupService->dependencyMap(),
        ]);
    }

    public function create(): View
    {
        return view('sales::create', $this->formViewData(new Sale([
            'status' => Sale::STATUS_DRAFT,
            'source' => Sale::SOURCE_MANUAL,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'transaction_date' => now(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StoreDraftSaleRequest $request): RedirectResponse
    {
        $sale = $this->createDraftSale->execute($request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Draft penjualan dibuat.');
    }

    public function show(Sale $sale): View
    {
        $sale = $this->repository->findForDetail($sale);

        return view('sales::show', [
            'sale' => $sale,
            'statusOptions' => $this->lookupService->statusOptions(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'activities' => $sale->activities()->with('causer')->latest()->get(),
        ]);
    }

    public function edit(Sale $sale): View
    {
        $sale = $this->repository->findForEdit($sale);

        abort_unless($sale->isDraft(), 404);

        return view('sales::edit', $this->formViewData($sale));
    }

    public function update(UpdateDraftSaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale = $this->updateDraftSale->execute($sale, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Draft diperbarui.');
    }

    public function finalize(FinalizeSaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale = $this->finalizeSale->execute($sale, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Penjualan difinalisasi.');
    }

    public function void(VoidSaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale = $this->voidSale->execute($sale, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Penjualan di-void.');
    }

    public function cancel(CancelDraftSaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale = $this->cancelDraftSale->execute($sale, $request->validated(), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Draft dibatalkan.');
    }

    public function invoice(Sale $sale): View
    {
        $sale = $this->repository->findForDetail($sale);

        return view('sales::invoice', [
            'sale' => $sale,
            'documentSettings' => $this->documentSettings->forScope($sale->tenant_id, $sale->company_id, $sale->branch_id),
        ]);
    }

    private function formViewData(Sale $sale): array
    {
        return [
            'sale' => $sale->loadMissing('items'),
            'customers' => $this->lookupService->customers(),
            'sellables' => $this->lookupService->sellables(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'paymentMethodOptions' => $this->lookupService->paymentMethodOptions(),
            'sourceOptions' => $this->lookupService->sourceOptions(),
        ];
    }
}
