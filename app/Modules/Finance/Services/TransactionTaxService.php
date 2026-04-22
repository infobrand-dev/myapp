<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FinanceTaxRate;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TransactionTaxService
{
    public function options(string $taxType): Collection
    {
        return FinanceTaxRate::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('tax_type', $taxType)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'tax_type',
                'rate_percent',
                'is_inclusive',
                'sales_account_code',
                'purchase_account_code',
            ]);
    }

    public function resolve(?int $taxRateId, string $taxType): ?FinanceTaxRate
    {
        if (!$taxRateId) {
            return null;
        }

        return FinanceTaxRate::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('tax_type', $taxType)
            ->where('is_active', true)
            ->find($taxRateId);
    }

    public function calculate(float $taxableBase, ?FinanceTaxRate $taxRate, string $errorKey = 'tax_rate_id'): array
    {
        $taxableBase = round(max(0, $taxableBase), 2);

        if (!$taxRate) {
            return [
                'tax_total' => 0.0,
                'taxable_base' => $taxableBase,
                'is_auto_applied' => false,
                'snapshot' => null,
            ];
        }

        if ((bool) $taxRate->is_inclusive) {
            throw ValidationException::withMessages([
                $errorKey => 'Tax master inclusive belum didukung untuk auto-apply draft. Pakai tax exclusive atau isi nominal pajak manual.',
            ]);
        }

        $ratePercent = round((float) $taxRate->rate_percent, 4);
        $taxTotal = round($taxableBase * ($ratePercent / 100), 2);

        return [
            'tax_total' => $taxTotal,
            'taxable_base' => $taxableBase,
            'is_auto_applied' => true,
            'snapshot' => $this->snapshot($taxRate, $taxTotal, $taxableBase),
        ];
    }

    public function snapshot(FinanceTaxRate $taxRate, float $taxTotal, float $taxableBase): array
    {
        return [
            'tax_rate_id' => (int) $taxRate->id,
            'code' => $taxRate->code,
            'name' => $taxRate->name,
            'tax_type' => $taxRate->tax_type,
            'rate_percent' => round((float) $taxRate->rate_percent, 4),
            'is_inclusive' => (bool) $taxRate->is_inclusive,
            'sales_account_code' => $taxRate->sales_account_code,
            'purchase_account_code' => $taxRate->purchase_account_code,
            'taxable_base' => round($taxableBase, 2),
            'tax_total' => round($taxTotal, 2),
            'auto_applied' => true,
        ];
    }
}
