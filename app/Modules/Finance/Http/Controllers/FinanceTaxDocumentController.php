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
use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationMessage;
use App\Support\TenantContext;
use App\Modules\Finance\Services\TaxDocumentNumberService;
use App\Modules\Finance\Services\TaxWithholdingJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FinanceTaxDocumentController extends Controller
{
    private $taxDocumentNumberService;
    private $taxWithholdingJournalService;

    public function __construct(TaxDocumentNumberService $taxDocumentNumberService, TaxWithholdingJournalService $taxWithholdingJournalService)
    {
        $this->taxDocumentNumberService = $taxDocumentNumberService;
        $this->taxWithholdingJournalService = $taxWithholdingJournalService;
    }

    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'contact', 'sourceDocument', 'creator', 'replacedDocument'])
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
            'replaceableDocumentOptions' => $this->replaceableDocumentOptions(null),
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
            ->with(['taxRate', 'sourceDocument', 'replacedDocument'])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'Document Type',
            'Document Status',
            'Tax Period',
            'Document Number',
            'Replaces Document Number',
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
            'Status Reason',
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
                    $document->replacedDocument ? $document->replacedDocument->document_number : null,
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
                    $document->status_reason,
                ]);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportEfaktur(Request $request): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        if ($filters['document_type'] === '') {
            $filters['document_type'] = FinanceTaxDocument::TYPE_OUTPUT_VAT;
        }

        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'sourceDocument', 'replacedDocument'])
            ->where('document_type', FinanceTaxDocument::TYPE_OUTPUT_VAT)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'export_status',
            'validation_notes',
            'fk_status',
            'transaction_code',
            'additional_code',
            'replacement_flag',
            'tax_invoice_number',
            'replaced_tax_invoice_number',
            'tax_period_month',
            'tax_period_year',
            'transaction_date',
            'document_date',
            'buyer_name',
            'buyer_tax_id',
            'buyer_tax_name',
            'buyer_tax_address',
            'taxable_base',
            'vat_amount',
            'luxury_tax_amount',
            'reference_document',
            'external_document_number',
            'tax_code',
            'tax_scope',
            'legal_basis',
            'notes',
        ];

        $fileName = 'efaktur-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($documents, $headers) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($documents as $document) {
                $validationNotes = $this->efakturValidationNotes($document);

                fputcsv($stream, [
                    empty($validationNotes) ? 'ready' : 'blocked',
                    implode('; ', $validationNotes),
                    $document->document_status === FinanceTaxDocument::STATUS_CANCELLED ? '0' : '1',
                    $this->efakturTransactionCode($document),
                    $this->efakturAdditionalCode($document),
                    $document->document_status === FinanceTaxDocument::STATUS_REPLACED ? '1' : '0',
                    $document->document_number,
                    $document->replacedDocument ? $document->replacedDocument->document_number : null,
                    (int) $document->tax_period_month,
                    (int) $document->tax_period_year,
                    optional($document->transaction_date)->format('Y-m-d'),
                    optional($document->document_date)->format('Y-m-d'),
                    $document->counterparty_name_snapshot,
                    $document->counterparty_tax_id_snapshot,
                    $document->counterparty_tax_name_snapshot,
                    $document->counterparty_tax_address_snapshot,
                    round((float) $document->taxable_base, 2),
                    round((float) $document->tax_amount, 2),
                    0,
                    $this->sourceNumber($document),
                    $document->external_document_number,
                    optional($document->taxRate)->code,
                    optional($document->taxRate)->tax_scope ?: data_get($document->meta, 'tax_scope'),
                    optional($document->taxRate)->legal_basis ?: data_get($document->meta, 'legal_basis'),
                    'Final app-side export untuk proses e-Faktur tenant; bukan direct submit ke DJP.',
                ]);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportWithholding(Request $request): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        if ($filters['document_type'] === '') {
            $filters['document_type'] = FinanceTaxDocument::TYPE_WITHHOLDING;
        }

        $documents = $this->filteredDocumentsQuery($filters)
            ->with(['taxRate', 'sourceDocument', 'replacedDocument'])
            ->where('document_type', FinanceTaxDocument::TYPE_WITHHOLDING)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $headers = [
            'export_status',
            'validation_notes',
            'document_status',
            'withholding_direction',
            'tax_period_month',
            'tax_period_year',
            'document_number',
            'external_document_number',
            'transaction_date',
            'document_date',
            'source_type',
            'source_number',
            'counterparty_name',
            'counterparty_tax_id',
            'counterparty_tax_name',
            'counterparty_tax_address',
            'tax_code',
            'tax_scope',
            'legal_basis',
            'taxable_base',
            'tax_amount',
            'withheld_amount',
            'currency_code',
            'withholding_account_code',
            'reference_note',
            'status_reason',
            'replaces_document_number',
        ];

        $fileName = 'withholding-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($documents, $headers) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($documents as $document) {
                $direction = $this->withholdingDirection($document);
                $validationNotes = $this->withholdingValidationNotes($document, $direction);

                fputcsv($stream, [
                    empty($validationNotes) ? 'ready' : 'blocked',
                    implode('; ', $validationNotes),
                    $document->document_status,
                    $direction,
                    (int) $document->tax_period_month,
                    (int) $document->tax_period_year,
                    $document->document_number,
                    $document->external_document_number,
                    optional($document->transaction_date)->format('Y-m-d'),
                    optional($document->document_date)->format('Y-m-d'),
                    $this->sourceTypeLabel($document),
                    $this->sourceNumber($document),
                    $document->counterparty_name_snapshot,
                    $document->counterparty_tax_id_snapshot,
                    $document->counterparty_tax_name_snapshot,
                    $document->counterparty_tax_address_snapshot,
                    optional($document->taxRate)->code,
                    optional($document->taxRate)->tax_scope ?: data_get($document->meta, 'tax_scope'),
                    optional($document->taxRate)->legal_basis ?: data_get($document->meta, 'legal_basis'),
                    round((float) $document->taxable_base, 2),
                    round((float) $document->tax_amount, 2),
                    round((float) $document->withheld_amount, 2),
                    $document->currency_code,
                    $this->withholdingAccountCode($document, $direction),
                    $document->reference_note,
                    $document->status_reason,
                    $document->replacedDocument ? $document->replacedDocument->document_number : null,
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
            'replaceableDocumentOptions' => $this->replaceableDocumentOptions($taxDocument),
            'sourceOptions' => $this->sourceOptions(),
        ]);
    }

    public function store(StoreFinanceTaxDocumentRequest $request): RedirectResponse
    {
        $payload = $this->buildPayload($request);
        $taxDocument = FinanceTaxDocument::query()->create($payload + [
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);
        $this->taxWithholdingJournalService->sync($taxDocument->fresh(['taxRate']));
        $this->publishTaxDocumentNotification($taxDocument->fresh(['taxRate']));

        return redirect()->route('finance.tax-documents.index')->with('status', 'Dokumen register pajak ditambahkan.');
    }

    public function update(FinanceTaxDocument $taxDocument, UpdateFinanceTaxDocumentRequest $request): RedirectResponse
    {
        $taxDocument->update($this->buildPayload($request) + [
            'updated_by' => optional($request->user())->id,
        ]);
        $this->taxWithholdingJournalService->sync($taxDocument->fresh(['taxRate']));
        $this->publishTaxDocumentNotification($taxDocument->fresh(['taxRate']));

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
        $withholdingDirection = $this->resolveWithholdingDirectionInput(
            (string) $request->input('document_type'),
            $request->input('withholding_direction'),
            $sourceDocument
        );
        $efakturTransactionCode = $this->nullableString($request->input('efaktur_transaction_code'))
            ?: $this->defaultEfakturTransactionCode($sourceDocument);
        $efakturAdditionalCode = $this->nullableString($request->input('efaktur_additional_code'));
        $documentNumber = $this->resolveDocumentNumber(
            $request->input('document_number'),
            $documentStatus,
            $request->input('document_type'),
            $documentDate,
            $currentDocument
        );
        $this->validateLifecycle($request, $taxRate, $documentStatus, $documentNumber, $counterpartyTaxId, $currentDocument);
        $statusTimestamps = $this->statusTimestamps($documentStatus, $currentDocument);
        $baseMeta = is_array(optional($currentDocument)->meta) ? $currentDocument->meta : [];

        return [
            'source_document_type' => $sourceDocument ? get_class($sourceDocument) : null,
            'source_document_id' => $sourceDocument ? $sourceDocument->id : null,
            'contact_id' => $contact ? $contact->id : null,
            'finance_tax_rate_id' => $taxRate ? $taxRate->id : null,
            'document_type' => $request->input('document_type'),
            'document_status' => $documentStatus,
            'replaces_tax_document_id' => $this->resolveReplacedDocumentId($request, $documentStatus, $currentDocument),
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
            'status_reason' => $this->nullableString($request->input('status_reason')),
            'issued_at' => $statusTimestamps['issued_at'],
            'replaced_at' => $statusTimestamps['replaced_at'],
            'cancelled_at' => $statusTimestamps['cancelled_at'],
            'meta' => array_merge($baseMeta, [
                'source_reference' => $request->input('source_reference'),
                'tax_scope' => $taxRate ? $taxRate->tax_scope : null,
                'legal_basis' => $taxRate ? $taxRate->legal_basis : null,
                'document_label' => $taxRate ? $taxRate->document_label : null,
                'requires_tax_number' => $taxRate ? (bool) $taxRate->requires_tax_number : false,
                'requires_counterparty_tax_id' => $taxRate ? (bool) $taxRate->requires_counterparty_tax_id : false,
                'withholding_direction' => $withholdingDirection,
                'efaktur_transaction_code' => $efakturTransactionCode,
                'efaktur_additional_code' => $efakturAdditionalCode,
                'number_auto_generated' => $documentNumber !== null && !$request->filled('document_number'),
                'lifecycle_last_changed_at' => now()->toDateTimeString(),
                'lifecycle_status_reason' => $this->nullableString($request->input('status_reason')),
            ]),
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

    private function replaceableDocumentOptions($currentDocument): Collection
    {
        return FinanceTaxDocument::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->whereIn('document_status', [FinanceTaxDocument::STATUS_ISSUED, FinanceTaxDocument::STATUS_REPLACED])
            ->when($currentDocument, function ($query) use ($currentDocument) {
                $query->where('id', '!=', $currentDocument->id)
                    ->where('document_type', $currentDocument->document_type);
            })
            ->tap(function ($query) {
                BranchContext::applyScope($query);
            })
            ->latest('document_date')
            ->limit(50)
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
            'missing_tax_profile_count' => (int) $rows->filter(fn (FinanceTaxDocument $document) => !$this->hasCounterpartyTaxProfile($document))->count(),
            'missing_document_number_count' => (int) $rows
                ->whereIn('document_status', [
                    FinanceTaxDocument::STATUS_ISSUED,
                    FinanceTaxDocument::STATUS_REPLACED,
                    FinanceTaxDocument::STATUS_CANCELLED,
                ])
                ->filter(fn (FinanceTaxDocument $document) => $this->nullableString($document->document_number) === null)
                ->count(),
            'ready_efaktur_count' => (int) $rows
                ->filter(fn (FinanceTaxDocument $document) => $this->isEfakturReady($document))
                ->count(),
            'ready_withholding_count' => (int) $rows
                ->filter(fn (FinanceTaxDocument $document) => $this->isWithholdingReady($document))
                ->count(),
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

    private function hasCounterpartyTaxProfile(FinanceTaxDocument $document): bool
    {
        return $this->nullableString($document->counterparty_tax_id_snapshot) !== null
            && $this->nullableString($document->counterparty_tax_name_snapshot) !== null
            && $this->nullableString($document->counterparty_tax_address_snapshot) !== null;
    }

    private function isEfakturReady(FinanceTaxDocument $document): bool
    {
        if ($document->document_type !== FinanceTaxDocument::TYPE_OUTPUT_VAT) {
            return false;
        }

        if ($document->document_status !== FinanceTaxDocument::STATUS_ISSUED) {
            return false;
        }

        return $this->nullableString($document->document_number) !== null
            && $this->hasCounterpartyTaxProfile($document);
    }

    private function isWithholdingReady(FinanceTaxDocument $document): bool
    {
        if ($document->document_type !== FinanceTaxDocument::TYPE_WITHHOLDING) {
            return false;
        }

        if (!in_array($document->document_status, [
            FinanceTaxDocument::STATUS_ISSUED,
            FinanceTaxDocument::STATUS_REPLACED,
            FinanceTaxDocument::STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        return $this->nullableString($document->document_number) !== null
            && $this->hasCounterpartyTaxProfile($document)
            && (float) $document->withheld_amount > 0
            && $this->nullableString($this->withholdingAccountCode($document, $this->withholdingDirection($document))) !== null;
    }

    private function publishTaxDocumentNotification(FinanceTaxDocument $document): void
    {
        $needsAttention = !$this->hasCounterpartyTaxProfile($document)
            || (
                in_array($document->document_status, [
                    FinanceTaxDocument::STATUS_ISSUED,
                    FinanceTaxDocument::STATUS_REPLACED,
                    FinanceTaxDocument::STATUS_CANCELLED,
                ], true)
                && $this->nullableString($document->document_number) === null
            );

        if (!$needsAttention) {
            return;
        }

        app(NotificationCenter::class)->publish(new NotificationMessage(
            module: 'finance',
            type: 'finance.tax_document_incomplete',
            title: 'Tax register belum lengkap',
            body: 'Dokumen pajak ' . ($document->document_number ?: ('#' . $document->id)) . ' masih perlu dilengkapi sebelum compliance/export.',
            tenantId: (int) $document->tenant_id,
            companyId: (int) $document->company_id,
            branchId: $document->branch_id ? (int) $document->branch_id : null,
            resourceType: $document->getMorphClass(),
            resourceId: (int) $document->id,
            dedupeKey: 'tax-document-incomplete:' . $document->id,
            actions: [
                [
                    'label' => 'Buka Tax Register',
                    'url' => route('finance.tax-documents.edit', $document),
                ],
            ],
        ));
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

    private function withholdingDirection(FinanceTaxDocument $document): string
    {
        $metaDirection = (string) data_get($document->meta, 'withholding_direction', '');
        if ($metaDirection !== '') {
            return $metaDirection;
        }

        if ($document->source_document_type === Purchase::class) {
            return 'payable';
        }

        if ($document->source_document_type === Sale::class) {
            return 'receivable';
        }

        return 'payable';
    }

    private function resolveWithholdingDirectionInput(string $documentType, $input, $sourceDocument): ?string
    {
        if ($documentType !== FinanceTaxDocument::TYPE_WITHHOLDING) {
            return null;
        }

        $normalized = $this->nullableString($input);
        if ($normalized !== null) {
            return $normalized;
        }

        if ($sourceDocument instanceof Sale) {
            return 'receivable';
        }

        return 'payable';
    }

    private function withholdingAccountCode(FinanceTaxDocument $document, string $direction): string
    {
        if ($document->taxRate && $document->taxRate->withholding_account_code) {
            return (string) $document->taxRate->withholding_account_code;
        }

        return $direction === 'receivable' ? 'PPH_RECEIVABLE' : 'PPH_PAYABLE';
    }

    private function defaultEfakturTransactionCode($sourceDocument): string
    {
        if ($sourceDocument instanceof Sale) {
            return '01';
        }

        return '01';
    }

    private function efakturTransactionCode(FinanceTaxDocument $document): string
    {
        return (string) (data_get($document->meta, 'efaktur_transaction_code') ?: $this->defaultEfakturTransactionCode($document->sourceDocument));
    }

    private function efakturAdditionalCode(FinanceTaxDocument $document): ?string
    {
        return $this->nullableString(data_get($document->meta, 'efaktur_additional_code'));
    }

    private function efakturValidationNotes(FinanceTaxDocument $document): array
    {
        $notes = [];

        if ($document->document_status !== FinanceTaxDocument::STATUS_ISSUED) {
            $notes[] = 'document_not_issued';
        }

        if ($this->nullableString($document->document_number) === null) {
            $notes[] = 'missing_tax_invoice_number';
        }

        if (!$this->hasCounterpartyTaxProfile($document)) {
            $notes[] = 'missing_counterparty_tax_profile';
        }

        if ($this->nullableString($document->counterparty_name_snapshot) === null) {
            $notes[] = 'missing_counterparty_name';
        }

        if ($this->nullableString($document->counterparty_tax_name_snapshot) === null) {
            $notes[] = 'missing_counterparty_tax_name';
        }

        if ($this->nullableString($document->counterparty_tax_address_snapshot) === null) {
            $notes[] = 'missing_counterparty_tax_address';
        }

        if (!$document->transaction_date) {
            $notes[] = 'missing_transaction_date';
        }

        if (!$document->document_date) {
            $notes[] = 'missing_document_date';
        }

        if ((float) $document->taxable_base <= 0) {
            $notes[] = 'missing_taxable_base';
        }

        if ((float) $document->tax_amount <= 0) {
            $notes[] = 'missing_vat_amount';
        }

        if (!preg_match('/^\d{2}$/', $this->efakturTransactionCode($document))) {
            $notes[] = 'invalid_transaction_code';
        }

        return $notes;
    }

    private function withholdingValidationNotes(FinanceTaxDocument $document, string $direction): array
    {
        $notes = [];

        if ($document->document_status === FinanceTaxDocument::STATUS_DRAFT) {
            $notes[] = 'document_not_formal';
        }

        if ($this->nullableString($document->document_number) === null) {
            $notes[] = 'missing_document_number';
        }

        if (!$this->hasCounterpartyTaxProfile($document)) {
            $notes[] = 'missing_counterparty_tax_profile';
        }

        if (!$document->transaction_date) {
            $notes[] = 'missing_transaction_date';
        }

        if (!$document->document_date) {
            $notes[] = 'missing_document_date';
        }

        if ((float) $document->taxable_base <= 0) {
            $notes[] = 'missing_taxable_base';
        }

        if ((float) $document->withheld_amount <= 0) {
            $notes[] = 'missing_withheld_amount';
        }

        if (!in_array($direction, ['payable', 'receivable'], true)) {
            $notes[] = 'invalid_withholding_direction';
        }

        if ($this->nullableString($this->withholdingAccountCode($document, $direction)) === null) {
            $notes[] = 'missing_withholding_account_code';
        }

        return $notes;
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

    private function validateLifecycle(Request $request, ?FinanceTaxRate $taxRate, string $documentStatus, ?string $documentNumber, ?string $counterpartyTaxId, $currentDocument): void
    {
        if ($currentDocument && $currentDocument->document_status !== FinanceTaxDocument::STATUS_DRAFT && $documentStatus === FinanceTaxDocument::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'document_status' => 'Dokumen pajak formal tidak boleh dikembalikan ke draft.',
            ]);
        }

        if ($currentDocument
            && in_array($currentDocument->document_status, [FinanceTaxDocument::STATUS_CANCELLED, FinanceTaxDocument::STATUS_REPLACED], true)
            && $documentStatus !== $currentDocument->document_status) {
            throw ValidationException::withMessages([
                'document_status' => 'Dokumen pajak cancelled/replaced bersifat terminal dan tidak boleh diganti statusnya.',
            ]);
        }

        if ($taxRate && (bool) $taxRate->requires_counterparty_tax_id && $documentStatus !== FinanceTaxDocument::STATUS_DRAFT && $this->nullableString($counterpartyTaxId) === null) {
            throw ValidationException::withMessages([
                'counterparty_tax_id_snapshot' => 'Counterparty Tax ID wajib diisi untuk tax master ini.',
            ]);
        }

        if ($taxRate && (bool) $taxRate->requires_tax_number && $documentStatus !== FinanceTaxDocument::STATUS_DRAFT && $this->nullableString($documentNumber) === null) {
            throw ValidationException::withMessages([
                'document_number' => 'Document Number wajib ada sebelum tax register menjadi formal.',
            ]);
        }

        if (in_array($documentStatus, [FinanceTaxDocument::STATUS_CANCELLED, FinanceTaxDocument::STATUS_REPLACED], true)
            && $this->nullableString($request->input('status_reason')) === null) {
            throw ValidationException::withMessages([
                'status_reason' => 'Status reason wajib diisi untuk dokumen pajak cancelled/replaced.',
            ]);
        }

        if ($documentStatus === FinanceTaxDocument::STATUS_REPLACED && !$request->input('replaces_tax_document_id')) {
            throw ValidationException::withMessages([
                'replaces_tax_document_id' => 'Dokumen pengganti wajib memilih dokumen pajak yang diganti.',
            ]);
        }
    }

    private function resolveReplacedDocumentId(Request $request, string $documentStatus, $currentDocument): ?int
    {
        if ($documentStatus !== FinanceTaxDocument::STATUS_REPLACED) {
            return null;
        }

        $replaceId = (int) $request->input('replaces_tax_document_id');

        if ($replaceId <= 0) {
            return null;
        }

        $query = FinanceTaxDocument::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('id', $replaceId)
            ->whereIn('document_status', [FinanceTaxDocument::STATUS_ISSUED, FinanceTaxDocument::STATUS_REPLACED]);

        if ($currentDocument) {
            $query->where('id', '!=', $currentDocument->id);
        }

        $document = $query->first();

        if (!$document) {
            throw ValidationException::withMessages([
                'replaces_tax_document_id' => 'Dokumen pajak yang diganti tidak ditemukan atau belum formal.',
            ]);
        }

        return (int) $document->id;
    }

    private function statusTimestamps(string $documentStatus, $currentDocument): array
    {
        $issuedAt = $currentDocument ? $currentDocument->issued_at : null;
        $replacedAt = $currentDocument ? $currentDocument->replaced_at : null;
        $cancelledAt = $currentDocument ? $currentDocument->cancelled_at : null;

        if ($documentStatus === FinanceTaxDocument::STATUS_ISSUED && !$issuedAt) {
            $issuedAt = now();
        }

        if ($documentStatus === FinanceTaxDocument::STATUS_REPLACED && !$replacedAt) {
            $replacedAt = now();
        }

        if ($documentStatus === FinanceTaxDocument::STATUS_CANCELLED && !$cancelledAt) {
            $cancelledAt = now();
        }

        return [
            'issued_at' => $issuedAt,
            'replaced_at' => $replacedAt,
            'cancelled_at' => $cancelledAt,
        ];
    }
}
