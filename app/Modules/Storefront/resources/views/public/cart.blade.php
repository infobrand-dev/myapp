<x-guest-layout>
    @php($brand = $storefrontBrand)
    <div class="container py-4 py-lg-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-end justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold">Cart</div>
                        <h1 class="h3 mb-1">Belanja Anda</h1>
                        <div class="text-muted">Atur jumlah item sebelum lanjut ke checkout.</div>
                    </div>
                    @if(($cartCount ?? 0) > 0)
                        <form method="POST" action="{{ route('storefront.public.cart.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary rounded-pill px-3">Kosongkan Cart</button>
                        </form>
                    @endif
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @forelse($items as $item)
                            @php($product = $item['product'])
                            @php($image = $productImageUrls[$product->id] ?? null)
                            <div class="p-4 border-bottom">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-2">
                                        @if($image)
                                            <img src="{{ $image }}" alt="{{ $product->name }}" class="img-fluid rounded-4" style="height: 92px; width: 100%; object-fit: cover;">
                                        @else
                                            <div class="rounded-4 bg-light d-flex align-items-center justify-content-center text-muted" style="height: 92px;">Tanpa gambar</div>
                                        @endif
                                    </div>
                                    <div class="col-md-5">
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        <div class="text-muted small">{{ strtoupper((string) $product->type) }}</div>
                                        <div class="text-muted small">Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="POST" action="{{ route('storefront.public.cart.update', $product) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="number" min="0" max="999" name="qty" value="{{ $item['qty'] }}" class="form-control">
                                            <button type="submit" class="btn btn-outline-secondary">Update</button>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-md-end">
                                        <div class="fw-semibold mb-2">Rp{{ number_format((float) $item['line_total'], 0, ',', '.') }}</div>
                                        <form method="POST" action="{{ route('storefront.public.cart.remove', $product) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-5 text-center">
                                <div class="h5 mb-2">Cart masih kosong</div>
                                <div class="text-muted mb-3">Tambahkan produk atau layanan dari katalog untuk mulai checkout.</div>
                                <a href="{{ route('storefront.public.index') }}" class="btn btn-primary rounded-pill px-4">Jelajahi Katalog</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-3">Ringkasan Cart</div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Jumlah item</span>
                            <span>{{ (int) ($cartCount ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">Rp{{ number_format((float) $cartSubtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('storefront.public.checkout') }}" class="btn btn-lg rounded-pill @if(($cartCount ?? 0) === 0) disabled @endif" style="background:#223756; color:#fff;">Lanjut ke Checkout</a>
                            <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary rounded-pill">Tambah Produk Lagi</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
