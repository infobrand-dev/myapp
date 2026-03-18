<?php

namespace App\Modules\Purchases\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Purchases\Models\Purchase;
use Illuminate\Support\Collection;

class PurchaseLookupService
{
    public function suppliers(): Collection
    {
        return Contact::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function purchasables(): Collection
    {
        $products = Product::query()
            ->with([
                'unit',
                'variants' => fn ($query) => $query->whereNull('deleted_at')->where('is_active', true)->orderBy('position'),
            ])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return $products->flatMap(function (Product $product) {
            $rows = collect([[
                'key' => 'product:' . $product->id,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'label' => $product->name,
                'description' => implode(' | ', array_filter([
                    'SKU: ' . $product->sku,
                    $product->unit ? 'Unit: ' . $product->unit->name : null,
                    'Cost default: Rp ' . number_format((float) $product->cost_price, 0, ',', '.'),
                ])),
                'unit_cost' => (float) $product->cost_price,
            ]]);

            $variants = $product->variants->map(function (ProductVariant $variant) use ($product) {
                return [
                    'key' => 'variant:' . $variant->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'label' => $product->name . ' - ' . $variant->name,
                    'description' => implode(' | ', array_filter([
                        'SKU: ' . $variant->sku,
                        $variant->attribute_summary,
                        'Cost default: Rp ' . number_format((float) $variant->cost_price, 0, ',', '.'),
                    ])),
                    'unit_cost' => (float) $variant->cost_price,
                ];
            });

            return $rows->concat($variants);
        })->values();
    }

    public function inventoryLocations(): Collection
    {
        return InventoryLocation::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function statusOptions(): array
    {
        return [
            Purchase::STATUS_DRAFT => 'Draft',
            Purchase::STATUS_CONFIRMED => 'Confirmed',
            Purchase::STATUS_PARTIAL_RECEIVED => 'Partial Received',
            Purchase::STATUS_RECEIVED => 'Received',
            Purchase::STATUS_CANCELLED => 'Cancelled',
            Purchase::STATUS_VOIDED => 'Voided',
        ];
    }

    public function paymentStatusOptions(): array
    {
        return [
            Purchase::PAYMENT_UNPAID => 'Unpaid',
            Purchase::PAYMENT_PARTIAL => 'Partial',
            Purchase::PAYMENT_PAID => 'Paid',
            Purchase::PAYMENT_OVERPAID => 'Overpaid',
        ];
    }

    public function dependencyMap(): array
    {
        return [
            [
                'module' => 'products',
                'type' => 'required',
                'notes' => 'Purchases membaca master product dan variant dari Products, lalu menyimpan snapshot item saat draft/final.',
            ],
            [
                'module' => 'contacts',
                'type' => 'required',
                'notes' => 'Supplier/vendor direferensikan dari Contacts dan disimpan snapshotnya untuk menjaga histori.',
            ],
            [
                'module' => 'inventory',
                'type' => 'required',
                'notes' => 'Receiving memicu stock-in ke Inventory. Purchases tidak menyimpan balance stok.',
            ],
            [
                'module' => 'payments',
                'type' => 'required',
                'notes' => 'Status pembayaran diringkas dari allocation di Payments. Purchases tidak menyimpan domain pembayaran utama.',
            ],
        ];
    }
}
