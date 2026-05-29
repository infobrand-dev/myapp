<?php

namespace App\Support\Commerce;

use App\Modules\Sales\Models\Sale;

class CommerceSalePricingService
{
    public function applyShippingCharge(Sale $sale, ?array $selectedRate): Sale
    {
        $shippingTotal = round((float) data_get($selectedRate, 'price', 0), 2);
        $baseGrandTotal = round((float) data_get($sale->totals_snapshot, 'grand_total', $sale->grand_total), 2);
        $baseSubtotal = round((float) data_get($sale->totals_snapshot, 'subtotal', $sale->subtotal), 2);
        $discountTotal = round((float) data_get($sale->totals_snapshot, 'discount_total', $sale->discount_total), 2);
        $taxTotal = round((float) data_get($sale->totals_snapshot, 'tax_total', $sale->tax_total), 2);

        $grandTotal = round($baseGrandTotal + $shippingTotal, 2);
        $meta = is_array($sale->meta) ? $sale->meta : [];
        $totalsSnapshot = is_array($sale->totals_snapshot) ? $sale->totals_snapshot : [];

        data_set($meta, 'commerce.shipping.amount', $shippingTotal);
        data_set($meta, 'commerce.shipping.currency', (string) data_get($selectedRate, 'currency', $sale->currency_code ?: 'IDR'));

        $totalsSnapshot = array_merge($totalsSnapshot, [
            'subtotal' => $baseSubtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'shipping_total' => $shippingTotal,
            'grand_total' => $grandTotal,
        ]);

        $sale->update([
            'grand_total' => $grandTotal,
            'balance_due' => round($grandTotal - (float) $sale->paid_total, 2),
            'meta' => $meta,
            'totals_snapshot' => $totalsSnapshot,
        ]);

        return $sale->fresh();
    }

    public function shippingTotal(?Sale $sale): float
    {
        return round((float) data_get($sale?->totals_snapshot, 'shipping_total', data_get($sale?->meta, 'commerce.shipping.amount', 0)), 2);
    }
}
