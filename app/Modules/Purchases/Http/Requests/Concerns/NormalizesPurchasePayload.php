<?php

namespace App\Modules\Purchases\Http\Requests\Concerns;

use App\Modules\Products\Models\ProductVariant;
use App\Support\CurrencySettingsResolver;
use App\Support\TenantContext;

trait NormalizesPurchasePayload
{
    protected function normalizePurchasePayload(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;
                $key = trim((string) ($item['purchasable_key'] ?? ''));

                if ($key !== '' && str_contains($key, ':')) {
                    [$type, $id] = explode(':', $key, 2);
                    if ($type === 'product') {
                        $productId = (int) $id;
                        $variantId = null;
                    }

                    if ($type === 'variant') {
                        $variantId = (int) $id;
                    }
                }

                return [
                    'purchasable_key' => $key,
                    'product_id' => $productId ? (int) $productId : null,
                    'product_variant_id' => $variantId ? (int) $variantId : null,
                    'qty' => ($item['qty'] ?? '') === '' ? null : $item['qty'],
                    'unit_cost' => ($item['unit_cost'] ?? '') === '' ? null : $item['unit_cost'],
                    'discount_total' => ($item['discount_total'] ?? '') === '' ? 0 : $item['discount_total'],
                    'tax_total' => ($item['tax_total'] ?? '') === '' ? 0 : $item['tax_total'],
                    'notes' => isset($item['notes']) && trim((string) $item['notes']) !== '' ? trim((string) $item['notes']) : null,
                ];
            })
            ->filter(fn ($item) => $item['product_id'] || $item['product_variant_id'])
            ->values()
            ->all();

        foreach ($items as $index => $item) {
            if (!empty($item['product_variant_id']) && empty($item['product_id'])) {
                $variant = ProductVariant::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->find($item['product_variant_id']);
                if ($variant) {
                    $items[$index]['product_id'] = (int) $variant->product_id;
                }
            }
        }

        $this->merge([
            'contact_id' => $this->filled('contact_id') ? (int) $this->input('contact_id') : null,
            'tax_rate_id' => $this->filled('tax_rate_id') ? (int) $this->input('tax_rate_id') : null,
            'items' => $items,
            'purchase_date' => $this->filled('purchase_date') ? $this->input('purchase_date') : now()->format('Y-m-d\TH:i'),
            'due_date' => $this->filled('due_date') ? $this->input('due_date') : null,
            'expected_receive_date' => $this->filled('expected_receive_date') ? $this->input('expected_receive_date') : null,
            'supplier_bill_received_at' => $this->filled('supplier_bill_received_at') ? $this->input('supplier_bill_received_at') : null,
            'landed_cost_total' => ($this->input('landed_cost_total', '') === '' ? 0 : $this->input('landed_cost_total', 0)),
            'currency_code' => $this->filled('currency_code') ? strtoupper((string) $this->input('currency_code')) : app(CurrencySettingsResolver::class)->defaultCurrency(),
        ]);
    }
}
