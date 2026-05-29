<x-guest-layout>
    @php($brand = $storefrontBrand)
    <div class="container py-4 py-lg-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')

        <div class="mb-4">
            <a href="{{ route('storefront.public.index') }}" class="text-decoration-none small">&larr; Kembali ke katalog</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    @if($productImageUrl)
                        <img src="{{ $productImageUrl }}" alt="{{ $product->name }}" class="card-img-top" style="height: 540px; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height: 540px;">Tanpa gambar</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="small text-muted">{{ strtoupper((string) $product->type) }}</span>
                            @if($product->track_stock)
                                <span class="badge rounded-pill text-bg-light">Produk Fisik</span>
                            @else
                                <span class="badge rounded-pill text-bg-light">Digital / Jasa</span>
                            @endif
                        </div>
                        <h1 class="display-6 fw-bold mb-2" style="color:#223756;">{{ $product->name }}</h1>
                        <div class="h3 mb-4" style="color:#223756;">Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>

                        @if($product->description)
                            <div class="text-muted mb-4">{!! nl2br(e((string) $product->description)) !!}</div>
                        @endif

                        <form method="POST" action="{{ route('storefront.public.cart.add', $product) }}" class="row g-3 mb-3">
                            @csrf
                            <div class="col-sm-4">
                                <label class="form-label">Qty</label>
                                <input type="number" min="1" max="999" name="qty" class="form-control form-control-lg" value="1">
                            </div>
                            <div class="col-sm-8 d-flex align-items-end">
                                <input type="hidden" name="redirect" value="cart">
                                <button type="submit" class="btn btn-lg rounded-pill w-100" style="background:#223756; color:#fff;">Tambah ke Cart</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('storefront.public.buy-now', $product) }}">
                            @csrf
                            <input type="hidden" name="qty" value="1">
                            <button type="submit" class="btn btn-outline-secondary btn-lg rounded-pill w-100">Checkout Sekarang</button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-3">Ringkasan belanja</div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Checkout online</span>
                            <span>{{ ($activeGatewayMeta['label'] ?? null) ?: 'Manual / Bayar Nanti' }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Pengiriman</span>
                            <span>{{ ($activeShippingMeta['label'] ?? null) ?: 'Manual / Sesuai kesepakatan' }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Status cart</span>
                            <span>{{ (int) ($cartCount ?? 0) }} item</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
