<?php

namespace App\Modules\PointOfSale\Services;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\PointOfSale\Models\PosCartItem;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosCartService
{
    public function activeCartFor(User $user): PosCart
    {
        return DB::transaction(function () use ($user) {
            User::query()
                ->where('tenant_id', TenantContext::currentId())
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            $activeCarts = PosCart::query()
                ->with(['items', 'contact'])
                ->where('tenant_id', TenantContext::currentId())
                ->where('cashier_user_id', $user->id)
                ->where('status', PosCart::STATUS_ACTIVE)
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            $cart = $activeCarts->first();

            if ($cart) {
                if ($activeCarts->count() > 1) {
                    $duplicateIds = $activeCarts->slice(1)->pluck('id')->all();

                    PosCart::query()
                        ->where('tenant_id', TenantContext::currentId())
                        ->whereIn('id', $duplicateIds)
                        ->update([
                            'status' => PosCart::STATUS_CANCELLED,
                            'notes' => DB::raw("TRIM(CONCAT(COALESCE(notes, ''), ' [AUTO-CANCELLED duplicate active cart]'))"),
                            'meta' => json_encode([
                                'auto_cancelled_duplicate_active_cart' => true,
                                'auto_cancelled_at' => now()->toDateTimeString(),
                                'kept_active_cart_id' => $cart->id,
                            ]),
                        ]);

                    $cart->notes = trim((string) $cart->notes . ' [Duplicate active carts detected and cleaned]');
                    $cart->meta = array_merge($cart->meta ?? [], [
                        'duplicate_active_carts_cleaned' => true,
                        'duplicate_active_cart_ids' => $duplicateIds,
                    ]);
                    $cart->save();
                }

                return $cart;
            }

            return PosCart::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'uuid' => (string) Str::uuid(),
                'status' => PosCart::STATUS_ACTIVE,
                'cashier_user_id' => $user->id,
                'pos_cash_session_id' => optional($this->activeSession($user))->id,
                'outlet_id' => optional($this->activeSession($user))->outlet_id,
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

        if (!$product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk tidak aktif dan tidak bisa dijual di POS.',
            ]);
        }

        if ($variant) {
            if ((int) $variant->product_id !== (int) $product->id) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Variant tidak cocok dengan produk yang dipilih.',
                ]);
            }

            if (!$variant->is_active) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Variant tidak aktif dan tidak bisa dijual di POS.',
                ]);
            }
        }

        return DB::transaction(function () use ($user, $product, $variant, $qty, $barcodeScanned) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with('items')->lockForUpdate()->findOrFail($cart->id);

            $existing = $cart->items
                ->first(function (PosCartItem $item) use ($product, $variant) {
                    return (int) $item->product_id === (int) $product->id
                        && (int) ($item->product_variant_id ?? 0) === (int) ($variant ? $variant->id : 0);
                });

            if ($existing) {
                $existing->qty = round((float) $existing->qty + $qty, 4);
                $existing->barcode_scanned = $barcodeScanned ?: $existing->barcode_scanned;
                $existing->discount_total = 0;
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
                    'tenant_id' => TenantContext::currentId(),
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

            $this->resetDiscountState($cart);

            return $this->refreshTotals($cart);
        });
    }

    public function updateItem(User $user, PosCartItem $item, array $data): PosCart
    {
        return DB::transaction(function () use ($user, $item, $data) {
            $item = PosCartItem::query()->where('tenant_id', TenantContext::currentId())->with('cart')->lockForUpdate()->findOrFail($item->id);
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

            $this->resetDiscountState($item->cart);

            return $this->refreshTotals($item->cart);
        });
    }

    public function removeItem(User $user, PosCartItem $item): PosCart
    {
        return DB::transaction(function () use ($user, $item) {
            $item = PosCartItem::query()->where('tenant_id', TenantContext::currentId())->with('cart')->lockForUpdate()->findOrFail($item->id);
            $this->assertOwnedBy($item->cart, $user);

            $cart = $item->cart;
            $item->delete();

            $this->resetDiscountState($cart);

            return $this->refreshTotals($cart);
        });
    }

    public function clear(User $user): PosCart
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with('items')->lockForUpdate()->findOrFail($cart->id);
            $cart->items()->delete();
            $cart->update([
                'contact_id' => null,
                'customer_label' => 'Walk-in Customer',
                'notes' => null,
                'discount_snapshot' => null,
                'item_count' => 0,
                'subtotal' => 0,
                'item_discount_total' => 0,
                'order_discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
            ]);

            return $cart->fresh(['items', 'contact']);
        });
    }

    public function hold(User $user, ?string $label = null): array
    {
        return DB::transaction(function () use ($user, $label) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with('items')->lockForUpdate()->findOrFail($cart->id);

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
                'tenant_id' => TenantContext::currentId(),
                'uuid' => (string) Str::uuid(),
                'status' => PosCart::STATUS_ACTIVE,
                'cashier_user_id' => $user->id,
                'pos_cash_session_id' => optional($this->activeSession($user))->id,
                'outlet_id' => optional($this->activeSession($user))->outlet_id,
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
            ->where('tenant_id', TenantContext::currentId())
            ->where('cashier_user_id', $user->id)
            ->where('status', PosCart::STATUS_HELD)
            ->latest('held_at')
            ->get();
    }

    public function resume(User $user, PosCart $heldCart): PosCart
    {
        return DB::transaction(function () use ($user, $heldCart) {
            $heldCart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with(['items', 'contact'])->lockForUpdate()->findOrFail($heldCart->id);
            $this->assertOwnedBy($heldCart, $user);

            if ($heldCart->status !== PosCart::STATUS_HELD) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart tersebut tidak dalam status hold.',
                ]);
            }

            $activeCart = PosCart::query()
                ->with('items')
                ->where('tenant_id', TenantContext::currentId())
                ->where('cashier_user_id', $user->id)
                ->where('status', PosCart::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($activeCart && (int) $activeCart->id !== (int) $heldCart->id) {
                if ($activeCart->items->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Masih ada cart aktif berisi item. Hold atau clear cart aktif sebelum resume cart lain.',
                    ]);
                }

                $activeCart->delete();
            }

            $heldCart->update([
                'status' => PosCart::STATUS_ACTIVE,
                'held_at' => null,
            ]);

            return $heldCart->fresh(['items', 'contact']);
        });
    }

    public function assignCustomer(User $user, ?int $contactId, ?string $label = null): PosCart
    {
        if ($contactId) {
            Contact::query()
                ->where('tenant_id', TenantContext::currentId())
                ->findOrFail($contactId);
        }

        $cart = $this->activeCartFor($user);
        $cart->update([
            'contact_id' => $contactId,
            'customer_label' => $label ?: ($contactId ? null : 'Walk-in Customer'),
        ]);

        $this->resetDiscountState($cart);

        return $cart->fresh(['items', 'contact']);
    }

    public function applyDiscountEvaluation(User $user, array $evaluation): PosCart
    {
        return DB::transaction(function () use ($user, $evaluation) {
            $cart = $this->activeCartFor($user);
            $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with(['items', 'contact'])->lockForUpdate()->findOrFail($cart->id);

            $lineTotals = collect($evaluation['line_totals'] ?? [])
                ->filter(function ($row) {
                    return is_array($row) && !empty($row['line_key']);
                })
                ->keyBy('line_key');

            foreach ($cart->items as $item) {
                $line = $lineTotals->get($item->uuid);

                $item->discount_total = $line ? round((float) ($line['discount_total'] ?? 0), 2) : 0;
                $item->line_total = $this->lineTotal(
                    (float) $item->qty,
                    (float) $item->unit_price,
                    (float) $item->discount_total,
                    (float) $item->tax_total
                );
                $item->save();
            }

            $cart->update([
                'discount_snapshot' => $evaluation,
                'order_discount_total' => 0,
            ]);

            return $this->refreshTotals($cart);
        });
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
        $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with(['items', 'contact'])->findOrFail($cart->id);
        $itemCount = (int) $cart->items->count();
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

    private function resetDiscountState(PosCart $cart): void
    {
        $cart = PosCart::query()->where('tenant_id', TenantContext::currentId())->with('items')->findOrFail($cart->id);

        foreach ($cart->items as $item) {
            if ((float) $item->discount_total !== 0.0) {
                $item->discount_total = 0;
                $item->line_total = $this->lineTotal(
                    (float) $item->qty,
                    (float) $item->unit_price,
                    0,
                    (float) $item->tax_total
                );
                $item->save();
            }
        }

        $cart->update([
            'discount_snapshot' => null,
            'order_discount_total' => 0,
        ]);
    }

    private function lineTotal(float $qty, float $unitPrice, float $discountTotal, float $taxTotal): float
    {
        return round(($qty * $unitPrice) - $discountTotal + $taxTotal, 2);
    }

    private function activeSession(User $user): ?PosCashSession
    {
        return PosCashSession::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('cashier_user_id', $user->id)
            ->where('status', PosCashSession::STATUS_ACTIVE)
            ->latest('opened_at')
            ->first();
    }
}
