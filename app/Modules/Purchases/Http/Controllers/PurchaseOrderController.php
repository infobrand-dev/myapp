<?php

namespace App\Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchases\Actions\ConvertPurchaseOrderToPurchaseAction;
use App\Modules\Purchases\Actions\CreatePurchaseOrderAction;
use App\Modules\Purchases\Actions\UpdatePurchaseOrderAction;
use App\Modules\Purchases\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\Purchases\Http\Requests\UpdatePurchaseOrderRequest;
use App\Modules\Purchases\Models\PurchaseOrder;
use App\Modules\Purchases\Repositories\PurchaseOrderRepository;
use App\Modules\Purchases\Services\PurchaseLookupService;
use App\Support\CurrencySettingsResolver;
use App\Support\DocumentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    private $repository;
    private $lookupService;
    private $createOrder;
    private $updateOrder;
    private $convertOrder;
    private $currencySettings;
    private $documentWorkflow;

    public function __construct(
        PurchaseOrderRepository $repository,
        PurchaseLookupService $lookupService,
        CreatePurchaseOrderAction $createOrder,
        UpdatePurchaseOrderAction $updateOrder,
        ConvertPurchaseOrderToPurchaseAction $convertOrder,
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

        return view('purchases::orders.index', [
            'orders' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('purchases::orders.create', $this->formViewData(new PurchaseOrder([
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->createOrder->execute($request->validated(), $request->user());

        return redirect()->route('purchases.orders.show', $order)->with('status', 'Purchase order dibuat.');
    }

    public function show(PurchaseOrder $order): View
    {
        $order = $this->repository->findForDetail($order);

        return view('purchases::orders.show', [
            'order' => $order,
            'activities' => $order->activities()->with('causer')->latest()->get(),
            'requiresApprovalBeforeConversion' => $this->documentWorkflow->requiresApprovalBeforeConversion('purchase_order', $order->company_id, $order->branch_id),
        ]);
    }

    public function edit(PurchaseOrder $order): View
    {
        $order = $this->repository->findForEdit($order);

        abort_unless($order->isDraft(), 404);

        return view('purchases::orders.edit', $this->formViewData($order));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $order): RedirectResponse
    {
        $order = $this->updateOrder->execute($order, $request->validated(), $request->user());

        return redirect()->route('purchases.orders.show', $order)->with('status', 'Purchase order diperbarui.');
    }

    public function markStatus(Request $request, PurchaseOrder $order, string $status): RedirectResponse
    {
        abort_unless(in_array($status, [
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_REJECTED,
        ], true), 404);

        if ($order->isConverted()) {
            return redirect()->route('purchases.orders.show', $order)->withErrors([
                'purchase_order' => 'Purchase order yang sudah dikonversi tidak bisa diubah statusnya.',
            ]);
        }

        if (!$order->canTransitionTo($status)) {
            return redirect()->route('purchases.orders.show', $order)->withErrors([
                'purchase_order' => 'Transisi status purchase order tidak valid dari status saat ini.',
            ]);
        }

        $attributes = [
            'status' => $status,
            'updated_by' => $request->user() ? $request->user()->id : null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
        ];

        if ($status === PurchaseOrder::STATUS_SENT) {
            $attributes['sent_at'] = now();
        }

        if ($status === PurchaseOrder::STATUS_APPROVED) {
            $attributes['approved_at'] = now();
            $attributes['approved_by'] = $request->user() ? $request->user()->id : null;
        }

        if ($status === PurchaseOrder::STATUS_REJECTED) {
            $attributes['rejected_at'] = now();
        }

        $order->update($attributes);

        return redirect()->route('purchases.orders.show', $order)->with('status', 'Status purchase order diperbarui.');
    }

    public function convert(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $order = $this->convertOrder->execute($order, $request->user());

        return redirect()->route('purchases.orders.show', $order)->with('status', 'Purchase order berhasil dikonversi menjadi draft purchase.');
    }

    private function formViewData(PurchaseOrder $order): array
    {
        return [
            'order' => $order->loadMissing('items'),
            'purchasables' => $this->lookupService->purchasables(),
            'purchaseTaxOptions' => $this->lookupService->purchaseTaxOptions(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            PurchaseOrder::STATUS_DRAFT => 'Draft',
            PurchaseOrder::STATUS_SENT => 'Sent',
            PurchaseOrder::STATUS_APPROVED => 'Approved',
            PurchaseOrder::STATUS_REJECTED => 'Rejected',
            PurchaseOrder::STATUS_CONVERTED => 'Converted',
        ];
    }
}
