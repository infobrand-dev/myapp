<?php

namespace App\Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchases\Actions\ConvertPurchaseRequestToOrderAction;
use App\Modules\Purchases\Actions\CreatePurchaseRequestAction;
use App\Modules\Purchases\Actions\UpdatePurchaseRequestAction;
use App\Modules\Purchases\Http\Requests\StorePurchaseRequestRequest;
use App\Modules\Purchases\Http\Requests\UpdatePurchaseRequestRequest;
use App\Modules\Purchases\Models\PurchaseRequest;
use App\Modules\Purchases\Repositories\PurchaseRequestRepository;
use App\Modules\Purchases\Services\PurchaseLookupService;
use App\Support\CurrencySettingsResolver;
use App\Support\DocumentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    private $repository;
    private $lookupService;
    private $createRequest;
    private $updateRequest;
    private $convertRequest;
    private $currencySettings;
    private $documentWorkflow;

    public function __construct(
        PurchaseRequestRepository $repository,
        PurchaseLookupService $lookupService,
        CreatePurchaseRequestAction $createRequest,
        UpdatePurchaseRequestAction $updateRequest,
        ConvertPurchaseRequestToOrderAction $convertRequest,
        CurrencySettingsResolver $currencySettings,
        DocumentWorkflowService $documentWorkflow
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createRequest = $createRequest;
        $this->updateRequest = $updateRequest;
        $this->convertRequest = $convertRequest;
        $this->currencySettings = $currencySettings;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'contact_id', 'date_from', 'date_to']);

        return view('purchases::requests.index', [
            'requests' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('purchases::requests.create', $this->formViewData(new PurchaseRequest([
            'status' => PurchaseRequest::STATUS_DRAFT,
            'request_date' => now(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StorePurchaseRequestRequest $request): RedirectResponse
    {
        $purchaseRequest = $this->createRequest->execute($request->validated(), $request->user());

        return redirect()->route('purchases.requests.show', $purchaseRequest)->with('status', 'Purchase request dibuat.');
    }

    public function show(PurchaseRequest $requestModel): View
    {
        $requestModel = $this->repository->findForDetail($requestModel);

        return view('purchases::requests.show', [
            'requestModel' => $requestModel,
            'activities' => $requestModel->activities()->with('causer')->latest()->get(),
            'requiresApprovalBeforeConversion' => $this->documentWorkflow->requiresApprovalBeforeConversion('purchase_request', $requestModel->company_id, $requestModel->branch_id),
        ]);
    }

    public function edit(PurchaseRequest $requestModel): View
    {
        $requestModel = $this->repository->findForEdit($requestModel);

        abort_unless($requestModel->isDraft(), 404);

        return view('purchases::requests.edit', $this->formViewData($requestModel));
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $requestModel): RedirectResponse
    {
        $requestModel = $this->updateRequest->execute($requestModel, $request->validated(), $request->user());

        return redirect()->route('purchases.requests.show', $requestModel)->with('status', 'Purchase request diperbarui.');
    }

    public function markStatus(Request $request, PurchaseRequest $requestModel, string $status): RedirectResponse
    {
        abort_unless(in_array($status, [
            PurchaseRequest::STATUS_SUBMITTED,
            PurchaseRequest::STATUS_APPROVED,
            PurchaseRequest::STATUS_REJECTED,
        ], true), 404);

        if ($requestModel->isConverted()) {
            return redirect()->route('purchases.requests.show', $requestModel)->withErrors([
                'purchase_request' => 'Purchase request yang sudah dikonversi tidak bisa diubah statusnya.',
            ]);
        }

        if (!$requestModel->canTransitionTo($status)) {
            return redirect()->route('purchases.requests.show', $requestModel)->withErrors([
                'purchase_request' => 'Transisi status purchase request tidak valid dari status saat ini.',
            ]);
        }

        $attributes = [
            'status' => $status,
            'updated_by' => $request->user() ? $request->user()->id : null,
            'submitted_at' => $requestModel->submitted_at,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
        ];

        if ($status === PurchaseRequest::STATUS_SUBMITTED) {
            $attributes['submitted_at'] = now();
        }

        if ($status === PurchaseRequest::STATUS_APPROVED) {
            $attributes['approved_at'] = now();
            $attributes['approved_by'] = $request->user() ? $request->user()->id : null;
        }

        if ($status === PurchaseRequest::STATUS_REJECTED) {
            $attributes['rejected_at'] = now();
        }

        $requestModel->update($attributes);

        return redirect()->route('purchases.requests.show', $requestModel)->with('status', 'Status purchase request diperbarui.');
    }

    public function convert(Request $request, PurchaseRequest $requestModel): RedirectResponse
    {
        $requestModel = $this->convertRequest->execute($requestModel, $request->user());

        return redirect()->route('purchases.requests.show', $requestModel)->with('status', 'Purchase request berhasil dikonversi menjadi purchase order.');
    }

    private function formViewData(PurchaseRequest $requestModel): array
    {
        return [
            'requestModel' => $requestModel->loadMissing('items'),
            'purchasables' => $this->lookupService->purchasables(),
            'purchaseTaxOptions' => $this->lookupService->purchaseTaxOptions(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            PurchaseRequest::STATUS_DRAFT => 'Draft',
            PurchaseRequest::STATUS_SUBMITTED => 'Submitted',
            PurchaseRequest::STATUS_APPROVED => 'Approved',
            PurchaseRequest::STATUS_REJECTED => 'Rejected',
            PurchaseRequest::STATUS_CONVERTED => 'Converted',
        ];
    }
}
