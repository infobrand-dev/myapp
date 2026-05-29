<div class="card h-100 border-0 shadow-sm" style="border-radius: 1.25rem;">
    @if($image)
        <img src="{{ $image }}" alt="{{ $product->name }}" class="card-img-top" style="height: 240px; object-fit: cover;">
    @else
        <div class="d-flex align-items-center justify-content-center text-muted" style="height: 240px; background: #eef2f6;">Tanpa gambar</div>
    @endif
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <div class="small text-muted">{{ strtoupper((string) $product->type) }}</div>
            @if($product->track_stock)
                <span class="badge rounded-pill text-bg-light">Fisik</span>
            @else
                <span class="badge rounded-pill text-bg-light">Digital / Jasa</span>
            @endif
        </div>
        <h2 class="h5 mb-2">{{ $product->name }}</h2>
        @if($product->description)
            <p class="text-muted small flex-grow-1">{{ \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 140) }}</p>
        @else
            <div class="flex-grow-1"></div>
        @endif
        <div class="fw-bold fs-5 mt-2">Rp{{ number_format((float) $product->sell_price, 0, ',', '.') }}</div>
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('storefront.public.products.show', $product) }}" class="btn btn-outline-secondary rounded-pill px-3 flex-grow-1">Lihat</a>
            <form method="POST" action="{{ route('storefront.public.cart.add', $product) }}" class="flex-grow-1">
                @csrf
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="redirect" value="cart">
                <button type="submit" class="btn btn-primary rounded-pill px-3 w-100">Tambah</button>
            </form>
        </div>
    </div>
</div>
