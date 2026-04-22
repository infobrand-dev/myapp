<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Models\FinanceTaxRate;
use App\Modules\Finance\Services\TransactionTaxService;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Validation\ValidationException;

class RecalculateSaleTotalsAction
{
    private $snapshotService;
    private $transactionTaxService;

    public function __construct(SaleSnapshotService $snapshotService, TransactionTaxService $transactionTaxService)
    {
        $this->snapshotService = $snapshotService;
        $this->transactionTaxService = $transactionTaxService;
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
        $taxRate = $this->transactionTaxService->resolve(
            !empty($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
            FinanceTaxRate::TYPE_SALES
        );
        $usesTaxMaster = $taxRate !== null;

        $items = $normalizedItems->map(function (array $item, int $index) use (&$subtotal, &$itemDiscountTotal, &$itemTaxTotal, $products, $variants, $usesTaxMaster) {
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
            $itemTax = $usesTaxMaster ? 0.0 : round((float) ($item['tax_total'] ?? 0), 2);
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
        $headerTaxCalculation = $usesTaxMaster
            ? $this->transactionTaxService->calculate(
                round($subtotal - $itemDiscountTotal - $headerDiscountTotal, 2),
                $taxRate,
                'tax_rate_id'
            )
            : [
                'tax_total' => round((float) ($data['header_tax_total'] ?? 0), 2),
                'taxable_base' => round($subtotal - $itemDiscountTotal - $headerDiscountTotal, 2),
                'is_auto_applied' => false,
                'snapshot' => null,
            ];
        $headerTaxTotal = round((float) $headerTaxCalculation['tax_total'], 2);
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
                'tax_rate_id' => $taxRate?->id,
                'tax_auto_applied' => (bool) $headerTaxCalculation['is_auto_applied'],
                'tax_snapshot' => $headerTaxCalculation['snapshot'],
            ],
            'tax_context' => [
                'tax_rate_id' => $taxRate?->id,
                'header_tax_total' => $headerTaxTotal,
                'tax_total' => $taxTotal,
                'is_auto_applied' => (bool) $headerTaxCalculation['is_auto_applied'],
                'tax_snapshot' => $headerTaxCalculation['snapshot'],
            ],
        ];
    }
}
