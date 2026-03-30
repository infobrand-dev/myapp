@extends('layouts.admin')

@section('content')
@php
    $defaultCurrency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<style>
    .pos-shell { --pos-accent:#0f766e; --pos-accent-rgb:15,118,110; --pos-ink:#16302b; --pos-panel:#fffdf9; --pos-line:#e7dccd; min-height:calc(100vh - 6.5rem); background:radial-gradient(circle at top left, rgba(var(--pos-accent-rgb),.10), transparent 24rem), linear-gradient(180deg,#faf5ee 0%,#f5f0e7 100%); border-radius:1.5rem; padding:1rem; }
    .pos-topbar { background:linear-gradient(135deg,#0f766e,#155e75); color:#fff; border-radius:1.25rem; padding:1rem 1.25rem; box-shadow:0 1rem 2rem rgba(15,118,110,.18); }
    .pos-panel { background:var(--pos-panel); border:1px solid var(--pos-line); border-radius:1.25rem; box-shadow:0 .75rem 1.5rem rgba(36,34,30,.06); }
    .pos-section-title { color:var(--pos-ink); font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .pos-search-input, .pos-barcode-input { height:3.25rem; border-radius:1rem; border:1px solid #d9c8b4; background:#fff; padding-inline:1rem; font-size:1rem; }
    .pos-barcode-input { font-weight:700; letter-spacing:.04em; background:#fffdf8; }
    .pos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:.9rem; }
    .pos-product-card { border:1px solid var(--pos-line); border-radius:1rem; background:linear-gradient(180deg,#fff,#fbf7f0); padding:.95rem; min-height:150px; transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease; cursor:pointer; }
    .pos-product-card:hover { transform:translateY(-2px); border-color:rgba(var(--pos-accent-rgb),.35); box-shadow:0 .85rem 1.35rem rgba(15,118,110,.10); }
    .pos-cart-panel { position:sticky; top:1rem; }
    .pos-cart-line, .pos-held-item, .pos-customer-item, .pos-payment-row { border:1px solid var(--pos-line); border-radius:1rem; background:#fff; padding:.85rem; }
    .pos-qty-box { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #dcccb9; border-radius:.8rem; padding:.3rem; background:#fcfaf7; }
    .pos-qty-input { width:3.5rem; border:0; background:transparent; text-align:center; font-weight:700; }
    .pos-summary { background:linear-gradient(180deg,#f7f2e9,#fdfcf9); border:1px dashed #d7c5af; border-radius:1rem; padding:1rem; }
    .pos-total-figure { font-size:2rem; font-weight:800; color:var(--pos-ink); letter-spacing:-.02em; }
    .pos-action-btn { height:3.05rem; border-radius:.95rem; font-weight:700; }
    .pos-toast { position:fixed; right:1rem; bottom:1rem; z-index:1080; min-width:260px; max-width:360px; border-radius:1rem; padding:.9rem 1rem; color:#fff; box-shadow:0 1rem 2rem rgba(0,0,0,.18); }
    .pos-toast.success { background:#0f766e; } .pos-toast.error { background:#b91c1c; } .pos-toast.info { background:#1d4ed8; }
    @media (max-width: 991.98px) { .pos-shell{padding:.75rem;} .pos-cart-panel{position:static;} .pos-topbar{padding:.9rem 1rem;} }
</style>

<div class="pos-shell"
    x-data="posScreen({
        initialProducts: @json($initialProducts->map(function ($product) { return ['sellable_key' => 'product:' . $product->id, 'product_id' => $product->id, 'product_variant_id' => null, 'name' => $product->name, 'variant_name' => null, 'sku' => $product->sku, 'barcode' => $product->barcode, 'price' => (float) $product->sell_price, 'unit' => null]; })->values()),
        initialCustomers: @json($initialCustomers->map(function ($customer) { return ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->mobile ?: $customer->phone, 'email' => $customer->email]; })->values()),
        paymentMethods: @json($paymentMethods->map(function ($method) { return ['id' => $method->id, 'code' => $method->code, 'name' => $method->name, 'type' => $method->type, 'requires_reference' => (bool) $method->requires_reference]; })->values()),
        defaultCurrency: @json($defaultCurrency),
        routes: {
            cartActive: '{{ route('pos.cart.active') }}',
            cartUpdate: '{{ route('pos.cart.update') }}',
            cartClear: '{{ route('pos.cart.clear') }}',
            cartItems: '{{ route('pos.cart.items.store') }}',
            cartItemBase: '{{ url('/pos/cart/items') }}',
            barcodeScan: '{{ route('pos.barcode.scan') }}',
            heldIndex: '{{ route('pos.held.index') }}',
            heldStore: '{{ route('pos.held.store') }}',
            heldResumeBase: '{{ url('/pos/held-carts') }}',
            productSearch: '{{ route('pos.products.search') }}',
            customerSearch: '{{ route('pos.customers.search') }}',
            discountEvaluate: '{{ route('pos.discounts.evaluate') }}',
            checkout: '{{ route('pos.checkout.store') }}',
        }
    })"
    x-init="init()">

    <div class="pos-topbar mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <div class="small text-uppercase opacity-75 fw-bold">Cashier Terminal</div>
                <div class="h2 mb-1">Point Of Sale</div>
                <div class="opacity-75">Fast checkout flow for store counter and walk-in sales.</div>
            </div>
            <div class="col-lg-7">
                <div class="row g-2 text-lg-end">
                    <div class="col-6 col-lg-3"><div class="small opacity-75">Cashier</div><div class="fw-semibold">{{ auth()->user()->name }}</div></div>
                    <div class="col-6 col-lg-3"><div class="small opacity-75">Customer</div><div class="fw-semibold" x-text="cart.customer.label || 'Walk-in Customer'"></div></div>
                    <div class="col-6 col-lg-3"><div class="small opacity-75">Held Carts</div><div class="fw-semibold" x-text="heldCarts.length"></div></div>
                    <div class="col-6 col-lg-3"><div class="small opacity-75">Shortcut</div><div class="fw-semibold">F3 Scan | F9 Pay</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert {{ $activeShift ? 'alert-success' : 'alert-warning' }} mb-3">
        @if($activeShift)
            Shift aktif: <a href="{{ route('pos.shifts.show', $activeShift) }}" class="alert-link">{{ $activeShift->code }}</a>
            | Opening cash: {{ $money->format((float) $activeShift->opening_cash_amount, $activeShift->currency_code ?: $defaultCurrency) }}
        @else
            Tidak ada shift aktif. <a href="{{ route('pos.shifts.create') }}" class="alert-link">Buka shift</a> sebelum checkout POS.
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="pos-panel p-3 mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <div class="pos-section-title mb-2">Barcode Input</div>
                        <form @submit.prevent="scanBarcode()">
                            <input x-ref="barcodeInput" x-model="barcode" type="text" class="form-control pos-barcode-input" placeholder="Scan barcode or type SKU then press Enter" autocomplete="off">
                        </form>
                    </div>
                    <div class="col-lg-5">
                        <div class="pos-section-title mb-2">Product Search</div>
                        <input x-model="productQuery" @input.debounce.250ms="searchProducts()" type="text" class="form-control pos-search-input" placeholder="Search product, SKU, barcode">
                    </div>
                </div>
            </div>

            <div class="pos-panel p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div><div class="pos-section-title mb-1">Quick Products</div></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="refreshWorkspace()">Refresh</button>
                </div>
                <div class="pos-grid">
                    <template x-for="product in products" :key="product.sellable_key">
                        <button type="button" class="pos-product-card text-start" @click="addProduct(product)">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-teal-lt text-teal" x-text="product.variant_name ? 'Variant' : 'Product'"></span>
                                <span class="small text-muted" x-text="product.unit || 'Unit'"></span>
                            </div>
                            <div class="fw-bold mb-1" x-text="product.name"></div>
                            <div class="text-muted small mb-2" x-text="product.variant_name || product.sku || '-'"></div>
                            <div class="small text-muted">Barcode: <span x-text="product.barcode || '-'"></span></div>
                            <div class="mt-3 fw-bold fs-4" x-text="money(product.price)"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="pos-cart-panel">
                <div class="pos-panel p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><div class="pos-section-title mb-1">Customer</div></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="customerQuery=''; searchCustomers()">Browse</button>
                    </div>
                    <div class="input-group mb-3">
                        <input x-model="customerQuery" @input.debounce.250ms="searchCustomers()" type="text" class="form-control" placeholder="Search customer">
                        <button type="button" class="btn btn-outline-secondary" @click="setWalkIn()">Walk-in</button>
                    </div>
                    <div class="row g-2">
                        <template x-for="customer in customers" :key="customer.id">
                            <div class="col-12">
                                <button type="button" class="pos-customer-item w-100 text-start" @click="assignCustomer(customer)">
                                    <div class="fw-semibold" x-text="customer.name"></div>
                                    <div class="small text-muted" x-text="customer.phone || customer.email || '-'"></div>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pos-panel p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><div class="pos-section-title mb-1">Active Cart</div></div>
                        <span class="badge bg-orange-lt text-orange" x-text="cart.totals.item_count + ' Lines'"></span>
                    </div>
                    <div class="d-grid gap-2" style="max-height: 360px; overflow:auto;">
                        <template x-if="cart.items.length === 0"><div class="text-center text-muted py-5">Cart is empty</div></template>
                        <template x-for="item in cart.items" :key="item.uuid">
                            <div class="pos-cart-line">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="me-2">
                                        <div class="fw-semibold" x-text="item.product_name"></div>
                                        <div class="small text-muted" x-text="item.variant_name || item.sku || item.barcode || '-'"></div>
                                        <div class="small text-muted mt-1" x-text="money(item.unit_price) + ' / item'"></div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeItem(item)"><i class="ti ti-trash"></i></button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="pos-qty-box">
                                        <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary" @click="changeQty(item, -1)">-</button>
                                        <input type="number" min="0.0001" step="1" class="pos-qty-input" :value="item.qty" @change="setQty(item, $event.target.value)">
                                        <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary" @click="changeQty(item, 1)">+</button>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold" x-text="money(item.line_total)"></div>
                                        <div class="small text-success" x-show="item.discount_total > 0" x-text="'Disc ' + money(item.discount_total)"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pos-panel p-3 mb-3">
                    <div class="pos-section-title mb-2">Discount</div>
                    <div class="input-group mb-2">
                        <input x-model="voucherCode" type="text" class="form-control" placeholder="Voucher code">
                        <button type="button" class="btn btn-outline-primary" @click="evaluateDiscounts()">Apply</button>
                    </div>
                    <div class="small text-muted" x-text="discountSummary"></div>
                </div>

                <div class="pos-summary mb-3">
                    <div class="d-flex justify-content-between small mb-2"><span>Subtotal</span><span x-text="money(cart.totals.subtotal)"></span></div>
                    <div class="d-flex justify-content-between small mb-2"><span>Discount</span><span x-text="money(cart.totals.item_discount_total + cart.totals.order_discount_total)"></span></div>
                    <div class="d-flex justify-content-between small mb-2"><span>Tax</span><span x-text="money(cart.totals.tax_total)"></span></div>
                    <div class="border-top pt-3 mt-3">
                        <div class="small text-muted">Grand Total</div>
                        <div class="pos-total-figure" x-text="money(cart.totals.grand_total)"></div>
                    </div>
                </div>

                <div class="pos-panel p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="pos-section-title">Payments</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="addPaymentRow()">Add Split</button>
                    </div>
                    <div class="d-grid gap-2">
                        <template x-for="(payment, index) in payments" :key="index">
                            <div class="pos-payment-row">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <select class="form-select" x-model="payment.payment_method">
                                            <template x-for="method in paymentMethods" :key="method.code">
                                                <option :value="mapMethodToSalesInput(method.code)" x-text="method.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" step="0.01" min="0" class="form-control" x-model="payment.amount" placeholder="Amount">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-danger w-100" @click="removePaymentRow(index)" x-show="payments.length > 1">Remove</button>
                                    </div>
                                    <div class="col-md-12" x-show="requiresReference(payment.payment_method)">
                                        <input type="text" class="form-control" x-model="payment.reference_number" placeholder="Reference number">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Cash Received</label>
                        <input type="number" step="0.01" min="0" class="form-control" x-model="cashReceivedAmount" placeholder="Only for cash payment">
                        <div class="small text-muted mt-1">Change preview: <span x-text="money(changePreview())"></span></div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-outline-warning pos-action-btn" @click="holdCart()">Hold Cart</button>
                    <button type="button" class="btn btn-outline-secondary pos-action-btn" @click="clearCart()">Clear Cart</button>
                    <button type="button" class="btn btn-primary pos-action-btn" @click="checkout()">Checkout</button>
                    <a class="btn btn-outline-dark pos-action-btn d-flex align-items-center justify-content-center" :href="lastReceiptRoute || '#'" :class="{disabled: !lastReceiptRoute}">Print Receipt</a>
                </div>

                <div class="pos-panel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pos-section-title">Held Carts</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="loadHeldCarts()">Reload</button>
                    </div>
                    <div class="d-grid gap-2">
                        <template x-if="heldCarts.length === 0"><div class="text-muted small">Tidak ada cart yang ditahan.</div></template>
                        <template x-for="held in heldCarts" :key="held.id">
                            <button type="button" class="pos-held-item w-100 text-start" @click="resumeHeldCart(held)">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold" x-text="held.customer.label"></span>
                                    <span class="small text-muted" x-text="money(held.totals.grand_total)"></span>
                                </div>
                                <div class="small text-muted" x-text="held.totals.item_count + ' lines'"></div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-if="toast.show">
        <div class="pos-toast" :class="toast.type" x-text="toast.message"></div>
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
        cart: { customer: { label: 'Walk-in Customer' }, totals: { item_count: 0, subtotal: 0, item_discount_total: 0, order_discount_total: 0, tax_total: 0, grand_total: 0 }, items: [] },
        barcode: '',
        productQuery: '',
        customerQuery: '',
        voucherCode: '',
        cashReceivedAmount: '',
        payments: [{ payment_method: 'cash', amount: '', reference_number: '' }],
        discountSummary: 'No discount applied.',
        lastReceiptRoute: '',
        toast: { show: false, type: 'info', message: '' },
        toastTimer: null,

        init() {
            this.loadCart();
            this.loadHeldCarts();
            window.addEventListener('keydown', (event) => {
                if (event.key === 'F3') { event.preventDefault(); this.$refs.barcodeInput.focus(); }
                if (event.key === 'F9') { event.preventDefault(); this.checkout(); }
            });
            this.$nextTick(() => this.$refs.barcodeInput.focus());
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

        async refreshWorkspace() {
            this.productQuery = '';
            this.customerQuery = '';
            await this.searchProducts();
            await this.searchCustomers();
            await this.loadHeldCarts();
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
                const response = await this.fetchJson(this.routes.cartItems, { method: 'POST', body: JSON.stringify({ product_id: product.product_id, product_variant_id: product.product_variant_id, qty: 1 }) });
                this.cart = response.data;
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', response.message);
                this.$refs.barcodeInput.focus();
            } catch (error) { this.notify('error', error.message); }
        },

        async scanBarcode() {
            if (!this.barcode.trim()) return;
            try {
                const response = await this.fetchJson(this.routes.barcodeScan, { method: 'POST', body: JSON.stringify({ barcode: this.barcode }) });
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
                const response = await this.fetchJson(`${this.routes.cartItemBase}/${item.id}`, { method: 'PATCH', body: JSON.stringify({ qty: qty }) });
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
                const response = await this.fetchJson(this.routes.cartUpdate, { method: 'PATCH', body: JSON.stringify({ contact_id: customer.id }) });
                this.cart = response.data;
                this.notify('success', 'Customer assigned.');
            } catch (error) { this.notify('error', error.message); }
        },

        async setWalkIn() {
            try {
                const response = await this.fetchJson(this.routes.cartUpdate, { method: 'PATCH', body: JSON.stringify({ contact_id: null, customer_label: 'Walk-in Customer' }) });
                this.cart = response.data;
                this.notify('info', 'Walk-in customer selected.');
            } catch (error) { this.notify('error', error.message); }
        },

        async clearCart() {
            if (!confirm('Clear current cart?')) return;
            try {
                const response = await this.fetchJson(this.routes.cartClear, { method: 'DELETE' });
                this.cart = response.data;
                this.discountSummary = 'No discount applied.';
                this.voucherCode = '';
                this.payments = [{ payment_method: 'cash', amount: '', reference_number: '' }];
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async holdCart() {
            try {
                const response = await this.fetchJson(this.routes.heldStore, { method: 'POST', body: JSON.stringify({ label: this.cart.customer.label }) });
                this.cart = response.active;
                this.discountSummary = 'No discount applied.';
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
                this.notify('success', response.message);
            } catch (error) { this.notify('error', error.message); }
        },

        async evaluateDiscounts() {
            if (!this.cart.items.length) return this.notify('error', 'Cart is empty.');
            try {
                const response = await this.fetchJson(this.routes.discountEvaluate, {
                    method: 'POST',
                    body: JSON.stringify({
                        voucher_code: this.voucherCode || null,
                        customer: this.cart.customer.contact_id ? { reference_type: 'contact', reference_id: String(this.cart.customer.contact_id) } : null,
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
                const appliedCount = (response.data.applied_discounts || []).length;
                this.discountSummary = appliedCount ? `${appliedCount} discount(s) applied, total ${this.money(response.data.discount_total)}` : 'No eligible discount found.';
                this.payments[0].amount = this.cart.totals.grand_total || '';
                this.notify('success', 'Discount recalculated.');
            } catch (error) { this.notify('error', error.message); }
        },

        addPaymentRow() { this.payments.push({ payment_method: 'cash', amount: '', reference_number: '' }); },
        removePaymentRow(index) { this.payments.splice(index, 1); },

        requiresReference(methodCode) {
            const method = this.paymentMethods.find((item) => item.code === methodCode);
            return method ? method.requires_reference : false;
        },
        mapMethodToSalesInput(code) { return code; },
        mapSalesInputToMethod(code) { return code; },

        async checkout() {
            if (!this.cart.items.length) return this.notify('error', 'Cart is empty.');
            try {
                const response = await this.fetchJson(this.routes.checkout, {
                    method: 'POST',
                    body: JSON.stringify({
                        payments: this.payments.map((payment) => ({
                            payment_method: payment.payment_method,
                            amount: Number(payment.amount || 0),
                            reference_number: payment.reference_number || null,
                        })),
                        cash_received_amount: Number(this.cashReceivedAmount || 0),
                    }),
                });
                this.lastReceiptRoute = response.data.receipt_print_route;
                this.notify('success', `${response.message} Invoice ${response.data.sale_number}`);
                window.open(response.data.receipt_route, '_blank');
                await this.loadCart();
                await this.loadHeldCarts();
                this.voucherCode = '';
                this.discountSummary = 'No discount applied.';
                this.cashReceivedAmount = '';
                this.payments = [{ payment_method: 'cash', amount: '', reference_number: '' }];
            } catch (error) { this.notify('error', error.message); }
        },

        changePreview() {
            const cashPayment = this.payments.filter((payment) => payment.payment_method === 'cash').reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
            const received = Number(this.cashReceivedAmount || 0);
            if (received <= 0 || cashPayment <= 0) return 0;
            return Math.max(0, received - cashPayment);
        },

        money(value, currency = null) {
            const resolvedCurrency = (currency || this.cart.currency_code || this.defaultCurrency || 'IDR').toUpperCase();
            const localeMap = { IDR: 'id-ID', USD: 'en-US', SGD: 'en-SG', EUR: 'de-DE' };
            const fractionDigits = resolvedCurrency === 'IDR' ? 0 : 2;
            return new Intl.NumberFormat(localeMap[resolvedCurrency] || 'id-ID', { style: 'currency', currency: resolvedCurrency, maximumFractionDigits: fractionDigits, minimumFractionDigits: fractionDigits }).format(Number(value || 0));
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
