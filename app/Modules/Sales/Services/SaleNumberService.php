<?php

namespace App\Modules\Sales\Services;

use App\Models\DocumentSetting;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class SaleNumberService
{
    public function generate(?\DateTimeInterface $date = null, ?int $companyId = null, ?int $branchId = null): string
    {
        $date = $date ?: now();
        $companyId = $companyId ?? CompanyContext::currentId();
        $branchId = $branchId ?? BranchContext::currentId();

        if ($companyId) {
            $documentSetting = $this->lockDocumentSetting($companyId, $branchId);

            if ($documentSetting) {
                return $this->nextNumberFromDocumentSetting($documentSetting, $date);
            }
        }

        return $this->nextSequenceFallback($date);
    }

    private function lockDocumentSetting(int $companyId, ?int $branchId = null): ?DocumentSetting
    {
        $tenantId = TenantContext::currentId();

        if ($branchId) {
            $branchSetting = DocumentSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($branchSetting) {
                return $branchSetting;
            }
        }

        return DocumentSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNull('branch_id')
            ->lockForUpdate()
            ->first();
    }

    private function nextNumberFromDocumentSetting(DocumentSetting $documentSetting, \DateTimeInterface $date): string
    {
        $resetPeriod = $documentSetting->invoice_reset_period ?: 'never';
        $currentPeriod = null;

        if ($resetPeriod === 'monthly') {
            $currentPeriod = $date->format('Y-m');
        } elseif ($resetPeriod === 'yearly') {
            $currentPeriod = $date->format('Y');
        }

        $nextNumber = (int) ($documentSetting->invoice_next_number ?: 1);

        if ($currentPeriod !== null && $documentSetting->invoice_last_period !== $currentPeriod) {
            $nextNumber = 1;
        }

        $padding = max(1, (int) ($documentSetting->invoice_padding ?: 4));
        $prefix = $documentSetting->invoice_prefix ?: 'SAL-' . $date->format('Ymd');

        $documentSetting->forceFill([
            'invoice_next_number' => $nextNumber + 1,
            'invoice_last_period' => $currentPeriod,
        ])->save();

        return $prefix . '-' . str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
    }

    private function nextSequenceFallback(\DateTimeInterface $date): string
    {
        $sequenceDate = $date->format('Ymd');
        $prefix = 'SAL-' . $sequenceDate;

        $row = DB::table('sale_sequences')
            ->where('sequence_date', $sequenceDate)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            DB::table('sale_sequences')->insert([
                'sequence_date' => $sequenceDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('sale_sequences')
                ->where('sequence_date', $sequenceDate)
                ->lockForUpdate()
                ->first();
        }

        $nextSequence = ((int) $row->last_number) + 1;

        DB::table('sale_sequences')
            ->where('id', $row->id)
            ->update([
                'last_number' => $nextSequence,
                'updated_at' => now(),
            ]);

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }
}
