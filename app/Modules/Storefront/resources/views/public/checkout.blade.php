<x-guest-layout>
    @php($brand = $storefrontBrand)
    <div class="container py-4 py-lg-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')

        <div class="mb-4">
            <a href="{{ route('storefront.public.cart') }}" class="text-decoration-none small">&larr; Kembali ke cart</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-uppercase text-muted small fw-semibold mb-2">Checkout</div>
                        <h1 class="h3 mb-4">Lengkapi data pemesanan</h1>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('storefront.public.checkout.cart') }}" class="row g-4">
                            @csrf
                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light-subtle">
                                    <div class="fw-semibold mb-3">Customer</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">No. HP</label>
                                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light-subtle">
                                    <div class="fw-semibold mb-3">Pengiriman</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Fulfillment</label>
                                            <select name="fulfillment_method" class="form-select">
                                                <option value="pickup" @selected(old('fulfillment_method', 'pickup') === 'pickup')>Pickup</option>
                                                <option value="delivery" @selected(old('fulfillment_method') === 'delivery')>Delivery</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Alamat Pengiriman / Instruksi Pickup</label>
                                            <textarea name="customer_address" class="form-control" rows="3">{{ old('customer_address') }}</textarea>
                                            @if($activeShippingMeta && ($activeShippingMeta['ready'] ?? false) && $activeShippingProvider)
                                                <div class="form-text">Delivery akan menghitung ongkir melalui {{ $activeShippingLabel ?: strtoupper($activeShippingProvider) }} setelah tujuan dan rate dipilih.</div>
                                            @endif
                                        </div>
                                        @if($activeShippingMeta && $activeShippingProvider === 'biteship')
                                            <div class="col-md-6">
                                                <label class="form-label">Kode Pos Tujuan</label>
                                                <input type="text" name="destination_postal_code" class="form-control" value="{{ old('destination_postal_code') }}" placeholder="Contoh: 12240">
                                            </div>
                                        @endif
                                        @if($activeShippingMeta && $activeShippingProvider === 'rajaongkir')
                                            <div class="col-md-6">
                                                <label class="form-label">Area ID Tujuan</label>
                                                <input type="text" name="destination_area_id" class="form-control" value="{{ old('destination_area_id') }}" placeholder="Contoh: 501">
                                            </div>
                                        @endif
                                        @if($activeShippingMeta && $activeShippingProvider)
                                            <div class="col-md-6">
                                                <label class="form-label">Kurir</label>
                                                <input type="text" name="couriers" class="form-control" value="{{ old('couriers') }}" placeholder="Kosongkan untuk default provider">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!empty($shippingSelectionOptions))
                                <div class="col-12">
                                    <div class="border rounded-4 p-3">
                                        <div class="fw-semibold mb-2">Pilih Layanan Pengiriman</div>
                                        <div class="d-flex flex-column gap-2">
                                            @foreach($shippingSelectionOptions as $option)
                                                <label class="border rounded-3 px-3 py-2 d-flex justify-content-between gap-3 align-items-center">
                                                    <span>
                                                        <input class="form-check-input me-2" type="radio" name="selected_shipping_rate" value="{{ $option['selection_key'] }}" @checked(old('selected_shipping_rate') === $option['selection_key'])>
                                                        <span class="fw-medium">{{ $option['courier_name'] }} &middot; {{ $option['service_name'] }}</span>
                                                        <span class="text-muted small d-block ms-4">{{ $option['etd'] ?: 'Estimasi belum tersedia' }}</span>
                                                    </span>
                                                    <span class="fw-semibold">Rp{{ number_format((float) $option['price'], 0, ',', '.') }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="border rounded-4 p-3 bg-light-subtle">
                                    <div class="fw-semibold mb-3">Pembayaran</div>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Metode Pembayaran</label>
                                            <select name="payment_method" class="form-select">
                                                @if($activeGatewayMeta && ($activeGatewayMeta['ready'] ?? false) && $activeGatewayProvider && $activeGatewayLabel)
                                                    <option value="{{ $activeGatewayProvider }}" @selected(old('payment_method', $activeGatewayProvider) === $activeGatewayProvider)>{{ $activeGatewayLabel }}</option>
                                                @endif
                                                <option value="manual" @selected(old('payment_method', ($activeGatewayMeta && ($activeGatewayMeta['ready'] ?? false)) ? $activeGatewayProvider : 'manual') === 'manual')>Manual / Bayar Nanti</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Catatan</label>
                                            <textarea name="customer_note" class="form-control" rows="2">{{ old('customer_note') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <div class="text-muted small">Dengan melanjutkan checkout, Anda membuat order resmi untuk diproses oleh toko.</div>
                                <button type="submit" class="btn btn-lg rounded-pill px-4" style="background:#223756; color:#fff;">Buat Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-3">Ringkasan Order</div>
                        <div class="d-flex flex-column gap-3">
                            @foreach($items as $item)
                                @php($product = $item['product'])
                                <div class="d-flex gap-3">
                                    <div class="rounded-4 overflow-hidden bg-light flex-shrink-0" style="width:72px; height:72px;">
                                        @if($productImageUrls[$product->id] ?? null)
                                            <img src="{{ $productImageUrls[$product->id] }}" alt="{{ $product->name }}" style="width:100%; height:100%; object-fit:cover;">
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $product->name }}</div>
                                        <div class="text-muted small">{{ $item['qty'] }} x Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="fw-semibold">Rp{{ number_format((float) $item['line_total'], 0, ',', '.') }}</div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">Rp{{ number_format((float) $cartSubtotal, 0, ',', '.') }}</span>
                        </div>
                        @if(!empty($shippingSelectionOptions) && old('selected_shipping_rate'))
                            @php($selectedOption = collect($shippingSelectionOptions)->firstWhere('selection_key', old('selected_shipping_rate')))
                            @if($selectedOption)
                                <div class="d-flex justify-content-between mt-2">
                                    <span class="text-muted">Ongkir</span>
                                    <span class="fw-semibold">Rp{{ number_format((float) $selectedOption['price'], 0, ',', '.') }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
