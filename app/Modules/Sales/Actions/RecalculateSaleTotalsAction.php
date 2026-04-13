<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Validation\ValidationException;

class RecalculateSaleTotalsAction
{
    private $snapshotService;

    public function __construct(SaleSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    public function execute(array $data): array
    {
        $normalizedItems = collect($data['items'] ?? [])
            ->filter(fn ($item) => is_array($item) && !empty($item['product_id']))
            ->values();

        if ($normalizedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item transaksi wajib diisi.',
            ]);
        }

        $products = Product::query()
            ->with('unit')
            ->whereIn('id', $normalizedItems->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $variantIds = $normalizedItems->pluck('product_variant_id')->filter()->unique()->all();
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $subtotal = 0.0;
        $itemDiscountTotal = 0.0;
        $itemTaxTotal = 0.0;

        $items = $normalizedItems->map(function (array $item, int $index) use (&$subtotal, &$itemDiscountTotal, &$itemTaxTotal, $products, $variants) {
            $product = $products->get((int) $item['product_id']);
            $variant = !empty($item['product_variant_id']) ? $variants->get((int) $item['product_variant_id']) : null;

            if (!$product) {
                throw ValidationException::withMessages([
                    "items.{$index}.sellable_key" => 'Produk tidak ditemukan.',
                ]);
            }

            if ($variant && (int) $variant->product_id !== (int) $product->id) {
                throw ValidationException::withMessages([
                    "items.{$index}.sellable_key" => 'Variant tidak cocok dengan produk induknya.',
                ]);
            }

            $qty = round((float) $item['qty'], 4);
            $unitPrice = round((float) $item['unit_price'], 2);
            $itemDiscount = round((float) ($item['discount_total'] ?? 0), 2);
            $itemTax = round((float) ($item['tax_total'] ?? 0), 2);
            $lineSubtotal = round($qty * $unitPrice, 2);
            $lineTotal = round($lineSubtotal - $itemDiscount + $itemTax, 2);

            if ($lineTotal < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_total" => 'Total line tidak boleh negatif.',
                ]);
            }

            $snapshot = $this->snapshotService->productSnapshot($product, $variant);

            $subtotal += $lineSubtotal;
            $itemDiscountTotal += $itemDiscount;
            $itemTaxTotal += $itemTax;

            return [
                'line_no' => $index + 1,
                'product_id' => $product->id,
                'product_variant_id' => $variant ? $variant->id : null,
                'product_name_snapshot' => $snapshot['product_name'],
                'variant_name_snapshot' => $snapshot['variant_name'],
                'sku_snapshot' => $snapshot['sku'],
                'barcode_snapshot' => $snapshot['barcode'],
                'unit_snapshot' => $snapshot['unit'],
                'product_snapshot' => $snapshot['payload'],
                'notes' => $item['notes'] ?? null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'discount_total' => $itemDiscount,
                'tax_total' => $itemTax,
                'line_total' => $lineTotal,
                'pricing_snapshot' => [
                    'line_subtotal' => $lineSubtotal,
                    'discount_total' => $itemDiscount,
                    'tax_total' => $itemTax,
                    'line_total' => $lineTotal,
                ],
                'sort_order' => $index,
            ];
        })->all();

        $headerDiscountTotal = round((float) ($data['header_discount_total'] ?? 0), 2);
        $headerTaxTotal = round((float) ($data['header_tax_total'] ?? 0), 2);
        $discountTotal = round($itemDiscountTotal + $headerDiscountTotal, 2);
        $taxTotal = round($itemTaxTotal + $headerTaxTotal, 2);
        $grandTotal = round($subtotal - $discountTotal + $taxTotal, 2);
        if ($grandTotal < 0) {
            throw ValidationException::withMessages([
                'header_discount_total' => 'Grand total tidak boleh negatif.',
            ]);
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'totals_snapshot' => [
                'subtotal' => round($subtotal, 2),
                'item_discount_total' => round($itemDiscountTotal, 2),
                'header_discount_total' => $headerDiscountTotal,
                'discount_total' => $discountTotal,
                'item_tax_total' => round($itemTaxTotal, 2),
                'header_tax_total' => $headerTaxTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ],
        ];
    }
}
