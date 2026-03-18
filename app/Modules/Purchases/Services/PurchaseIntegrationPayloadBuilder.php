<?php

namespace App\Modules\Purchases\Services;

use App\Modules\Purchases\Models\Purchase;

class PurchaseIntegrationPayloadBuilder
{
    public function build(Purchase $purchase): array
    {
        $purchase->loadMissing(['items', 'receipts.items', 'paymentAllocations.payment']);

        return [
            'purchase_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'status' => $purchase->status,
            'payment_status' => $purchase->payment_status,
            'purchase_date' => optional($purchase->purchase_date)->toDateTimeString(),
            'supplier' => $purchase->supplier_snapshot,
            'totals' => [
                'subtotal' => (float) $purchase->subtotal,
                'discount_total' => (float) $purchase->discount_total,
                'tax_total' => (float) $purchase->tax_total,
                'grand_total' => (float) $purchase->grand_total,
                'paid_total' => (float) $purchase->paid_total,
                'balance_due' => (float) $purchase->balance_due,
            ],
            'items' => $purchase->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'qty' => (float) $item->qty,
                'qty_received' => (float) $item->qty_received,
                'unit_cost' => (float) $item->unit_cost,
                'line_total' => (float) $item->line_total,
                'snapshot' => $item->product_snapshot,
            ])->all(),
        ];
    }
}
