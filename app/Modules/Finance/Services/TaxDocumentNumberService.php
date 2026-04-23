<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinanceTaxDocument;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\DocumentNumberingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class TaxDocumentNumberService
{
    private $documentNumbering;

    public function __construct(DocumentNumberingService $documentNumbering)
    {
        $this->documentNumbering = $documentNumbering;
    }

    public function generateForDocumentType(string $taxDocumentType, ?\DateTimeInterface $date = null, ?int $branchId = null): string
    {
        $date = $date ?: now();
        $branchId = $branchId !== null ? $branchId : BranchContext::currentOrDefaultId();
        $documentType = $this->numberingDocumentType($taxDocumentType);

        $configuredNumber = $this->documentNumbering->nextConfiguredNumber(
            $documentType,
            $date,
            CompanyContext::currentId(),
            $branchId
        );

        if ($configuredNumber) {
            return $configuredNumber;
        }

        return $this->fallbackNumber($documentType, $date, $branchId);
    }

    private function numberingDocumentType(string $taxDocumentType): string
    {
        if ($taxDocumentType === FinanceTaxDocument::TYPE_OUTPUT_VAT) {
            return 'tax_output_vat';
        }

        if ($taxDocumentType === FinanceTaxDocument::TYPE_INPUT_VAT) {
            return 'tax_input_vat';
        }

        return 'tax_withholding';
    }

    private function fallbackNumber(string $documentType, \DateTimeInterface $date, ?int $branchId = null): string
    {
        $sequenceDate = $date->format('Ym');
        $scopeKey = $branchId === null ? 'company' : ('branch:' . $branchId);
        $prefixMap = [
            'tax_output_vat' => 'FPK-' . $date->format('Ym'),
            'tax_input_vat' => 'FPM-' . $date->format('Ym'),
            'tax_withholding' => 'BUPOT-' . $date->format('Ym'),
        ];
        $prefix = isset($prefixMap[$documentType]) ? $prefixMap[$documentType] : ('TAX-' . $date->format('Ym'));

        return DB::transaction(function () use ($documentType, $date, $branchId, $sequenceDate, $scopeKey, $prefix) {
            $query = DB::table('finance_tax_document_sequences')
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('sequence_scope_key', $scopeKey)
                ->where('document_type', $documentType)
                ->where('sequence_date', $sequenceDate);

            if ($branchId === null) {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }

            $row = $query->lockForUpdate()->first();

            if (!$row) {
                DB::table('finance_tax_document_sequences')->insert([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => $branchId,
                    'sequence_scope_key' => $scopeKey,
                    'document_type' => $documentType,
                    'sequence_date' => $sequenceDate,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $row = $query->lockForUpdate()->first();
            }

            $nextNumber = ((int) $row->last_number) + 1;

            DB::table('finance_tax_document_sequences')
                ->where('id', $row->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return sprintf('%s-%04d', $prefix, $nextNumber);
        });
    }
}
