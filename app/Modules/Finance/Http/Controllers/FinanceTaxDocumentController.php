<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Finance\Http\Requests\StoreFinanceTaxDocumentRequest;
use App\Modules\Finance\Http\Requests\UpdateFinanceTaxDocumentRequest;
use App\Modules\Finance\Models\FinanceTaxDocument;
use App\Modules\Finance\Models\FinanceTaxRate;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use App\Modules\Finance\Services\TaxDocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FinanceTaxDocumentController extends Controller
{
    private $taxDocumentNumberService;

    public function __construct(TaxDocumentNumberService $taxDocumentNumberService)
    {
        $this->taxDocumentNumberService = $taxDocumentNumberService;
    }

    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'contact', 'sourceDocument', 'creator'])
            ->latest('document_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance::tax-documents.index', [
            'documents' => $documents,
            'filters' => $filters,
            'documentTypeOptions' => FinanceTaxDocument::documentTypeOptions(),
            'documentStatusOptions' => FinanceTaxDocument::documentStatusOptions(),
            'taxRateOptions' => $this->taxRateOptions(),
            'sourceOptions' => $this->sourceOptions(),
            'summary' => $this->summary($filters),
            'defaultPeriodMonth' => (int) now()->month,
            'defaultPeriodYear' => (int) now()->year,
        ]);
    }

    public function exportRegister(Request $request): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'sourceDocument'])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'Document Type',
            'Document Status',
            'Tax Period',
            'Document Number',
            'External Number',
            'Transaction Date',
            'Document Date',
            'Tax Code',
            'Tax Scope',
            'Counterparty Name',
            'Counterparty Tax ID',
            'Taxable Base',
            'Tax Amount',
            'Withheld Amount',
            'Currency',
            'Source Type',
            'Source Number',
            'Reference Note',
        ];

        $fileName = 'tax-register-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($documents, $headers) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($documents as $document) {
                fputcsv($stream, [
                    $this->documentTypeLabel($document),
                    $this->documentStatusLabel($document),
                    sprintf('%02d/%04d', (int) $document->tax_period_month, (int) $document->tax_period_year),
                    $document->document_number,
                    $document->external_document_number,
                    optional($document->transaction_date)->format('Y-m-d'),
                    optional($document->document_date)->format('Y-m-d'),
                    optional($document->taxRate)->code,
                    data_get($document->meta, 'tax_scope'),
                    $document->counterparty_name_snapshot,
                    $document->counterparty_tax_id_snapshot,
                    round((float) $document->taxable_base, 2),
                    round((float) $document->tax_amount, 2),
                    round((float) $document->withheld_amount, 2),
                    $document->currency_code,
                    $this->sourceTypeLabel($document),
                    $this->sourceNumber($document),
                    $document->reference_note,
                ]);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportEfakturDraft(Request $request): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        if ($filters['document_type'] === '') {
            $filters['document_type'] = FinanceTaxDocument::TYPE_OUTPUT_VAT;
        }

        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'sourceDocument'])
            ->where('document_type', FinanceTaxDocument::TYPE_OUTPUT_VAT)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'fk_status',
            'replacement_flag',
            'tax_invoice_number',
            'tax_period_month',
            'tax_period_year',
            'transaction_date',
            'buyer_name',
            'buyer_tax_id',
            'buyer_tax_name',
            'buyer_tax_address',
            'taxable_base',
            'vat_amount',
            'luxury_tax_amount',
            'reference_document',
            'tax_code',
            'notes',
        ];

        $fileName = 'efaktur-draft-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($documents, $headers) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($documents as $document) {
                fputcsv($stream, [
                    $document->document_status === FinanceTaxDocument::STATUS_CANCELLED ? '0' : '1',
                    $document->document_status === FinanceTaxDocument::STATUS_REPLACED ? '1' : '0',
                    $document->document_number,
                    (int) $document->tax_period_month,
                    (int) $document->tax_period_year,
                    optional($document->document_date)->format('Y-m-d'),
                    $document->counterparty_name_snapshot,
                    $document->counterparty_tax_id_snapshot,
                    $document->counterparty_tax_name_snapshot,
                    $document->counterparty_tax_address_snapshot,
                    round((float) $document->taxable_base, 2),
                    round((float) $document->tax_amount, 2),
                    0,
                    $this->sourceNumber($document),
                    optional($document->taxRate)->code,
                    'Draft export struktur e-Faktur dari tax register.',
                ]);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(FinanceTaxDocument $taxDocument): View
    {
        return view('finance::tax-documents.edit', [
            'taxDocument' => $taxDocument->load(['taxRate', 'contact', 'sourceDocument']),
            'documentTypeOptions' => FinanceTaxDocument::documentTypeOptions(),
            'documentStatusOptions' => FinanceTaxDocument::documentStatusOptions(),
            'taxRateOptions' => $this->taxRateOptions(),
            'sourceOptions' => $this->sourceOptions(),
        ]);
    }

    public function store(StoreFinanceTaxDocumentRequest $request): RedirectResponse
    {
        $payload = $this->buildPayload($request);
        FinanceTaxDocument::query()->create($payload + [
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return redirect()->route('finance.tax-documents.index')->with('status', 'Dokumen register pajak ditambahkan.');
    }

    public function update(FinanceTaxDocument $taxDocument, UpdateFinanceTaxDocumentRequest $request): RedirectResponse
    {
        $taxDocument->update($this->buildPayload($request) + [
            'updated_by' => optional($request->user())->id,
        ]);

        return redirect()->route('finance.tax-documents.index')->with('status', 'Dokumen register pajak diperbarui.');
    }

    private function buildPayload(Request $request): array
    {
        $currentDocument = $request->route('taxDocument');
        $source = $this->resolveSourceDocument((string) $request->input('source_reference'));
        $sourceDocument = $source['model'];
        $contact = $source['contact'];
        $taxRate = $this->resolveTaxRate($request->input('finance_tax_rate_id'));
        $transactionDate = $request->filled('transaction_date')
            ? Carbon::parse($request->input('transaction_date'))
            : ($sourceDocument ? $this->resolveSourceTransactionDate($sourceDocument) : null);
        $documentDate = Carbon::parse($request->input('document_date'));

        $taxableBase = $request->filled('taxable_base')
            ? round((float) $request->input('taxable_base'), 2)
            : ($sourceDocument ? $this->resolveSourceTaxableBase($sourceDocument) : 0.0);
        $taxAmount = $request->filled('tax_amount')
            ? round((float) $request->input('tax_amount'), 2)
            : ($sourceDocument ? $this->resolveSourceTaxAmount($sourceDocument) : 0.0);

        $counterpartyName = $request->input('counterparty_name_snapshot') ?: ($contact ? $contact->name : null);
        $counterpartyTaxId = $request->input('counterparty_tax_id_snapshot') ?: ($contact ? $contact->vat : null);
        $counterpartyTaxName = $request->input('counterparty_tax_name_snapshot') ?: ($contact ? ($contact->tax_name ?: $contact->name) : null);
        $counterpartyTaxAddress = $request->input('counterparty_tax_address_snapshot') ?: ($contact ? $contact->tax_address : null);
        $documentStatus = (string) $request->input('document_status');
        $documentNumber = $this->resolveDocumentNumber(
            $request->input('document_number'),
            $documentStatus,
            $request->input('document_type'),
            $documentDate,
            $currentDocument
        );

        return [
            'source_document_type' => $sourceDocument ? get_class($sourceDocument) : null,
            'source_document_id' => $sourceDocument ? $sourceDocument->id : null,
            'contact_id' => $contact ? $contact->id : null,
            'finance_tax_rate_id' => $taxRate ? $taxRate->id : null,
            'document_type' => $request->input('document_type'),
            'document_status' => $documentStatus,
            'document_number' => $documentNumber,
            'external_document_number' => $this->nullableString($request->input('external_document_number')),
            'transaction_date' => $transactionDate ? $transactionDate->toDateString() : null,
            'document_date' => $documentDate->toDateString(),
            'tax_period_month' => (int) $request->input('tax_period_month'),
            'tax_period_year' => (int) $request->input('tax_period_year'),
            'counterparty_name_snapshot' => $this->nullableString($counterpartyName),
            'counterparty_tax_id_snapshot' => $this->nullableString($counterpartyTaxId),
            'counterparty_tax_name_snapshot' => $this->nullableString($counterpartyTaxName),
            'counterparty_tax_address_snapshot' => $this->nullableString($counterpartyTaxAddress),
            'taxable_base' => $taxableBase,
            'tax_amount' => $taxAmount,
            'withheld_amount' => round((float) ($request->input('withheld_amount') ?: 0), 2),
            'currency_code' => strtoupper((string) ($request->input('currency_code') ?: 'IDR')),
            'reference_note' => $request->input('reference_note'),
            'meta' => [
                'source_reference' => $request->input('source_reference'),
                'tax_scope' => $taxRate ? $taxRate->tax_scope : null,
                'legal_basis' => $taxRate ? $taxRate->legal_basis : null,
                'document_label' => $taxRate ? $taxRate->document_label : null,
                'requires_tax_number' => $taxRate ? (bool) $taxRate->requires_tax_number : false,
                'requires_counterparty_tax_id' => $taxRate ? (bool) $taxRate->requires_counterparty_tax_id : false,
                'number_auto_generated' => $documentNumber !== null && !$request->filled('document_number'),
            ],
        ];
    }

    private function taxRateOptions(): Collection
    {
        return FinanceTaxRate::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->active()
            ->orderBy('tax_type')
            ->orderBy('code')
            ->get();
    }

    private function sourceOptions(): Collection
    {
        $sales = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('status', Sale::STATUS_FINALIZED)
            ->where('tax_total', '>', 0)
            ->with('contact')
            ->tap(function ($query) {
                BranchContext::applyScope($query);
            })
            ->latest('transaction_date')
            ->limit(30)
            ->get()
            ->map(function (Sale $sale) {
                return [
                    'value' => 'sale:' . $sale->id,
                    'label' => 'Sale: ' . ($sale->sale_number ?: ('#' . $sale->id)) . ' | Tax ' . number_format((float) $sale->tax_total, 2, ',', '.'),
                ];
            });

        $purchases = Purchase::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->whereNotNull('confirmed_at')
            ->where('tax_total', '>', 0)
            ->with('supplier')
            ->tap(function ($query) {
                BranchContext::applyScope($query);
            })
            ->latest('purchase_date')
            ->limit(30)
            ->get()
            ->map(function (Purchase $purchase) {
                return [
                    'value' => 'purchase:' . $purchase->id,
                    'label' => 'Purchase: ' . ($purchase->purchase_number ?: ('#' . $purchase->id)) . ' | Tax ' . number_format((float) $purchase->tax_total, 2, ',', '.'),
                ];
            });

        return $sales->concat($purchases)->values();
    }

    private function summary(array $filters): array
    {
        $rows = $this->filteredDocumentsQuery($filters)->get();

        return [
            'output_vat_total' => round((float) $rows->where('document_type', FinanceTaxDocument::TYPE_OUTPUT_VAT)->sum('tax_amount'), 2),
            'input_vat_total' => round((float) $rows->where('document_type', FinanceTaxDocument::TYPE_INPUT_VAT)->sum('tax_amount'), 2),
            'withholding_total' => round((float) $rows->where('document_type', FinanceTaxDocument::TYPE_WITHHOLDING)->sum('withheld_amount'), 2),
            'issued_count' => (int) $rows->where('document_status', FinanceTaxDocument::STATUS_ISSUED)->count(),
        ];
    }

    private function resolveSourceDocument(string $sourceReference): array
    {
        if ($sourceReference === '' || !str_contains($sourceReference, ':')) {
            return ['model' => null, 'contact' => null];
        }

        [$type, $id] = explode(':', $sourceReference, 2);
        $id = (int) $id;

        if ($type === 'sale' && $id > 0) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('contact')
                ->find($id);

            return ['model' => $sale, 'contact' => $sale ? $sale->contact : null];
        }

        if ($type === 'purchase' && $id > 0) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('supplier')
                ->find($id);

            return ['model' => $purchase, 'contact' => $purchase ? $purchase->supplier : null];
        }

        return ['model' => null, 'contact' => null];
    }

    private function resolveTaxRate($taxRateId): ?FinanceTaxRate
    {
        if (!$taxRateId) {
            return null;
        }

        return FinanceTaxRate::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->find((int) $taxRateId);
    }

    private function resolveSourceTransactionDate($sourceDocument): ?Carbon
    {
        if ($sourceDocument instanceof Sale) {
            return $sourceDocument->transaction_date ? Carbon::parse($sourceDocument->transaction_date) : null;
        }

        if ($sourceDocument instanceof Purchase) {
            return $sourceDocument->purchase_date ? Carbon::parse($sourceDocument->purchase_date) : null;
        }

        return null;
    }

    private function resolveSourceTaxableBase($sourceDocument): float
    {
        if ($sourceDocument instanceof Sale || $sourceDocument instanceof Purchase) {
            return round(max(0, (float) $sourceDocument->subtotal - (float) $sourceDocument->discount_total), 2);
        }

        return 0.0;
    }

    private function resolveSourceTaxAmount($sourceDocument): float
    {
        if ($sourceDocument instanceof Sale || $sourceDocument instanceof Purchase) {
            return round((float) $sourceDocument->tax_total, 2);
        }

        return 0.0;
    }

    private function nullableString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'document_type' => (string) $request->input('document_type'),
            'document_status' => (string) $request->input('document_status'),
            'period_year' => (string) $request->input('period_year'),
            'period_month' => (string) $request->input('period_month'),
        ];
    }

    private function filteredDocumentsQuery(array $filters)
    {
        return FinanceTaxDocument::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->when($filters['document_type'] !== '', function ($query) use ($filters) {
                $query->where('document_type', $filters['document_type']);
            })
            ->when($filters['document_status'] !== '', function ($query) use ($filters) {
                $query->where('document_status', $filters['document_status']);
            })
            ->when($filters['period_year'] !== '', function ($query) use ($filters) {
                $query->where('tax_period_year', (int) $filters['period_year']);
            })
            ->when($filters['period_month'] !== '', function ($query) use ($filters) {
                $query->where('tax_period_month', (int) $filters['period_month']);
            })
            ->tap(function ($query) {
                BranchContext::applyScope($query);
            });
    }

    private function documentTypeLabel(FinanceTaxDocument $document): string
    {
        $options = FinanceTaxDocument::documentTypeOptions();

        return isset($options[$document->document_type]) ? $options[$document->document_type] : (string) $document->document_type;
    }

    private function documentStatusLabel(FinanceTaxDocument $document): string
    {
        $options = FinanceTaxDocument::documentStatusOptions();

        return isset($options[$document->document_status]) ? $options[$document->document_status] : (string) $document->document_status;
    }

    private function sourceTypeLabel(FinanceTaxDocument $document): ?string
    {
        if (!$document->source_document_type) {
            return null;
        }

        return class_basename($document->source_document_type);
    }

    private function sourceNumber(FinanceTaxDocument $document): ?string
    {
        $source = $document->sourceDocument;

        if ($source instanceof Sale) {
            return $source->sale_number;
        }

        if ($source instanceof Purchase) {
            return $source->purchase_number;
        }

        return null;
    }

    private function resolveDocumentNumber($requestedNumber, string $documentStatus, string $documentType, Carbon $documentDate, $currentDocument): ?string
    {
        $manualNumber = $this->nullableString($requestedNumber);

        if ($manualNumber !== null) {
            return $manualNumber;
        }

        if ($currentDocument && $currentDocument->document_number) {
            return $currentDocument->document_number;
        }

        if ($documentStatus === FinanceTaxDocument::STATUS_DRAFT) {
            return null;
        }

        return $this->taxDocumentNumberService->generateForDocumentType(
            $documentType,
            $documentDate,
            BranchContext::currentOrDefaultId()
        );
    }
}
