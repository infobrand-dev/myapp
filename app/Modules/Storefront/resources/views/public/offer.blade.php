<x-guest-layout>
    @php($brand = $storefrontBrand)
    <div class="container py-4 py-lg-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')

        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <div class="rounded-4 overflow-hidden border bg-white">
                    @if($productImageUrl)
                        <img src="{{ $productImageUrl }}" alt="{{ $offer['headline'] }}" class="w-100" style="aspect-ratio: 4 / 3; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio: 4 / 3;">{{ $offer['headline'] }}</div>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.18em; color: {{ $brand['accent'] ?? '#223756' }};">Direct Offer</div>
                <h1 class="display-6 fw-bold mb-3" style="color: {{ $brand['accent'] ?? '#223756' }};">{{ $offer['headline'] }}</h1>
                @if($offer['subtitle'])
                    <p class="lead text-muted mb-3">{{ $offer['subtitle'] }}</p>
                @endif
                <div class="h3 mb-4">Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>

                @if($offer['slot_note'])
                    <div class="alert alert-light border">{{ $offer['slot_note'] }}</div>
                @endif

                @include('storefront::public.partials.buy-now-form', ['checkoutChannel' => 'direct_offer'])

                @if($offer['delivery_instructions'] || $offer['download_url'] || $offer['external_url'])
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Delivery Flow</div>
                            @if($offer['delivery_instructions'])
                                <p class="text-muted mb-2">{{ $offer['delivery_instructions'] }}</p>
                            @endif
                            @if($offer['download_url'])
                                <div class="small text-muted">Download akan dibuka setelah pembayaran di halaman order signed.</div>
                            @endif
                            @if($offer['external_url'])
                                <div class="small text-muted">Link external juga hanya ditampilkan setelah pembayaran.</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
