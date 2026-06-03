<?php

namespace App\Modules\Finance\Services;

use App\Models\Company;
use App\Modules\Finance\Models\FinanceTaxDocument;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class EfakturPartnerExportService
{
    public function buildEnvelope(Collection $documents, ?Company $company = null): array
    {
        $partnerName = (string) config('services.efaktur_partner.name', '');

        return [
            'handoff_mode' => 'partner_api_ready',
            'direct_submit_supported' => false,
            'partner_name' => $partnerName !== '' ? $partnerName : null,
            'source' => [
                'system' => (string) config('workspace-files.origin_system', config('app.name', 'app')),
                'owner' => (string) config('workspace-files.origin_owner', 'first_party'),
                'tenant_id' => TenantContext::currentId(),
                'company_id' => $company?->id,
                'company_name' => $company?->name,
                'generated_at' => now()->toIso8601String(),
            ],
            'records' => $documents->map(fn (FinanceTaxDocument $document) => $this->record($document))->values()->all(),
        ];
    }

    private function record(FinanceTaxDocument $document): array
    {
        return [
            'document_id' => (int) $document->id,
            'document_number' => $document->document_number,
            'document_status' => $document->document_status,
            'transaction_code' => (string) (data_get($document->meta, 'efaktur_transaction_code') ?: '01'),
            'additional_code' => data_get($document->meta, 'efaktur_additional_code'),
            'tax_period_month' => (int) $document->tax_period_month,
            'tax_period_year' => (int) $document->tax_period_year,
            'transaction_date' => optional($document->transaction_date)->format('Y-m-d'),
            'document_date' => optional($document->document_date)->format('Y-m-d'),
            'buyer' => [
                'name' => $document->counterparty_name_snapshot,
                'tax_id' => $document->counterparty_tax_id_snapshot,
                'tax_name' => $document->counterparty_tax_name_snapshot,
                'tax_address' => $document->counterparty_tax_address_snapshot,
            ],
            'amounts' => [
                'taxable_base' => round((float) $document->taxable_base, 2),
                'vat_amount' => round((float) $document->tax_amount, 2),
                'luxury_tax_amount' => 0,
            ],
            'reference' => [
                'source_type' => $document->source_document_type,
                'source_id' => $document->source_document_id,
                'external_document_number' => $document->external_document_number,
            ],
            'metadata' => [
                'tax_code' => optional($document->taxRate)->code,
                'tax_scope' => optional($document->taxRate)->tax_scope ?: data_get($document->meta, 'tax_scope'),
                'legal_basis' => optional($document->taxRate)->legal_basis ?: data_get($document->meta, 'legal_basis'),
            ],
        ];
    }
}
