<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockMutationService
{
    public function record(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            [$product, $variant, $location, $stock] = $this->lockStock($payload);

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
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $location->branch_id,
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

    public function reserve(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            [$product, $variant, $location, $stock] = $this->lockStock($payload);

            $qty = $this->normalizeQuantity($payload['quantity'] ?? 0);
            $available = $stock->availableQuantity();

            if ($qty <= 0) {
                throw new DomainException('Quantity reservasi harus lebih besar dari 0.');
            }

            if ($available < $qty) {
                throw new DomainException('Stok tersedia tidak cukup untuk reservasi.');
            }

            $beforeReserved = round((float) $stock->reserved_quantity, 4);
            $afterReserved = round($beforeReserved + $qty, 4);

            $stock->reserved_quantity = $afterReserved;
            $stock->last_movement_at = $payload['occurred_at'] ?? now();
            $stock->save();

            return $this->createMovement($stock, $product, $variant, $location, [
                'movement_type' => $payload['movement_type'] ?? 'reservation',
                'direction' => 'reserve',
                'quantity' => $qty,
                'before_quantity' => (float) $stock->current_quantity,
                'after_quantity' => (float) $stock->current_quantity,
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'reason_code' => $payload['reason_code'] ?? 'reservation',
                'reason_text' => $payload['reason_text'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'performed_by' => $payload['performed_by'] ?? null,
                'approved_by' => $payload['approved_by'] ?? null,
                'meta' => array_merge($payload['meta'] ?? [], [
                    'reserved_before' => $beforeReserved,
                    'reserved_after' => $afterReserved,
                    'available_before' => $available,
                    'available_after' => round($stock->current_quantity - $afterReserved, 4),
                ]),
            ]);
        });
    }

    public function release(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            [$product, $variant, $location, $stock] = $this->lockStock($payload);

            $qty = $this->normalizeQuantity($payload['quantity'] ?? 0);
            $beforeReserved = round((float) $stock->reserved_quantity, 4);

            if ($qty <= 0) {
                throw new DomainException('Quantity release harus lebih besar dari 0.');
            }

            if ($beforeReserved < $qty) {
                throw new DomainException('Reserved quantity tidak cukup untuk dilepas.');
            }

            $afterReserved = round($beforeReserved - $qty, 4);
            $stock->reserved_quantity = $afterReserved;
            $stock->last_movement_at = $payload['occurred_at'] ?? now();
            $stock->save();

            return $this->createMovement($stock, $product, $variant, $location, [
                'movement_type' => $payload['movement_type'] ?? 'reservation_release',
                'direction' => 'release',
                'quantity' => $qty,
                'before_quantity' => (float) $stock->current_quantity,
                'after_quantity' => (float) $stock->current_quantity,
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'reason_code' => $payload['reason_code'] ?? 'reservation_release',
                'reason_text' => $payload['reason_text'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'performed_by' => $payload['performed_by'] ?? null,
                'approved_by' => $payload['approved_by'] ?? null,
                'meta' => array_merge($payload['meta'] ?? [], [
                    'reserved_before' => $beforeReserved,
                    'reserved_after' => $afterReserved,
                    'available_before' => round($stock->current_quantity - $beforeReserved, 4),
                    'available_after' => round($stock->current_quantity - $afterReserved, 4),
                ]),
            ]);
        });
    }

    public function consumeReserved(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            [$product, $variant, $location, $stock] = $this->lockStock($payload);

            $qty = $this->normalizeQuantity($payload['quantity'] ?? 0);
            $before = round((float) $stock->current_quantity, 4);
            $beforeReserved = round((float) $stock->reserved_quantity, 4);
            $after = round($before - $qty, 4);
            $afterReserved = round($beforeReserved - $qty, 4);

            if ($qty <= 0) {
                throw new DomainException('Quantity penjualan reserved harus lebih besar dari 0.');
            }

            if ($beforeReserved < $qty) {
                throw new DomainException('Reserved quantity tidak cukup untuk diposting sebagai penjualan.');
            }

            if ($after < 0 && !$stock->allow_negative_stock && !($payload['allow_negative_stock'] ?? false)) {
                throw new DomainException('Stok tidak boleh negatif saat consume reserved quantity.');
            }

            $stock->current_quantity = $after;
            $stock->reserved_quantity = max(0, $afterReserved);
            $stock->minimum_quantity = $payload['minimum_quantity'] ?? $stock->minimum_quantity;
            $stock->reorder_quantity = $payload['reorder_quantity'] ?? $stock->reorder_quantity;
            $stock->allow_negative_stock = (bool) ($payload['allow_negative_stock'] ?? $stock->allow_negative_stock);
            $stock->last_movement_at = $payload['occurred_at'] ?? now();
            $stock->save();

            return $this->createMovement($stock, $product, $variant, $location, [
                'movement_type' => $payload['movement_type'] ?? 'sale_reserved',
                'direction' => 'out',
                'quantity' => $qty,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'reason_code' => $payload['reason_code'] ?? 'sale_reserved',
                'reason_text' => $payload['reason_text'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'performed_by' => $payload['performed_by'] ?? null,
                'approved_by' => $payload['approved_by'] ?? null,
                'meta' => array_merge($payload['meta'] ?? [], [
                    'reserved_before' => $beforeReserved,
                    'reserved_after' => max(0, $afterReserved),
                    'available_before' => round($before - $beforeReserved, 4),
                    'available_after' => round($after - max(0, $afterReserved), 4),
                ]),
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

        $variant = ProductVariant::query()
            ->where('tenant_id', TenantContext::currentId())
            ->findOrFail($variantId);
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

    private function lockStock(array $payload): array
    {
        $product = Product::query()
            ->where('tenant_id', TenantContext::currentId())
            ->findOrFail($payload['product_id']);
        $variant = $this->resolveVariant($product, $payload['product_variant_id'] ?? null);
        $location = InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->findOrFail($payload['inventory_location_id']);

        if (!$product->track_stock) {
            throw new DomainException('Produk non-stockable tidak dapat diproses oleh Inventory.');
        }

        if ($variant && !$variant->track_stock) {
            throw new DomainException('Variant ini tidak ditandai untuk tracking stock.');
        }

        $stockKey = $this->stockKey($product->id, $variant?->id, $location->id);
        $stock = StockBalance::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->where('stock_key', $stockKey)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stock = StockBalance::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $location->branch_id,
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

            $stock = StockBalance::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->whereKey($stock->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return [$product, $variant, $location, $stock];
    }

    private function createMovement(
        StockBalance $stock,
        Product $product,
        ?ProductVariant $variant,
        InventoryLocation $location,
        array $payload
    ): StockMovement {
        return StockMovement::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => $location->branch_id,
            'stock_key' => $stock->stock_key,
            'inventory_stock_id' => $stock->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'inventory_location_id' => $location->id,
            'movement_type' => $payload['movement_type'],
            'direction' => $payload['direction'],
            'quantity' => $payload['quantity'],
            'before_quantity' => $payload['before_quantity'],
            'after_quantity' => $payload['after_quantity'],
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'reason_code' => $payload['reason_code'] ?? null,
            'reason_text' => $payload['reason_text'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now(),
            'performed_by' => $this->actorId($payload['performed_by'] ?? null),
            'approved_by' => $this->actorId($payload['approved_by'] ?? null),
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    private function normalizeQuantity(mixed $quantity): float
    {
        return round((float) $quantity, 4);
    }
}
