<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockMutationService
{
    public function record(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            $product = Product::query()->findOrFail($payload['product_id']);
            $variant = $this->resolveVariant($product, $payload['product_variant_id'] ?? null);
            $location = InventoryLocation::query()->findOrFail($payload['inventory_location_id']);

            if (!$product->track_stock) {
                throw new DomainException('Produk non-stockable tidak dapat diproses oleh Inventory.');
            }

            if ($variant && !$variant->track_stock) {
                throw new DomainException('Variant ini tidak ditandai untuk tracking stock.');
            }

            $stockKey = $this->stockKey($product->id, $variant?->id, $location->id);
            $stock = StockBalance::query()->where('stock_key', $stockKey)->lockForUpdate()->first();

            if (!$stock) {
                $stock = StockBalance::query()->create([
                    'stock_key' => $stockKey,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'inventory_location_id' => $location->id,
                    'current_quantity' => 0,
                    'reserved_quantity' => 0,
                    'minimum_quantity' => $payload['minimum_quantity'] ?? 0,
                    'reorder_quantity' => $payload['reorder_quantity'] ?? 0,
                    'allow_negative_stock' => (bool) ($payload['allow_negative_stock'] ?? false),
                ]);

                $stock = StockBalance::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            }

            $before = round((float) $stock->current_quantity, 4);
            $qty = round((float) $payload['quantity'], 4);
            $after = $payload['direction'] === 'in' ? $before + $qty : $before - $qty;

            if ($after < 0 && !$stock->allow_negative_stock && !($payload['allow_negative_stock'] ?? false)) {
                throw new DomainException('Stok tidak boleh negatif.');
            }

            $stock->fill([
                'current_quantity' => $after,
                'minimum_quantity' => $payload['minimum_quantity'] ?? $stock->minimum_quantity,
                'reorder_quantity' => $payload['reorder_quantity'] ?? $stock->reorder_quantity,
                'allow_negative_stock' => (bool) ($payload['allow_negative_stock'] ?? $stock->allow_negative_stock),
                'last_movement_at' => $payload['occurred_at'] ?? now(),
            ]);
            $stock->save();

            return StockMovement::query()->create([
                'stock_key' => $stock->stock_key,
                'inventory_stock_id' => $stock->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'inventory_location_id' => $location->id,
                'movement_type' => $payload['movement_type'],
                'direction' => $payload['direction'],
                'quantity' => $qty,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'reason_code' => $payload['reason_code'] ?? null,
                'reason_text' => $payload['reason_text'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'performed_by' => $this->actorId($payload['performed_by'] ?? null),
                'approved_by' => $this->actorId($payload['approved_by'] ?? null),
                'meta' => $payload['meta'] ?? null,
            ]);
        });
    }

    public function stockKey(int $productId, ?int $variantId, int $locationId): string
    {
        return implode(':', [$productId, $variantId ?: 'base', $locationId]);
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (!$variantId) {
            return null;
        }

        $variant = ProductVariant::query()->findOrFail($variantId);
        if ((int) $variant->product_id !== (int) $product->id) {
            throw new DomainException('Variant tidak cocok dengan produk.');
        }

        return $variant;
    }

    private function actorId(User|int|null $actor): ?int
    {
        if ($actor instanceof User) {
            return $actor->id;
        }

        return $actor ? (int) $actor : null;
    }
}
