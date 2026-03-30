<?php

namespace App\Modules\Sales\Http\Requests\Concerns;

use App\Modules\Products\Models\ProductVariant;
use App\Support\CurrencySettingsResolver;
use App\Support\TenantContext;

trait NormalizesSalePayload
{
    protected function normalizeSalePayload(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $sellableKey = trim((string) ($item['sellable_key'] ?? ''));
                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                if ($sellableKey !== '' && str_contains($sellableKey, ':')) {
                    [$type, $id] = explode(':', $sellableKey, 2);
                    if ($type === 'product') {
                        $productId = (int) $id;
                        $variantId = null;
                    }

                    if ($type === 'variant') {
                        $variantId = (int) $id;
                    }
                }

                return [
                    'sellable_key' => $sellableKey,
                    'product_id' => $productId ? (int) $productId : null,
                    'product_variant_id' => $variantId ? (int) $variantId : null,
                    'qty' => ($item['qty'] ?? '') === '' ? null : $item['qty'],
                    'unit_price' => ($item['unit_price'] ?? '') === '' ? null : $item['unit_price'],
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
                $variantModel = ProductVariant::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->find($item['product_variant_id']);
                if ($variantModel) {
                    $items[$index]['product_id'] = (int) $variantModel->product_id;
                }
            }
        }

        $this->merge([
            'contact_id' => $this->filled('contact_id') ? (int) $this->input('contact_id') : null,
            'items' => $items,
            'transaction_date' => $this->filled('transaction_date') ? $this->input('transaction_date') : now()->format('Y-m-d\TH:i'),
            'payment_status' => $this->filled('payment_status') ? $this->input('payment_status') : 'unpaid',
            'source' => $this->filled('source') ? $this->input('source') : 'manual',
            'currency_code' => $this->filled('currency_code') ? strtoupper((string) $this->input('currency_code')) : app(CurrencySettingsResolver::class)->defaultCurrency(),
        ]);
    }
}
