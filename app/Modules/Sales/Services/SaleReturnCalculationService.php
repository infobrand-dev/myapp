<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Validation\ValidationException;

class SaleReturnCalculationService
{
    public function calculate(Sale $sale, array $requestedItems, array $returnableMap): array
    {
        $indexedSaleItems = $sale->items->keyBy('id');
        $items = [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;
        $lineNo = 1;

        foreach ($requestedItems as $row) {
            $saleItemId = (int) ($row['sale_item_id'] ?? 0);
            $qty = round((float) ($row['qty_returned'] ?? 0), 4);

            if ($saleItemId <= 0 || $qty <= 0) {
                continue;
            }

            /** @var SaleItem|null $saleItem */
            $saleItem = $indexedSaleItems->get($saleItemId);
            if (!$saleItem) {
                throw ValidationException::withMessages([
                    'items' => 'Ada item return yang tidak cocok dengan sale asal.',
                ]);
            }

            $returnable = (float) ($returnableMap[$saleItemId]['remaining_qty'] ?? 0);
            if ($qty > $returnable) {
                throw ValidationException::withMessages([
                    'items' => 'Qty return melebihi qty yang masih dapat diretur.',
                ]);
            }

            $saleQty = max(0.0001, round((float) $saleItem->qty, 4));
            $subtotalPart = round(((float) $saleItem->line_subtotal / $saleQty) * $qty, 2);
            $discountPart = round(((float) $saleItem->discount_total / $saleQty) * $qty, 2);
            $taxPart = round(((float) $saleItem->tax_total / $saleQty) * $qty, 2);
            $lineTotal = round($subtotalPart - $discountPart + $taxPart, 2);

            $items[] = [
                'sale_item_id' => $saleItem->id,
                'line_no' => $lineNo,
                'product_id' => $saleItem->product_id,
                'product_variant_id' => $saleItem->product_variant_id,
                'product_name_snapshot' => $saleItem->product_name_snapshot,
                'variant_name_snapshot' => $saleItem->variant_name_snapshot,
                'sku_snapshot' => $saleItem->sku_snapshot,
                'barcode_snapshot' => $saleItem->barcode_snapshot,
                'unit_snapshot' => $saleItem->unit_snapshot,
                'product_snapshot' => $saleItem->product_snapshot,
                'notes' => $row['notes'] ?? null,
                'sale_qty_snapshot' => $saleItem->qty,
                'previous_returned_qty_snapshot' => $returnableMap[$saleItemId]['returned_qty'] ?? 0,
                'qty_returned' => $qty,
                'unit_price' => $saleItem->unit_price,
                'line_subtotal' => $subtotalPart,
                'discount_total' => $discountPart,
                'tax_total' => $taxPart,
                'line_total' => $lineTotal,
                'pricing_snapshot' => array_merge($saleItem->pricing_snapshot ?? [], [
                    'returnable_qty_before' => $returnable,
                    'sale_line_total' => (float) $saleItem->line_total,
                ]),
                'sort_order' => $lineNo,
            ];

            $subtotal += $subtotalPart;
            $discountTotal += $discountPart;
            $taxTotal += $taxPart;
            $grandTotal += $lineTotal;
            $lineNo++;
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item return dengan qty lebih dari nol wajib diisi.',
            ]);
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($grandTotal, 2),
            'totals_snapshot' => [
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($grandTotal, 2),
                'line_count' => count($items),
                'calculated_at' => now()->toDateTimeString(),
            ],
        ];
    }
}
