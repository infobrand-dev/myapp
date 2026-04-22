<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\ConvertSaleOrderToSaleAction;
use App\Modules\Sales\Actions\CreateSaleOrderAction;
use App\Modules\Sales\Actions\UpdateSaleOrderAction;
use App\Modules\Sales\Http\Requests\StoreSaleOrderRequest;
use App\Modules\Sales\Http\Requests\UpdateSaleOrderRequest;
use App\Modules\Sales\Models\SaleOrder;
use App\Modules\Sales\Repositories\SaleOrderRepository;
use App\Modules\Sales\Services\SaleLookupService;
use App\Support\CurrencySettingsResolver;
use App\Support\DocumentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleOrderController extends Controller
{
    private $repository;
    private $lookupService;
    private $createOrder;
    private $updateOrder;
    private $convertOrder;
    private $currencySettings;
    private $documentWorkflow;

    public function __construct(
        SaleOrderRepository $repository,
        SaleLookupService $lookupService,
        CreateSaleOrderAction $createOrder,
        UpdateSaleOrderAction $updateOrder,
        ConvertSaleOrderToSaleAction $convertOrder,
        CurrencySettingsResolver $currencySettings,
        DocumentWorkflowService $documentWorkflow
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createOrder = $createOrder;
        $this->updateOrder = $updateOrder;
        $this->convertOrder = $convertOrder;
        $this->currencySettings = $currencySettings;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'contact_id', 'date_from', 'date_to']);

        return view('sales::orders.index', [
            'orders' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('sales::orders.create', $this->formViewData(new SaleOrder([
            'status' => SaleOrder::STATUS_DRAFT,
            'order_date' => now(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StoreSaleOrderRequest $request): RedirectResponse
    {
        $order = $this->createOrder->execute($request->validated(), $request->user());

        return redirect()->route('sales.orders.show', $order)->with('status', 'Sales order dibuat.');
    }

    public function show(SaleOrder $order): View
    {
        $order = $this->repository->findForDetail($order);

        return view('sales::orders.show', [
            'order' => $order,
            'activities' => $order->activities()->with('causer')->latest()->get(),
            'requiresApprovalBeforeConversion' => $this->documentWorkflow->requiresApprovalBeforeConversion('sale_order', $order->company_id, $order->branch_id),
        ]);
    }

    public function edit(SaleOrder $order): View
    {
        $order = $this->repository->findForEdit($order);

        abort_unless($order->isDraft(), 404);

        return view('sales::orders.edit', $this->formViewData($order));
    }

    public function update(UpdateSaleOrderRequest $request, SaleOrder $order): RedirectResponse
    {
        $order = $this->updateOrder->execute($order, $request->validated(), $request->user());

        return redirect()->route('sales.orders.show', $order)->with('status', 'Sales order diperbarui.');
    }

    public function markStatus(Request $request, SaleOrder $order, string $status): RedirectResponse
    {
        abort_unless(in_array($status, [
            SaleOrder::STATUS_SENT,
            SaleOrder::STATUS_APPROVED,
            SaleOrder::STATUS_REJECTED,
        ], true), 404);

        if ($order->isConverted()) {
            return redirect()->route('sales.orders.show', $order)->withErrors([
                'sale_order' => 'Sales order yang sudah dikonversi tidak bisa diubah statusnya.',
            ]);
        }

        if (!$order->canTransitionTo($status)) {
            return redirect()->route('sales.orders.show', $order)->withErrors([
                'sale_order' => 'Transisi status sales order tidak valid dari status saat ini.',
            ]);
        }

        $attributes = [
            'status' => $status,
            'updated_by' => $request->user() ? $request->user()->id : null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
        ];

        if ($status === SaleOrder::STATUS_SENT) {
            $attributes['sent_at'] = now();
        }

        if ($status === SaleOrder::STATUS_APPROVED) {
            $attributes['approved_at'] = now();
            $attributes['approved_by'] = $request->user() ? $request->user()->id : null;
        }

        if ($status === SaleOrder::STATUS_REJECTED) {
            $attributes['rejected_at'] = now();
        }

        $order->update($attributes);

        return redirect()->route('sales.orders.show', $order)->with('status', 'Status sales order diperbarui.');
    }

    public function convert(Request $request, SaleOrder $order): RedirectResponse
    {
        $order = $this->convertOrder->execute($order, $request->user());

        return redirect()->route('sales.orders.show', $order)->with('status', 'Sales order berhasil dikonversi menjadi draft sale.');
    }

    private function formViewData(SaleOrder $order): array
    {
        return [
            'order' => $order->loadMissing('items'),
            'sellables' => $this->lookupService->sellables(),
            'salesTaxOptions' => $this->lookupService->salesTaxOptions(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            SaleOrder::STATUS_DRAFT => 'Draft',
            SaleOrder::STATUS_SENT => 'Sent',
            SaleOrder::STATUS_APPROVED => 'Approved',
            SaleOrder::STATUS_REJECTED => 'Rejected',
            SaleOrder::STATUS_CONVERTED => 'Converted',
        ];
    }
}
