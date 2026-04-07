@extends('layouts.pos')

@section('title', 'POS Terminal')

@section('content')
@php
    $defaultCurrency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
    $money = app(\App\Support\MoneyFormatter::class);
    $posConfig = [
        'initialProducts' => $initialProducts->map(fn ($product) => [
            'sellable_key' => 'product:' . $product->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'name' => $product->name,
            'variant_name' => null,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (float) $product->sell_price,
            'unit' => null,
        ])->values(),
        'initialCustomers' => $initialCustomers->map(fn ($customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->mobile ?: $customer->phone,
            'email' => $customer->email,
        ])->values(),
        'paymentMethods' => $paymentMethods->map(fn ($method) => [
            'id' => $method->id,
            'code' => $method->code,
            'name' => $method->name,
            'type' => $method->type,
            'requires_reference' => (bool) $method->requires_reference,
        ])->values(),
        'defaultCurrency' => $defaultCurrency,
        'routes' => [
            'cartActive' => route('pos.cart.active'),
            'cartUpdate' => route('pos.cart.update'),
            'cartClear' => route('pos.cart.clear'),
            'cartItems' => route('pos.cart.items.store'),
            'cartItemBase' => url('/pos/cart/items'),
            'barcodeScan' => route('pos.barcode.scan'),
            'heldIndex' => route('pos.held.index'),
            'heldStore' => route('pos.held.store'),
            'heldResumeBase' => url('/pos/held-carts'),
            'productSearch' => route('pos.products.search'),
            'customerSearch' => route('pos.customers.search'),
            'discountEvaluate' => route('pos.discounts.evaluate'),
            'checkout' => route('pos.checkout.store'),
        ],
    ];
@endphp

<style>
/* ── POS App Shell ──────────────────────────────── */
.pos-app { display:flex; flex-direction:column; height:100dvh; background:#ede9e0; }

/* ── Topbar ─────────────────────────────────────── */
.pos-topbar { flex-shrink:0; display:flex; align-items:center; gap:.5rem; padding:.5rem .75rem; background:linear-gradient(135deg,#0f766e,#155e75); color:#fff; min-height:52px; }
.pos-topbar-brand { font-weight:800; font-size:1rem; letter-spacing:.02em; white-space:nowrap; }
.pos-topbar-sep { width:1px; height:1.25rem; background:rgba(255,255,255,.25); flex-shrink:0; }
.pos-topbar-info { font-size:.78rem; opacity:.85; white-space:nowrap; }
.pos-topbar-btn { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .7rem; border-radius:.65rem; border:1px solid rgba(255,255,255,.25); background:rgba(255,255,255,.1); color:#fff; font-size:.8rem; font-weight:600; cursor:pointer; transition:background .15s; white-space:nowrap; }
.pos-topbar-btn:hover { background:rgba(255,255,255,.2); }
.pos-topbar-btn.active { background:rgba(255,255,255,.25); }
.pos-topbar-spacer { flex:1; }

/* ── Body ───────────────────────────────────────── */
.pos-body { display:flex; flex:1; min-height:0; gap:.6rem; padding:.6rem; }

/* ── Left: Products ─────────────────────────────── */
.pos-left { display:flex; flex-direction:column; flex:1; min-height:0; gap:.5rem; }
.pos-search-bar { flex-shrink:0; display:flex; gap:.5rem; }
.pos-barcode-input, .pos-search-input { flex:1; height:2.8rem; border-radius:.75rem; border:1px solid #d0c8bc; background:#fff; padding-inline:.9rem; font-size:.92rem; font-family:inherit; }
.pos-barcode-input { font-weight:700; letter-spacing:.04em; background:#fffdf8; max-width:300px; }
.pos-products-wrap { flex:1; overflow-y:auto; border-radius:1rem; }
.pos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(155px,1fr)); gap:.6rem; padding:.6rem; }
.pos-product-card { border:1px solid #ddd5c5; border-radius:.9rem; background:linear-gradient(180deg,#fff,#fbf7f0); padding:.8rem; min-height:130px; cursor:pointer; transition:transform .1s, box-shadow .1s, border-color .1s; text-align:left; }
.pos-product-card:hover { transform:translateY(-2px); border-color:rgba(15,118,110,.35); box-shadow:0 .6rem 1rem rgba(15,118,110,.12); }

/* ── Right: Cart ────────────────────────────────── */
.pos-right { display:flex; flex-direction:column; width:380px; flex-shrink:0; min-height:0; background:#fdfbf7; border-radius:1rem; border:1px solid #ddd5c5; overflow:hidden; }
.pos-cart-scroll { flex:1; overflow-y:auto; padding:.75rem; display:flex; flex-direction:column; gap:.5rem; }
.pos-cart-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#9ca3af; }
.pos-cart-line { border:1px solid #e8dfd2; border-radius:.8rem; background:#fff; padding:.7rem; }
.pos-qty-box { display:inline-flex; align-items:center; gap:.25rem; border:1px solid #dcccb9; border-radius:.7rem; padding:.2rem .35rem; background:#fcfaf7; }
.pos-qty-input { width:3rem; border:0; background:transparent; text-align:center; font-weight:700; font-size:.9rem; }

/* ── Cart footer (totals + payment + buttons) ───── */
.pos-cart-footer { flex-shrink:0; border-top:1px solid #e8dfd2; }
.pos-totals { padding:.6rem .75rem .4rem; background:#f7f2e9; }
.pos-total-row { display:flex; justify-content:space-between; font-size:.8rem; margin-bottom:.2rem; }
.pos-grand-total { font-size:1.5rem; font-weight:800; color:#0f766e; letter-spacing:-.02em; }
.pos-payment-section { padding:.6rem .75rem; border-top:1px dashed #ddd5c5; }
.pos-payment-row { display:flex; gap:.4rem; align-items:center; margin-bottom:.4rem; }
.pos-actions { display:flex; gap:.4rem; padding:.5rem .75rem .65rem; }
.pos-actions .btn { min-height:3rem; }
.pos-btn-checkout { flex:3; }
.pos-btn-hold, .pos-btn-clear { flex:1; }

/* ── Modal overlay ──────────────────────────────── */
.pos-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1040; display:flex; align-items:center; justify-content:center; padding:1rem; }
.pos-modal { background:#fff; border-radius:1.25rem; box-shadow:0 2rem 4rem rgba(0,0,0,.2); width:100%; max-width:480px; max-height:85dvh; display:flex; flex-direction:column; overflow:hidden; }
.pos-modal-header { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.1rem; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
.pos-modal-title { font-weight:700; font-size:1rem; }
.pos-modal-body { flex:1; overflow-y:auto; padding:1rem 1.1rem; }
.pos-modal-footer { padding:.75rem 1.1rem; border-top:1px solid #e5e7eb; flex-shrink:0; }
.pos-customer-item { border:1px solid #e8dfd2; border-radius:.75rem; background:#fafaf8; padding:.65rem .85rem; cursor:pointer; width:100%; text-align:left; transition:background .1s, border-color .1s; }
.pos-customer-item:hover { background:#f0faf9; border-color:rgba(15,118,110,.3); }
.pos-held-item { border:1px solid #e8dfd2; border-radius:.75rem; background:#fafaf8; padding:.65rem .85rem; cursor:pointer; width:100%; text-align:left; transition:background .1s; }
.pos-held-item:hover { background:#f0faf9; }

/* ── Toast ──────────────────────────────────────── */
.pos-toast { position:fixed; right:1rem; bottom:1rem; z-index:2000; min-width:240px; max-width:340px; border-radius:.9rem; padding:.75rem 1rem; color:#fff; font-weight:600; font-size:.87rem; box-shadow:0 1rem 2rem rgba(0,0,0,.18); }
.pos-toast.success { background:#0f766e; }
.pos-toast.error { background:#b91c1c; }
.pos-toast.info { background:#1d4ed8; }

/* ── Shift alert ────────────────────────────────── */
.pos-shift-bar { flex-shrink:0; padding:.35rem 1rem; font-size:.8rem; background:#fef9c3; color:#713f12; border-bottom:1px solid #fde68a; display:flex; align-items:center; gap:.5rem; }
.pos-shift-bar.ok { background:#dcfce7; color:#14532d; border-color:#bbf7d0; }

@media (max-width:900px) {
    .pos-right { width:320px; }
    .pos-barcode-input { max-width:200px; }
}
@media (max-width:680px) {
    .pos-body { flex-direction:column; }
    .pos-right { width:100%; height:50dvh; flex-shrink:0; }
    .pos-left { flex:none; height:50dvh; flex-shrink:0; }
}
</style>

<div class="pos-app"
    x-data='posScreen(@json($posConfig))'
    x-init="init()">

    {{-- ── Topbar ──────────────────────────────────────── --}}
    <div class="pos-topbar">
        <div class="pos-topbar-brand"><i class="ti ti-device-desktop me-1"></i>POS Terminal</div>
        <div class="pos-topbar-sep"></div>
        <div class="pos-topbar-info">
            <i class="ti ti-user-circle me-1"></i>{{ auth()->user()->name }}
        </div>
        <div class="pos-topbar-spacer"></div>

        {{-- Customer button --}}
        <button type="button" class="pos-topbar-btn" @click="openCustomerModal()" :class="{active: cart.customer.contact_id}">
            <i class="ti ti-users"></i>
            <span x-text="cart.customer.label || 'Walk-in'"></span>
        </button>

        {{-- Discount button --}}
        <button type="button" class="pos-topbar-btn" @click="showDiscountModal=true" :class="{active: appliedDiscountCount > 0}">
            <i class="ti ti-tag"></i>
            <span x-show="appliedDiscountCount > 0" x-text="appliedDiscountCount + ' disc'"></span>
            <span x-show="appliedDiscountCount === 0">Diskon</span>
        </button>

        {{-- Held carts button --}}
        <button type="button" class="pos-topbar-btn" @click="openHeldModal()">
            <i class="ti ti-shopping-cart-pause"></i>
            <span x-show="heldCarts.length > 0" x-text="heldCarts.length + ' held'"></span>
            <span x-show="heldCarts.length === 0">Held</span>
        </button>

        <div class="pos-topbar-sep"></div>

        {{-- Shortcuts hint --}}
        <div class="pos-topbar-info d-none d-lg-block">
            <i class="ti ti-keyboard me-1"></i>F3 Scan · F9 Pay
        </div>

        {{-- Exit to dashboard --}}
        <a href="{{ route('dashboard') }}" class="pos-topbar-btn" style="text-decoration:none;" title="Keluar dari POS">
            <i class="ti ti-door-exit"></i>
        </a>
    </div>

    {{-- ── Shift Alert Bar ─────────────────────────────── --}}
    @if($activeShift)
        <div class="pos-shift-bar ok">
            <i class="ti ti-circle-check"></i>
            Shift aktif: <a href="{{ route('pos.shifts.show', $activeShift) }}" style="color:inherit;font-weight:700;">{{ $activeShift->code }}</a>
            · Opening cash: {{ $money->format((float) $activeShift->opening_cash_amount, $activeShift->currency_code ?: $defaultCurrency) }}
        </div>
    @else
        <div class="pos-shift-bar">
            <i class="ti ti-alert-triangle"></i>
            Tidak ada shift aktif.
            <a href="{{ route('pos.shifts.create') }}" style="color:inherit;font-weight:700;text-decoration:underline;">Buka shift</a>
            sebelum checkout POS.
        </div>
    @endif

    {{-- ── Main Body ───────────────────────────────────── --}}
    <div class="pos-body">

        {{-- ── Left: Products ──────────────────────────── --}}
        <div class="pos-left">
            <div class="pos-search-bar">
                <form @submit.prevent="scanBarcode()" style="flex:1;max-width:300px;">
                    <input x-ref="barcodeInput" x-model="barcode" type="text" class="pos-barcode-input w-100"
                        placeholder="Scan barcode / Enter SKU…" autocomplete="off">
                </form>
                <input x-model="productQuery" @input.debounce.250ms="searchProducts()" type="text"
                    class="pos-search-input" placeholder="Cari nama produk, SKU…">
                <button type="button" class="btn btn-outline-secondary btn-sm" style="border-radius:.75rem;white-space:nowrap;" @click="refreshProducts()">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>

            <div class="pos-products-wrap">
                <div class="pos-grid">
                    <template x-for="product in products" :key="product.sellable_key">
                        <button type="button" class="pos-product-card" @click="addProduct(product)">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-teal-lt text-teal" style="font-size:.7rem;" x-text="product.variant_name ? 'Variant' : 'Product'"></span>
                            </div>
                            <div class="fw-bold mb-1" style="font-size:.88rem;line-height:1.3;" x-text="product.name"></div>
                            <div class="text-muted" style="font-size:.75rem;" x-text="product.variant_name || product.sku || '-'"></div>
                            <div class="mt-2 fw-bold" style="font-size:1.05rem;color:#0f766e;" x-text="money(product.price)"></div>
                        </button>
                    </template>
                    <template x-if="products.length === 0">
                        <div class="text-center text-muted py-5" style="grid-column:1/-1;">
                            <i class="ti ti-package d-block mb-2" style="font-size:2.5rem;"></i>
                            Produk tidak ditemukan.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── Right: Cart ──────────────────────────────── --}}
        <div class="pos-right">

            {{-- Cart items (scrollable) --}}
            <div class="pos-cart-scroll">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Active Cart</span>
                    <span class="badge bg-orange-lt text-orange" style="font-size:.72rem;" x-text="cart.totals.item_count + ' lines'"></span>
                </div>

                <template x-if="cart.items.length === 0">
                    <div class="pos-cart-empty">
                        <i class="ti ti-shopping-cart" style="font-size:2.5rem;margin-bottom:.5rem;"></i>
                        <div>Cart kosong</div>
                    </div>
                </template>

                <template x-for="item in cart.items" :key="item.uuid">
                    <div class="pos-cart-line">
                        <div class="d-flex justify-content-between gap-2 align-items-start">
                            <div style="flex:1;min-width:0;">
                                <div class="fw-semibold" style="font-size:.88rem;" x-text="item.product_name"></div>
                                <div class="text-muted" style="font-size:.75rem;" x-text="item.variant_name || item.sku || '-'"></div>
                                <div class="text-muted" style="font-size:.75rem;" x-text="money(item.unit_price) + ' / item'"></div>
                            </div>
                            <button type="button" class="btn btn-icon btn-sm btn-ghost-danger" @click="removeItem(item)" title="Hapus">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="pos-qty-box">
                                <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary" @click="changeQty(item, -1)" style="padding:.15rem;">−</button>
                                <input type="number" min="0.0001" step="1" class="pos-qty-input" :value="item.qty" @change="setQty(item, $event.target.value)">
                                <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary" @click="changeQty(item, 1)" style="padding:.15rem;">+</button>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold" style="font-size:.9rem;" x-text="money(item.line_total)"></div>
                                <div class="text-success" style="font-size:.75rem;" x-show="item.discount_total > 0" x-text="'- ' + money(item.discount_total)"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Cart footer: totals + payment + buttons --}}
            <div class="pos-cart-footer">

                {{-- Totals --}}
                <div class="pos-totals">
                    <div class="pos-total-row"><span class="text-muted">Subtotal</span><span x-text="money(cart.totals.subtotal)"></span></div>
                    <div class="pos-total-row" x-show="cart.totals.item_discount_total + cart.totals.order_discount_total > 0">
                        <span class="text-muted">Diskon</span>
                        <span class="text-success" x-text="'− ' + money(cart.totals.item_discount_total + cart.totals.order_discount_total)"></span>
                    </div>
                    <div class="pos-total-row" x-show="cart.totals.tax_total > 0">
                        <span class="text-muted">Tax</span><span x-text="money(cart.totals.tax_total)"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mt-1">
                        <span class="text-muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;">Grand Total</span>
                        <span class="pos-grand-total" x-text="money(cart.totals.grand_total)"></span>
                    </div>
                </div>

                {{-- Payment rows --}}
                <div class="pos-payment-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Pembayaran</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:.6rem;font-size:.75rem;padding:.2rem .6rem;" @click="addPaymentRow()">
                            <i class="ti ti-plus me-1"></i>Split
                        </button>
                    </div>

                    <template x-for="(payment, index) in payments" :key="index">
                        <div class="pos-payment-row">
                            <select class="form-select form-select-sm" style="border-radius:.65rem;flex:2;" x-model="payment.payment_method">
                                <template x-for="method in paymentMethods" :key="method.code">
                                    <option :value="method.code" x-text="method.name"></option>
                                </template>
                            </select>
                            <input type="number" step="1" min="0" class="form-control form-control-sm" style="border-radius:.65rem;flex:2;"
                                x-model="payment.amount"
                                @focus="$event.target.select()"
                                @blur="payment.amount = cleanNumber(payment.amount)"
                                placeholder="Jumlah">
                            <button type="button" class="btn btn-sm btn-ghost-danger btn-icon" @click="removePaymentRow(index)" x-show="payments.length > 1" title="Hapus">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div x-show="requiresReference(payment.payment_method)" class="mb-2">
                            <input type="text" class="form-control form-control-sm" style="border-radius:.65rem;" x-model="payment.reference_number" placeholder="Reference number">
                        </div>
                    </template>

                    {{-- Cash received / change --}}
                    <template x-if="hasCashPayment()">
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <input type="number" step="1" min="0" class="form-control form-control-sm" style="border-radius:.65rem;flex:1;"
                                x-model="cashReceivedAmount"
                                @focus="$event.target.select()"
                                @blur="cashReceivedAmount = cleanNumber(cashReceivedAmount)"
                                placeholder="Cash diterima">
                            <div class="text-muted" style="font-size:.78rem;white-space:nowrap;">
                                Kembalian: <strong x-text="money(changePreview())"></strong>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Action buttons --}}
                <div class="pos-actions">
                    <button type="button" class="btn btn-outline-warning pos-btn-hold" style="border-radius:.8rem;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;gap:.35rem;" @click="holdCart()" title="Hold Cart">
                        <i class="ti ti-shopping-cart-pause" style="font-size:1.1rem;"></i>
                        <span style="font-size:.78rem;">Hold</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary pos-btn-clear" style="border-radius:.8rem;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;gap:.35rem;" @click="clearCart()" title="Clear Cart">
                        <i class="ti ti-trash" style="font-size:1.1rem;"></i>
                        <span style="font-size:.78rem;">Clear</span>
                    </button>
                    <button type="button" class="btn btn-primary pos-btn-checkout" style="border-radius:.8rem;font-weight:700;font-size:.95rem;" @click="checkout()">
                        <i class="ti ti-circle-check me-1"></i>Checkout
                        <span class="ms-1 opacity-60" style="font-size:.73rem;">F9</span>
                    </button>
                </div>

                {{-- Last receipt link --}}
                <div x-show="lastReceiptRoute" class="px-3 pb-2 text-center">
                    <a :href="lastReceiptRoute" target="_blank" class="text-muted" style="font-size:.75rem;">
                        <i class="ti ti-printer me-1"></i>Print struk terakhir
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Toast ───────────────────────────────────────── --}}
    <template x-if="toast.show">
        <div class="pos-toast" :class="toast.type" x-text="toast.message"></div>
    </template>

    {{-- ══ MODAL: Customer ════════════════════════════════ --}}
    <template x-if="showCustomerModal">
        <div class="pos-modal-backdrop" @click.self="showCustomerModal=false">
            <div class="pos-modal">
                <div class="pos-modal-header">
                    <div class="pos-modal-title"><i class="ti ti-users me-2"></i>Pilih Customer</div>
                    <button type="button" class="btn btn-sm btn-ghost-secondary btn-icon" @click="showCustomerModal=false">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="pos-modal-body">
                    <input x-ref="customerSearchInput" x-model="customerQuery" @input.debounce.250ms="searchCustomers()"
                        type="text" class="form-control mb-3" style="border-radius:.8rem;"
                        placeholder="Cari nama, telepon, email…" autocomplete="off">
                    <div class="d-grid gap-2">
                        <template x-for="customer in customers" :key="customer.id">
                            <button type="button" class="pos-customer-item" @click="assignCustomer(customer)">
                                <div class="fw-semibold" style="font-size:.9rem;" x-text="customer.name"></div>
                                <div class="text-muted" style="font-size:.78rem;" x-text="customer.phone || customer.email || '-'"></div>
                            </button>
                        </template>
                        <template x-if="customers.length === 0 && customerQuery">
                            <div class="text-muted text-center py-3">Tidak ada hasil.</div>
                        </template>
                    </div>
                </div>
                <div class="pos-modal-footer">
                    <button type="button" class="btn btn-outline-secondary w-100" style="border-radius:.8rem;" @click="setWalkIn()">
                        <i class="ti ti-user me-1"></i>Walk-in Customer (Tanpa data)
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ══ MODAL: Discount ════════════════════════════════ --}}
    <template x-if="showDiscountModal">
        <div class="pos-modal-backdrop" @click.self="showDiscountModal=false">
            <div class="pos-modal" style="max-width:380px;">
                <div class="pos-modal-header">
                    <div class="pos-modal-title"><i class="ti ti-tag me-2"></i>Voucher / Diskon</div>
                    <button type="button" class="btn btn-sm btn-ghost-secondary btn-icon" @click="showDiscountModal=false">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="pos-modal-body">
                    <div class="input-group mb-3">
                        <input x-ref="voucherInput" x-model="voucherCode" type="text" class="form-control"
                            style="border-radius:.8rem 0 0 .8rem;" placeholder="Kode voucher (opsional)"
                            @keydown.enter.prevent="evaluateDiscounts()">
                        <button type="button" class="btn btn-primary" style="border-radius:0 .8rem .8rem 0;" @click="evaluateDiscounts()">
                            Apply
                        </button>
                    </div>
                    <div class="rounded p-3" style="background:#f7f2e9;font-size:.85rem;">
                        <i class="ti ti-info-circle me-1"></i>
                        <span x-text="discountSummary"></span>
                    </div>
                </div>
                <div class="pos-modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" style="border-radius:.8rem;" @click="showDiscountModal=false">Tutup</button>
                </div>
            </div>
        </div>
    </template>

    {{-- ══ MODAL: Held Carts ══════════════════════════════ --}}
    <template x-if="showHeldModal">
        <div class="pos-modal-backdrop" @click.self="showHeldModal=false">
            <div class="pos-modal" style="max-width:420px;">
                <div class="pos-modal-header">
                    <div class="pos-modal-title"><i class="ti ti-shopping-cart-pause me-2"></i>Held Carts</div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:.65rem;" @click="loadHeldCarts()">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary btn-icon" @click="showHeldModal=false">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
                <div class="pos-modal-body">
                    <template x-if="heldCarts.length === 0">
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-shopping-cart-off d-block mb-2" style="font-size:2rem;"></i>
                            Tidak ada cart yang ditahan.
                        </div>
                    </template>
                    <div class="d-grid gap-2">
                        <template x-for="held in heldCarts" :key="held.id">
                            <button type="button" class="pos-held-item" @click="resumeHeldCart(held)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold" x-text="held.customer.label"></div>
                                        <div class="text-muted" style="font-size:.78rem;" x-text="held.totals.item_count + ' item · ' + money(held.totals.grand_total)"></div>
                                    </div>
                                    <span class="badge bg-azure-lt text-azure" style="font-size:.7rem;">Resume</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function posScreen(config) {
    return {
        routes: config.routes,
        products: config.initialProducts || [],
        customers: config.initialCustomers || [],
        paymentMethods: config.paymentMethods || [],
        defaultCurrency: config.defaultCurrency || 'IDR',
        heldCarts: [],
        cart: {
            customer: { label: 'Walk-in Customer' },
            totals: { item_count: 0, subtotal: 0, item_discount_total: 0, order_discount_total: 0, tax_total: 0, grand_total: 0 },
            items: []
        },
        barcode: '',
        productQuery: '',
        customerQuery: '',
        voucherCode: '',
        cashReceivedAmount: '',
        payments: [{ payment_method: 'cash', amount: '', reference_number: '' }],
        discountSummary: 'Belum ada diskon.',
        appliedDiscountCount: 0,
        lastReceiptRoute: '',
        toast: { show: false, type: 'info', message: '' },
        toastTimer: null,
        showCustomerModal: false,
        showDiscountModal: false,
        showHeldModal: false,

        init() {
            this.loadCart();
            this.loadHeldCarts();
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F3') { e.preventDefault(); this.$refs.barcodeInput.focus(); }
                if (e.key === 'F9') { e.preventDefault(); this.checkout(); }
                if (e.key === 'F2') { e.preventDefault(); this.openCustomerModal(); }
                if (e.key === 'F4') { e.preventDefault(); this.openDiscountModal(); }
                if (e.key === 'Escape') { this.showCustomerModal = false; this.showDiscountModal = false; this.showHeldModal = false; }
            });
            this.$nextTick(() => this.$refs.barcodeInput.focus());
        },

        openCustomerModal() {
            this.showCustomerModal = true;
            this.$nextTick(() => this.$refs.customerSearchInput?.focus());
        },

        openDiscountModal() {
            this.showDiscountModal = true;
            this.$nextTick(() => this.$refs.voucherInput?.focus());
        },

        openHeldModal() {
            this.showHeldModal = true;
            this.loadHeldCarts();
        },

        async fetchJson(url, options = {}) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                ...options,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errorMessage = data.message || Object.values(data.errors || {}).flat()[0] || 'Request failed.';
                throw new Error(errorMessage);
            }
            return data;
        },

        async loadCart() {
            const response = await this.fetchJson(this.routes.cartActive);
            this.cart = response.data;
            if (!this.payments[0].amount) this.payments[0].amount = this.cart.totals.grand_total || '';
        },

        async refreshProducts() {
            this.productQuery = '';
            await this.searchProducts();
        },

        async searchProducts() {
            const query = this.productQuery ? '?q=' + encodeURIComponent(this.productQuery) : '';
            const response = await this.fetchJson(this.routes.productSearch + query);
            this.products = response.data || [];
        },

        async searchCustomers() {
            const query = this.customerQuery ? '?q=' + encodeURIComponent(this.customerQuery) : '';
            const response = await this.fetchJson(this.routes.customerSearch + query);
            this.customers = response.data || [];
        },

        async addProduct(product) {
            try {
                const response = await this.fetchJson(this.routes.cartItems, {
                    method: 'POST',
                    body: JSON.stringify({ product_id: product.product_id, product_variant_id: product.product_variant_id, qty: 1 })
                });
                this.cart = response.data;
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', response.message);
                this.$refs.barcodeInput.focus();
            } catch (error) { this.notify('error', error.message); }
        },

        async scanBarcode() {
            if (!this.barcode.trim()) return;
            try {
                const response = await this.fetchJson(this.routes.barcodeScan, {
                    method: 'POST',
                    body: JSON.stringify({ barcode: this.barcode })
                });
                this.cart = response.data;
                this.barcode = '';
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
            finally { this.$nextTick(() => this.$refs.barcodeInput.focus()); }
        },

        async changeQty(item, delta) {
            const next = Number(item.qty) + delta;
            if (next <= 0) return this.removeItem(item);
            return this.setQty(item, next);
        },

        async setQty(item, qty) {
            try {
                const response = await this.fetchJson(`${this.routes.cartItemBase}/${item.id}`, {
                    method: 'PATCH', body: JSON.stringify({ qty: qty })
                });
                this.cart = response.data;
                this.payments[0].amount = this.cart.totals.grand_total || '';
            } catch (error) { this.notify('error', error.message); }
        },

        async removeItem(item) {
            try {
                const response = await this.fetchJson(`${this.routes.cartItemBase}/${item.id}`, { method: 'DELETE' });
                this.cart = response.data;
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async assignCustomer(customer) {
            try {
                const response = await this.fetchJson(this.routes.cartUpdate, {
                    method: 'PATCH', body: JSON.stringify({ contact_id: customer.id })
                });
                this.cart = response.data;
                this.showCustomerModal = false;
                this.customerQuery = '';
                this.notify('success', 'Customer: ' + customer.name);
            } catch (error) { this.notify('error', error.message); }
        },

        async setWalkIn() {
            try {
                const response = await this.fetchJson(this.routes.cartUpdate, {
                    method: 'PATCH', body: JSON.stringify({ contact_id: null, customer_label: 'Walk-in Customer' })
                });
                this.cart = response.data;
                this.showCustomerModal = false;
                this.notify('info', 'Walk-in customer.');
            } catch (error) { this.notify('error', error.message); }
        },

        async clearCart() {
            if (!confirm('Clear current cart?')) return;
            try {
                const response = await this.fetchJson(this.routes.cartClear, { method: 'DELETE' });
                this.cart = response.data;
                this.discountSummary = 'Belum ada diskon.';
                this.appliedDiscountCount = 0;
                this.voucherCode = '';
                this.payments = [{ payment_method: 'cash', amount: '', reference_number: '' }];
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async holdCart() {
            try {
                const response = await this.fetchJson(this.routes.heldStore, {
                    method: 'POST', body: JSON.stringify({ label: this.cart.customer.label })
                });
                this.cart = response.active;
                this.discountSummary = 'Belum ada diskon.';
                this.appliedDiscountCount = 0;
                this.voucherCode = '';
                this.payments = [{ payment_method: 'cash', amount: '', reference_number: '' }];
                await this.loadHeldCarts();
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async loadHeldCarts() {
            try {
                const response = await this.fetchJson(this.routes.heldIndex);
                this.heldCarts = response.data || [];
            } catch (error) { this.notify('error', error.message); }
        },

        async resumeHeldCart(held) {
            try {
                const response = await this.fetchJson(`${this.routes.heldResumeBase}/${held.id}/resume`, { method: 'POST' });
                this.cart = response.data;
                this.payments = [{ payment_method: 'cash', amount: this.cart.totals.grand_total || '', reference_number: '' }];
                await this.loadHeldCarts();
                this.showHeldModal = false;
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async evaluateDiscounts() {
            if (!this.cart.items.length) return this.notify('error', 'Cart kosong.');
            try {
                const response = await this.fetchJson(this.routes.discountEvaluate, {
                    method: 'POST',
                    body: JSON.stringify({
                        voucher_code: this.voucherCode || null,
                        customer: this.cart.customer.contact_id
                            ? { reference_type: 'contact', reference_id: String(this.cart.customer.contact_id) }
                            : null,
                        items: this.cart.items.map((item) => ({
                            line_key: item.uuid,
                            product_id: item.product_id,
                            variant_id: item.product_variant_id,
                            quantity: item.qty,
                            unit_price: item.unit_price,
                            subtotal: item.qty * item.unit_price,
                        })),
                    }),
                });
                this.cart = response.cart;
                const applied = response.data.applied_discounts || [];
                this.appliedDiscountCount = applied.length;
                this.discountSummary = applied.length
                    ? `${applied.length} diskon diterapkan, total ${this.money(response.data.discount_total)}`
                    : 'Tidak ada diskon yang berlaku.';
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', 'Diskon dihitung ulang.');
            } catch (error) { this.notify('error', error.message); }
        },

        addPaymentRow() { this.payments.push({ payment_method: 'cash', amount: '', reference_number: '' }); },
        removePaymentRow(index) { this.payments.splice(index, 1); },

        requiresReference(methodCode) {
            const method = this.paymentMethods.find((m) => m.code === methodCode);
            return method ? method.requires_reference : false;
        },

        hasCashPayment() {
            return this.payments.some((p) => p.payment_method === 'cash');
        },

        async checkout() {
            if (!this.cart.items.length) return this.notify('error', 'Cart kosong.');
            try {
                const response = await this.fetchJson(this.routes.checkout, {
                    method: 'POST',
                    body: JSON.stringify({
                        payments: this.payments.map((p) => ({
                            payment_method: p.payment_method,
                            amount: Number(p.amount || 0),
                            reference_number: p.reference_number || null,
                        })),
                        cash_received_amount: Number(this.cashReceivedAmount || 0),
                    }),
                });
                this.lastReceiptRoute = response.data.receipt_print_route;
                this.notify('success', `${response.message} · ${response.data.sale_number}`);
                window.open(response.data.receipt_route, '_blank');
                await this.loadCart();
                await this.loadHeldCarts();
                this.voucherCode = '';
                this.discountSummary = 'Belum ada diskon.';
                this.appliedDiscountCount = 0;
                this.cashReceivedAmount = '';
                this.payments = [{ payment_method: 'cash', amount: '', reference_number: '' }];
            } catch (error) { this.notify('error', error.message); }
        },

        // Strip leading zeros, return clean integer string (or '' if empty/zero)
        cleanNumber(value) {
            const num = parseInt(value, 10);
            if (isNaN(num) || num === 0) return '';
            return String(num);
        },

        changePreview() {
            const cashPayment = this.payments
                .filter((p) => p.payment_method === 'cash')
                .reduce((sum, p) => sum + Number(p.amount || 0), 0);
            const received = Number(this.cashReceivedAmount || 0);
            if (received <= 0 || cashPayment <= 0) return 0;
            return Math.max(0, received - cashPayment);
        },

        money(value, currency = null) {
            const resolvedCurrency = (currency || this.cart.currency_code || this.defaultCurrency || 'IDR').toUpperCase();
            const localeMap = { IDR: 'id-ID', USD: 'en-US', SGD: 'en-SG', EUR: 'de-DE' };
            const fractionDigits = resolvedCurrency === 'IDR' ? 0 : 2;
            return new Intl.NumberFormat(localeMap[resolvedCurrency] || 'id-ID', {
                style: 'currency', currency: resolvedCurrency,
                maximumFractionDigits: fractionDigits, minimumFractionDigits: fractionDigits,
            }).format(Number(value || 0));
        },

        notify(type, message) {
            this.toast = { show: true, type, message };
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toast.show = false; }, 2400);
        },
    };
}
</script>
@endpush
