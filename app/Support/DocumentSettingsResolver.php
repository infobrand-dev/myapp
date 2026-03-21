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

    public function previewForSettingsPage(int $tenantId, ?int $companyId, ?int $branchId = null): array
    {
        $resolved = $this->forScope($tenantId, $companyId, $branchId);
        $companySetting = $resolved['company'];
        $branchSetting = $resolved['branch'];
        $effectiveSetting = $branchSetting ?: $companySetting;
        $branchSelected = $branchId !== null;

        return [
            'company_sale_number' => $this->previewNumber($companySetting, 'SAL'),
            'branch_sale_number' => $branchSetting ? $this->previewNumber($branchSetting, 'SAL') : null,
            'effective_sale_number' => $this->previewNumber($effectiveSetting, 'SAL'),
            'effective_header' => $resolved['document_header'],
            'effective_footer' => $resolved['document_footer'],
            'effective_receipt_footer' => $resolved['receipt_footer'],
            'effective_source' => $branchSetting ? 'Branch override' : 'Company default',
            'effective_reset_period' => $resolved['invoice_reset_period'],
            'has_branch_override' => $branchSetting !== null,
            'branch_selected' => $branchSelected,
            'effective_applies_to' => 'Sales invoice dan POS receipt',
            'pending_applies_to' => 'Payment numbering dan dokumen lain masih memakai generator masing-masing',
        ];
    }

    private function previewNumber(?DocumentSetting $documentSetting, string $fallbackPrefix): string
    {
        if (!$documentSetting) {
            return $fallbackPrefix . '-' . now()->format('Ymd') . '-0001';
        }

        $prefix = $documentSetting->invoice_prefix ?: $fallbackPrefix . '-' . now()->format('Ymd');
        $padding = max(1, (int) ($documentSetting->invoice_padding ?: 5));
        $number = max(1, (int) ($documentSetting->invoice_next_number ?: 1));

        return $prefix . '-' . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }
}
