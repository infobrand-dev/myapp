<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\ConvertSaleQuotationToSaleAction;
use App\Modules\Sales\Actions\CreateSaleQuotationAction;
use App\Modules\Sales\Actions\UpdateSaleQuotationAction;
use App\Modules\Sales\Http\Requests\StoreSaleQuotationRequest;
use App\Modules\Sales\Http\Requests\UpdateSaleQuotationRequest;
use App\Modules\Sales\Models\SaleQuotation;
use App\Modules\Sales\Repositories\SaleQuotationRepository;
use App\Modules\Sales\Services\SaleLookupService;
use App\Support\CurrencySettingsResolver;
use App\Support\DocumentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleQuotationController extends Controller
{
    private $repository;
    private $lookupService;
    private $createQuotation;
    private $updateQuotation;
    private $convertQuotation;
    private $currencySettings;
    private $documentWorkflow;

    public function __construct(
        SaleQuotationRepository $repository,
        SaleLookupService $lookupService,
        CreateSaleQuotationAction $createQuotation,
        UpdateSaleQuotationAction $updateQuotation,
        ConvertSaleQuotationToSaleAction $convertQuotation,
        CurrencySettingsResolver $currencySettings,
        DocumentWorkflowService $documentWorkflow
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createQuotation = $createQuotation;
        $this->updateQuotation = $updateQuotation;
        $this->convertQuotation = $convertQuotation;
        $this->currencySettings = $currencySettings;
        $this->documentWorkflow = $documentWorkflow;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'contact_id', 'date_from', 'date_to']);

        return view('sales::quotations.index', [
            'quotations' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('sales::quotations.create', $this->formViewData(new SaleQuotation([
            'status' => SaleQuotation::STATUS_DRAFT,
            'quotation_date' => now(),
            'valid_until_date' => now()->addDays(14)->startOfDay(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StoreSaleQuotationRequest $request): RedirectResponse
    {
        $quotation = $this->createQuotation->execute($request->validated(), $request->user());

        return redirect()->route('sales.quotations.show', $quotation)->with('status', 'Quotation dibuat.');
    }

    public function show(SaleQuotation $quotation): View
    {
        $quotation = $this->repository->findForDetail($quotation);

        return view('sales::quotations.show', [
            'quotation' => $quotation,
            'activities' => $quotation->activities()->with('causer')->latest()->get(),
            'requiresApprovalBeforeConversion' => $this->documentWorkflow->requiresApprovalBeforeConversion('sale_quotation', $quotation->company_id, $quotation->branch_id),
        ]);
    }

    public function edit(SaleQuotation $quotation): View
    {
        $quotation = $this->repository->findForEdit($quotation);

        abort_unless($quotation->isDraft(), 404);

        return view('sales::quotations.edit', $this->formViewData($quotation));
    }

    public function update(UpdateSaleQuotationRequest $request, SaleQuotation $quotation): RedirectResponse
    {
        $quotation = $this->updateQuotation->execute($quotation, $request->validated(), $request->user());

        return redirect()->route('sales.quotations.show', $quotation)->with('status', 'Quotation diperbarui.');
    }

    public function markStatus(Request $request, SaleQuotation $quotation, string $status): RedirectResponse
    {
        abort_unless(in_array($status, [
            SaleQuotation::STATUS_SENT,
            SaleQuotation::STATUS_APPROVED,
            SaleQuotation::STATUS_REJECTED,
            SaleQuotation::STATUS_EXPIRED,
        ], true), 404);

        if ($quotation->isConverted()) {
            return redirect()->route('sales.quotations.show', $quotation)->withErrors([
                'quotation' => 'Quotation yang sudah dikonversi tidak bisa diubah statusnya.',
            ]);
        }

        if (!$quotation->canTransitionTo($status)) {
            return redirect()->route('sales.quotations.show', $quotation)->withErrors([
                'quotation' => 'Transisi status quotation tidak valid dari status saat ini.',
            ]);
        }

        $attributes = [
            'status' => $status,
            'updated_by' => $request->user() ? $request->user()->id : null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'expired_at' => null,
        ];

        if ($status === SaleQuotation::STATUS_SENT) {
            $attributes['sent_at'] = now();
        }

        if ($status === SaleQuotation::STATUS_APPROVED) {
            $attributes['approved_at'] = now();
            $attributes['approved_by'] = $request->user() ? $request->user()->id : null;
        }

        if ($status === SaleQuotation::STATUS_REJECTED) {
            $attributes['rejected_at'] = now();
        }

        if ($status === SaleQuotation::STATUS_EXPIRED) {
            $attributes['expired_at'] = now();
        }

        $quotation->update($attributes);

        return redirect()->route('sales.quotations.show', $quotation)->with('status', 'Status quotation diperbarui.');
    }

    public function convert(Request $request, SaleQuotation $quotation): RedirectResponse
    {
        $quotation = $this->convertQuotation->execute($quotation, $request->user());

        return redirect()->route('sales.quotations.show', $quotation)->with('status', 'Quotation berhasil dikonversi menjadi draft sale.');
    }

    private function formViewData(SaleQuotation $quotation): array
    {
        return [
            'quotation' => $quotation->loadMissing('items'),
            'sellables' => $this->lookupService->sellables(),
            'salesTaxOptions' => $this->lookupService->salesTaxOptions(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            SaleQuotation::STATUS_DRAFT => 'Draft',
            SaleQuotation::STATUS_SENT => 'Sent',
            SaleQuotation::STATUS_APPROVED => 'Approved',
            SaleQuotation::STATUS_REJECTED => 'Rejected',
            SaleQuotation::STATUS_EXPIRED => 'Expired',
            SaleQuotation::STATUS_CONVERTED => 'Converted',
        ];
    }
}
