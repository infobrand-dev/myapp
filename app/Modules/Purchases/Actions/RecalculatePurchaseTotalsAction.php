<?php

namespace App\Modules\Purchases\Actions;

use App\Modules\Finance\Models\FinanceTaxRate;
use App\Modules\Finance\Services\TransactionTaxService;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RecalculatePurchaseTotalsAction
{
    private $snapshotService;
    private $transactionTaxService;

    public function __construct(PurchaseSnapshotService $snapshotService, TransactionTaxService $transactionTaxService)
    {
        $this->snapshotService = $snapshotService;
        $this->transactionTaxService = $transactionTaxService;
    }

    public function execute(array $data): array
    {
        $rows = collect($data['items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item purchase wajib diisi.',
            ]);
        }

        $taxRate = $this->transactionTaxService->resolve(
            !empty($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
            FinanceTaxRate::TYPE_PURCHASE
        );
        $usesTaxMaster = $taxRate !== null;

        $items = $rows->map(function (array $item, int $index) use ($usesTaxMaster) {
            $product = Product::query()
                ->with('unit')
                ->where('tenant_id', TenantContext::currentId())
                ->find($item['product_id'] ?? null);
            if (!$product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Produk tidak valid.',
                ]);
            }

            $variant = null;
            if (!empty($item['product_variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->find($item['product_variant_id']);
                if (!$variant || (int) $variant->product_id !== (int) $product->id) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_variant_id" => 'Variant tidak valid untuk produk ini.',
                    ]);
                }
            }

            $qty = round((float) ($item['qty'] ?? 0), 4);
            $unitCost = round((float) ($item['unit_cost'] ?? 0), 2);
            $discountTotal = round(max(0, (float) ($item['discount_total'] ?? 0)), 2);
            $taxTotal = $usesTaxMaster ? 0.0 : round(max(0, (float) ($item['tax_total'] ?? 0)), 2);

            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.qty" => 'Quantity pembelian harus lebih besar dari nol.',
                ]);
            }

            if ($unitCost < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_cost" => 'Harga beli tidak boleh negatif.',
                ]);
            }

            $lineSubtotal = round($qty * $unitCost, 2);
            $lineTotal = round($lineSubtotal - $discountTotal + $taxTotal, 2);

            if ($lineTotal < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_total" => 'Total item tidak boleh negatif.',
                ]);
            }

            $snapshot = $this->snapshotService->productSnapshot($product, $variant);

            return [
                'line_no' => $index + 1,
                'product_id' => $product->id,
                'product_variant_id' => $variant ? $variant->id : null,
                'product_name_snapshot' => $snapshot['product_name'],
                'variant_name_snapshot' => $snapshot['variant_name'],
                'sku_snapshot' => $snapshot['sku'],
                'unit_snapshot' => $snapshot['unit'],
                'product_snapshot' => $snapshot['payload'],
                'notes' => $item['notes'] ?? null,
                'qty' => $qty,
                'qty_received' => round((float) ($item['qty_received'] ?? 0), 4),
                'unit_cost' => $unitCost,
                'line_subtotal' => $lineSubtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'line_total' => $lineTotal,
                'pricing_snapshot' => [
                    'base_cost_price' => (float) ($variant ? $variant->cost_price : $product->cost_price),
                ],
                'sort_order' => $index,
            ];
        });

        return $this->summaries($items, $data, $taxRate);
    }

    private function summaries(Collection $items, array $data, ?FinanceTaxRate $taxRate = null): array
    {
        $subtotal = round($items->sum('line_subtotal'), 2);
        $discountTotal = round($items->sum('discount_total'), 2);
        $taxCalculation = $taxRate
            ? $this->transactionTaxService->calculate(
                round($subtotal - $discountTotal, 2),
                $taxRate,
                'tax_rate_id'
            )
            : [
                'tax_total' => round($items->sum('tax_total'), 2),
                'taxable_base' => round($subtotal - $discountTotal, 2),
                'is_auto_applied' => false,
                'snapshot' => null,
            ];
        $taxTotal = round((float) $taxCalculation['tax_total'], 2);
        $landedCostTotal = round(max(0, (float) ($data['landed_cost_total'] ?? 0)), 2);
        $grandTotal = $taxRate
            ? round($subtotal - $discountTotal + $taxTotal + $landedCostTotal, 2)
            : round($items->sum('line_total') + $landedCostTotal, 2);

        if ($grandTotal < 0) {
            throw ValidationException::withMessages([
                'grand_total' => 'Grand total tidak boleh negatif.',
            ]);
        }

        return [
            'items' => $items->all(),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'landed_cost_total' => $landedCostTotal,
            'grand_total' => $grandTotal,
            'totals_snapshot' => [
                'item_count' => $items->count(),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'landed_cost_total' => $landedCostTotal,
                'grand_total' => $grandTotal,
                'tax_rate_id' => $taxRate?->id,
                'tax_auto_applied' => (bool) $taxCalculation['is_auto_applied'],
                'tax_snapshot' => $taxCalculation['snapshot'],
            ],
            'tax_context' => [
                'tax_rate_id' => $taxRate?->id,
                'tax_total' => $taxTotal,
                'is_auto_applied' => (bool) $taxCalculation['is_auto_applied'],
                'tax_snapshot' => $taxCalculation['snapshot'],
            ],
        ];
    }
}
