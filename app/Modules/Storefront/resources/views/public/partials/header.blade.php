<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('storefront.public.index') }}" class="text-decoration-none text-reset d-flex align-items-center gap-3">
            <div class="rounded-4 overflow-hidden bg-white border d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 56px; height: 56px; border-color: rgba(34,55,86,.10) !important;">
                @if($brand['logo_url'])
                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                @else
                    <div class="fw-bold" style="font-size: 1rem; color: #36527a;">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brand['name'], 0, 2)) }}</div>
                @endif
            </div>
            <div>
                <div class="fw-semibold">{{ $brand['name'] }}</div>
                @if(!empty($brand['description']))
                    <div class="text-muted small">{{ $brand['description'] }}</div>
                @endif
            </div>
        </a>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('storefront.public.cart') }}" class="btn btn-outline-secondary rounded-pill px-3">
            Cart
            @if(($cartCount ?? 0) > 0)
                <span class="badge rounded-pill ms-2" style="background:#223756; color:#fff;">{{ $cartCount }}</span>
            @endif
        </a>
        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Masuk</a>
    </div>
</div>
