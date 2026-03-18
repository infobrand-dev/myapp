<?php

namespace App\Modules\PointOfSale\Services;

use App\Models\User;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\PointOfSale\Models\PosCartItem;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosCartService
{
    public function activeCartFor(User $user): PosCart
    {
        return DB::transaction(function () use ($user) {
            $cart = PosCart::query()
                ->with(['items', 'contact'])
                ->where('cashier_user_id', $user->id)
                ->where('status', PosCart::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($cart) {
                return $cart;
            }

            return PosCart::query()->create([
                'uuid' => (string) Str::uuid(),
                'status' => PosCart::STATUS_ACTIVE,
                'cashier_user_id' => $user->id,
                'customer_label' => 'Walk-in Customer',
                'currency_code' => 'IDR',
            ])->load(['items', 'contact']);
        });
    }

    public function addSellable(User $user, Product $product, ?ProductVariant $variant = null, float $qty = 1, ?string $barcodeScanned = null): PosCart
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Qty harus lebih besar dari nol.',
            ]);
        }

        return DB::transaction(function () use ($user, $product, $variant, $qty, $barcodeScanned) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->with('items')->lockForUpdate()->findOrFail($cart->id);

            $existing = $cart->items
                ->first(function (PosCartItem $item) use ($product, $variant) {
                    return (int) $item->product_id === (int) $product->id
                        && (int) ($item->product_variant_id ?? 0) === (int) ($variant ? $variant->id : 0);
                });

            if ($existing) {
                $existing->qty = round((float) $existing->qty + $qty, 4);
                $existing->barcode_scanned = $barcodeScanned ?: $existing->barcode_scanned;
                $existing->line_total = $this->lineTotal(
                    (float) $existing->qty,
                    (float) $existing->unit_price,
                    (float) $existing->discount_total,
                    (float) $existing->tax_total
                );
                $existing->save();
            } else {
                $price = round((float) ($variant ? $variant->sell_price : $product->sell_price), 2);

                $cart->items()->create([
                    'uuid' => (string) Str::uuid(),
                    'line_no' => ((int) $cart->items()->max('line_no')) + 1,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant ? $variant->id : null,
                    'barcode_scanned' => $barcodeScanned,
                    'sku_snapshot' => $variant && $variant->sku ? $variant->sku : $product->sku,
                    'barcode_snapshot' => $variant && $variant->barcode ? $variant->barcode : $product->barcode,
                    'product_name_snapshot' => $product->name,
                    'variant_name_snapshot' => $variant ? $variant->name : null,
                    'unit_name_snapshot' => $product->unit ? $product->unit->name : null,
                    'qty' => round($qty, 4),
                    'unit_price' => $price,
                    'manual_price_override' => false,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'line_total' => $this->lineTotal($qty, $price, 0, 0),
                ]);
            }

            return $this->refreshTotals($cart);
        });
    }

    public function updateItem(User $user, PosCartItem $item, array $data): PosCart
    {
        return DB::transaction(function () use ($user, $item, $data) {
            $item = PosCartItem::query()->with('cart')->lockForUpdate()->findOrFail($item->id);
            $this->assertOwnedBy($item->cart, $user);

            $qty = array_key_exists('qty', $data) ? round((float) $data['qty'], 4) : (float) $item->qty;
            $unitPrice = array_key_exists('unit_price', $data) ? round((float) $data['unit_price'], 2) : (float) $item->unit_price;

            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    'qty' => 'Qty harus lebih besar dari nol.',
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'unit_price' => 'Harga tidak boleh negatif.',
                ]);
            }

            $item->fill([
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'manual_price_override' => array_key_exists('unit_price', $data) ? true : $item->manual_price_override,
                'notes' => $data['notes'] ?? $item->notes,
            ]);
            $item->line_total = $this->lineTotal($qty, $unitPrice, (float) $item->discount_total, (float) $item->tax_total);
            $item->save();

            return $this->refreshTotals($item->cart);
        });
    }

    public function removeItem(User $user, PosCartItem $item): PosCart
    {
        return DB::transaction(function () use ($user, $item) {
            $item = PosCartItem::query()->with('cart')->lockForUpdate()->findOrFail($item->id);
            $this->assertOwnedBy($item->cart, $user);

            $cart = $item->cart;
            $item->delete();

            return $this->refreshTotals($cart);
        });
    }

    public function clear(User $user): PosCart
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->with('items')->lockForUpdate()->findOrFail($cart->id);
            $cart->items()->delete();

            return $this->refreshTotals($cart);
        });
    }

    public function hold(User $user, ?string $label = null): array
    {
        return DB::transaction(function () use ($user, $label) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->with('items')->lockForUpdate()->findOrFail($cart->id);

            if (!$cart->items()->exists()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart kosong tidak bisa di-hold.',
                ]);
            }

            $cart->update([
                'status' => PosCart::STATUS_HELD,
                'customer_label' => $label ?: $cart->customer_label,
                'held_at' => now(),
            ]);

            $active = PosCart::query()->create([
                'uuid' => (string) Str::uuid(),
                'status' => PosCart::STATUS_ACTIVE,
                'cashier_user_id' => $user->id,
                'customer_label' => 'Walk-in Customer',
                'currency_code' => $cart->currency_code,
            ]);

            return [
                'held' => $cart->fresh(['items', 'contact']),
                'active' => $active->load(['items', 'contact']),
            ];
        });
    }

    public function heldCarts(User $user)
    {
        return PosCart::query()
            ->with(['items', 'contact'])
            ->where('cashier_user_id', $user->id)
            ->where('status', PosCart::STATUS_HELD)
            ->latest('held_at')
            ->get();
    }

    public function resume(User $user, PosCart $heldCart): PosCart
    {
        return DB::transaction(function () use ($user, $heldCart) {
            $heldCart = PosCart::query()->with(['items', 'contact'])->lockForUpdate()->findOrFail($heldCart->id);
            $this->assertOwnedBy($heldCart, $user);

            if ($heldCart->status !== PosCart::STATUS_HELD) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart tersebut tidak dalam status hold.',
                ]);
            }

            PosCart::query()
                ->where('cashier_user_id', $user->id)
                ->where('status', PosCart::STATUS_ACTIVE)
                ->delete();

            $heldCart->update([
                'status' => PosCart::STATUS_ACTIVE,
                'held_at' => null,
            ]);

            return $heldCart->fresh(['items', 'contact']);
        });
    }

    public function assignCustomer(User $user, ?int $contactId, ?string $label = null): PosCart
    {
        $cart = $this->activeCartFor($user);
        $cart->update([
            'contact_id' => $contactId,
            'customer_label' => $label ?: ($contactId ? null : 'Walk-in Customer'),
        ]);

        return $cart->fresh(['items', 'contact']);
    }

    public function serialize(PosCart $cart): array
    {
        $cart->loadMissing(['items', 'contact']);

        return [
            'id' => $cart->id,
            'uuid' => $cart->uuid,
            'status' => $cart->status,
            'customer' => [
                'contact_id' => $cart->contact_id,
                'label' => $cart->contact ? $cart->contact->name : ($cart->customer_label ?: 'Walk-in Customer'),
            ],
            'notes' => $cart->notes,
            'totals' => [
                'item_count' => $cart->item_count,
                'subtotal' => (float) $cart->subtotal,
                'item_discount_total' => (float) $cart->item_discount_total,
                'order_discount_total' => (float) $cart->order_discount_total,
                'tax_total' => (float) $cart->tax_total,
                'grand_total' => (float) $cart->grand_total,
            ],
            'items' => $cart->items->map(fn (PosCartItem $item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'line_no' => $item->line_no,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name_snapshot,
                'variant_name' => $item->variant_name_snapshot,
                'sku' => $item->sku_snapshot,
                'barcode' => $item->barcode_snapshot,
                'qty' => (float) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'discount_total' => (float) $item->discount_total,
                'tax_total' => (float) $item->tax_total,
                'line_total' => (float) $item->line_total,
                'notes' => $item->notes,
            ])->values()->all(),
        ];
    }

    public function refreshTotals(PosCart $cart): PosCart
    {
        $cart = PosCart::query()->with(['items', 'contact'])->findOrFail($cart->id);
        $itemCount = (int) $cart->items->sum(fn (PosCartItem $item) => (float) $item->qty);
        $subtotal = round((float) $cart->items->sum(fn (PosCartItem $item) => (float) $item->qty * (float) $item->unit_price), 2);
        $itemDiscountTotal = round((float) $cart->items->sum(fn (PosCartItem $item) => (float) $item->discount_total), 2);
        $taxTotal = round((float) $cart->items->sum(fn (PosCartItem $item) => (float) $item->tax_total), 2);
        $grandTotal = round($subtotal - $itemDiscountTotal - (float) $cart->order_discount_total + $taxTotal, 2);

        $cart->update([
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'item_discount_total' => $itemDiscountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ]);

        return $cart->fresh(['items', 'contact']);
    }

    private function assertOwnedBy(PosCart $cart, User $user): void
    {
        if ((int) $cart->cashier_user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'cart' => 'Cart tersebut bukan milik kasir yang sedang login.',
            ]);
        }
    }

    private function lineTotal(float $qty, float $unitPrice, float $discountTotal, float $taxTotal): float
    {
        return round(($qty * $unitPrice) - $discountTotal + $taxTotal, 2);
    }
}
