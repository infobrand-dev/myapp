<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\FinanceTaxDocument;
use App\Modules\Finance\Models\FinanceTaxRate;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TaxDocumentSyncService
{
    public function syncFromSource($sourceDocument, ?User $actor = null): ?FinanceTaxDocument
    {
        if ($sourceDocument instanceof Sale) {
            return $this->syncSale($sourceDocument, $actor);
        }

        if ($sourceDocument instanceof Purchase) {
            return $this->syncPurchase($sourceDocument, $actor);
        }

        return null;
    }

    private function syncSale(Sale $sale, ?User $actor = null): ?FinanceTaxDocument
    {
        if ((float) $sale->tax_total <= 0) {
            return null;
        }

        $snapshot = $this->taxSnapshot($sale->meta);
        $taxRate = $this->resolveTaxRate($snapshot);
        $transactionDate = $sale->transaction_date ? Carbon::parse($sale->transaction_date) : now();

        return $this->upsertDocument($sale, FinanceTaxDocument::TYPE_OUTPUT_VAT, [
            'contact_id' => $sale->contact_id,
            'finance_tax_rate_id' => $taxRate ? $taxRate->id : null,
            'document_status' => FinanceTaxDocument::STATUS_DRAFT,
            'external_document_number' => $sale->sale_number,
            'transaction_date' => $transactionDate->toDateString(),
            'document_date' => $transactionDate->toDateString(),
            'tax_period_month' => (int) $transactionDate->month,
            'tax_period_year' => (int) $transactionDate->year,
            'counterparty_name_snapshot' => $sale->customer_name_snapshot,
            'counterparty_tax_id_snapshot' => $this->nullableString(data_get($sale->customer_snapshot, 'vat')),
            'counterparty_tax_name_snapshot' => $this->nullableString(
                data_get($sale->customer_snapshot, 'tax_name') ?: $sale->customer_name_snapshot
            ),
            'counterparty_tax_address_snapshot' => $this->nullableString(
                data_get($sale->customer_snapshot, 'tax_address') ?: $sale->customer_address_snapshot
            ),
            'taxable_base' => $this->resolveTaxableBase((float) $sale->subtotal, (float) $sale->discount_total, $snapshot),
            'tax_amount' => round((float) $sale->tax_total, 2),
            'withheld_amount' => 0,
            'currency_code' => $sale->currency_code ?: 'IDR',
            'reference_note' => 'Auto-generated from sale ' . $sale->sale_number,
            'meta' => $this->documentMeta('sales', $sale->sale_number, $taxRate, $snapshot),
        ], $actor);
    }

    private function syncPurchase(Purchase $purchase, ?User $actor = null): ?FinanceTaxDocument
    {
        if ((float) $purchase->tax_total <= 0) {
            return null;
        }

        $snapshot = $this->taxSnapshot($purchase->meta);
        $taxRate = $this->resolveTaxRate($snapshot);
        $transactionDate = $purchase->purchase_date ? Carbon::parse($purchase->purchase_date) : now();

        return $this->upsertDocument($purchase, FinanceTaxDocument::TYPE_INPUT_VAT, [
            'contact_id' => $purchase->contact_id,
            'finance_tax_rate_id' => $taxRate ? $taxRate->id : null,
            'document_status' => FinanceTaxDocument::STATUS_DRAFT,
            'external_document_number' => $purchase->purchase_number,
            'transaction_date' => $transactionDate->toDateString(),
            'document_date' => $transactionDate->toDateString(),
            'tax_period_month' => (int) $transactionDate->month,
            'tax_period_year' => (int) $transactionDate->year,
            'counterparty_name_snapshot' => $purchase->supplier_name_snapshot,
            'counterparty_tax_id_snapshot' => $this->nullableString(data_get($purchase->supplier_snapshot, 'vat')),
            'counterparty_tax_name_snapshot' => $this->nullableString(
                data_get($purchase->supplier_snapshot, 'tax_name') ?: $purchase->supplier_name_snapshot
            ),
            'counterparty_tax_address_snapshot' => $this->nullableString(
                data_get($purchase->supplier_snapshot, 'tax_address') ?: $purchase->supplier_address_snapshot
            ),
            'taxable_base' => $this->resolveTaxableBase((float) $purchase->subtotal, (float) $purchase->discount_total, $snapshot),
            'tax_amount' => round((float) $purchase->tax_total, 2),
            'withheld_amount' => 0,
            'currency_code' => $purchase->currency_code ?: 'IDR',
            'reference_note' => 'Auto-generated from purchase ' . $purchase->purchase_number,
            'meta' => $this->documentMeta('purchases', $purchase->purchase_number, $taxRate, $snapshot),
        ], $actor);
    }

    private function upsertDocument(Model $sourceDocument, string $documentType, array $attributes, ?User $actor = null): FinanceTaxDocument
    {
        $taxDocument = FinanceTaxDocument::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('source_document_type', get_class($sourceDocument))
            ->where('source_document_id', $sourceDocument->getKey())
            ->where('document_type', $documentType)
            ->first();

        if ($taxDocument) {
            $taxDocument->fill($attributes);
            $taxDocument->updated_by = $actor ? $actor->id : null;
            $taxDocument->save();

            return $taxDocument;
        }

        return FinanceTaxDocument::query()->create($attributes + [
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => data_get($sourceDocument, 'branch_id'),
            'source_document_type' => get_class($sourceDocument),
            'source_document_id' => $sourceDocument->getKey(),
            'created_by' => $actor ? $actor->id : null,
            'updated_by' => $actor ? $actor->id : null,
        ]);
    }

    private function resolveTaxRate(array $snapshot): ?FinanceTaxRate
    {
        $taxRateId = (int) ($snapshot['tax_rate_id'] ?? 0);
        if ($taxRateId > 0) {
            $taxRate = FinanceTaxRate::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($taxRateId);

            if ($taxRate) {
                return $taxRate;
            }
        }

        $code = trim((string) ($snapshot['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        return FinanceTaxRate::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('code', $code)
            ->first();
    }

    private function taxSnapshot($meta): array
    {
        $snapshot = data_get($meta, 'tax.tax_snapshot');
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $taxData = data_get($meta, 'tax');

        return is_array($taxData) ? $taxData : [];
    }

    private function resolveTaxableBase(float $subtotal, float $discountTotal, array $snapshot): float
    {
        if (isset($snapshot['taxable_base'])) {
            return round((float) $snapshot['taxable_base'], 2);
        }

        return round(max(0, $subtotal - $discountTotal), 2);
    }

    private function documentMeta(string $sourceModule, ?string $sourceNumber, ?FinanceTaxRate $taxRate, array $snapshot): array
    {
        return [
            'source_module' => $sourceModule,
            'source_number' => $sourceNumber,
            'tax_scope' => $taxRate ? $taxRate->tax_scope : ($snapshot['tax_scope'] ?? null),
            'legal_basis' => $taxRate ? $taxRate->legal_basis : null,
            'document_label' => $taxRate ? $taxRate->document_label : null,
            'requires_tax_number' => $taxRate ? (bool) $taxRate->requires_tax_number : false,
            'requires_counterparty_tax_id' => $taxRate ? (bool) $taxRate->requires_counterparty_tax_id : false,
            'tax_snapshot' => $snapshot,
            'auto_generated' => true,
        ];
    }

    private function nullableString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
