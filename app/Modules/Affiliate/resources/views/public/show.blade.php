<x-guest-layout>
    <div class="container py-4 py-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="text-uppercase small fw-semibold" style="letter-spacing:.18em; color: {{ $brand['accent'] ?? '#223756' }};">Affiliate Landing</div>
                <div class="text-muted small">{{ $brand['name'] }} merekomendasikan offer dari {{ $sellerName ?: 'seller' }}</div>
            </div>
            <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary rounded-pill">Kembali ke Brand Page</a>
        </div>

        <section class="rounded-4 overflow-hidden border shadow-sm">
            <div class="row g-0">
                <div class="col-lg-6">
                    @if($productImageUrl)
                        <img src="{{ $productImageUrl }}" alt="{{ $headline }}" class="w-100 h-100" style="min-height: 360px; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted h-100" style="min-height: 360px;">{{ $headline }}</div>
                    @endif
                </div>
                <div class="col-lg-6">
                    <div class="p-4 p-lg-5">
                        <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.18em; color: {{ $brand['accent'] ?? '#223756' }};">{{ optional($listing->user)->name ?: 'Affiliator Meetra' }}</div>
                        <h1 class="display-6 fw-bold mb-3" style="color: {{ $brand['accent'] ?? '#223756' }};">{{ $headline }}</h1>
                        @if($subtitle !== '')
                            <p class="lead text-muted mb-4">{{ $subtitle }}</p>
                        @endif

                        <div class="h3 mb-3">Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>
                        <div class="text-muted small mb-4">Checkout dan pembayaran tetap diproses di halaman seller asli agar attribution, order, dan settlement tetap akurat.</div>

                        @if($purchaseUrl)
                            <a href="{{ $purchaseUrl }}" class="btn btn-lg rounded-pill px-4" style="background: {{ $brand['accent'] ?? '#223756' }}; color: #fff;">{{ $ctaLabel }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
