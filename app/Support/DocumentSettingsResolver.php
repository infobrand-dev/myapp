<?php

namespace App\Support;

use App\Models\DocumentSetting;

class DocumentSettingsResolver
{
    public function forScope(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        if (!$companyId) {
            return $this->defaults();
        }

        $companySetting = DocumentSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNull('branch_id')
            ->first();

        $branchSetting = $branchId
            ? DocumentSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->first()
            : null;

        return [
            'company' => $companySetting,
            'branch' => $branchSetting,
            'document_header' => $branchSetting->document_header ?? $companySetting->document_header ?? null,
            'document_footer' => $branchSetting->document_footer ?? $companySetting->document_footer ?? null,
            'receipt_footer' => $branchSetting->receipt_footer ?? $companySetting->receipt_footer ?? null,
            'invoice_prefix' => $branchSetting->invoice_prefix ?? $companySetting->invoice_prefix ?? null,
            'invoice_padding' => (int) ($branchSetting->invoice_padding ?? $companySetting->invoice_padding ?? 5),
            'invoice_reset_period' => $branchSetting->invoice_reset_period ?? $companySetting->invoice_reset_period ?? 'never',
        ];
    }

    private function defaults(): array
    {
        return [
            'company' => null,
            'branch' => null,
            'document_header' => null,
            'document_footer' => null,
            'receipt_footer' => null,
            'invoice_prefix' => null,
            'invoice_padding' => 5,
            'invoice_reset_period' => 'never',
        ];
    }
}
