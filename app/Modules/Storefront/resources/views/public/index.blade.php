<x-guest-layout>
    @php
        $tenant = \App\Support\TenantContext::currentTenant();
        $brand = $storefrontBrand;
    @endphp

    <div class="container py-4 py-lg-5">
        @include('storefront::public.partials.header', ['brand' => $brand, 'cartCount' => $cartCount ?? 0])
        @include('storefront::public.partials.flash')

        @if(data_get($sections['hero'] ?? null, 'enabled', true))
        <section class="rounded-4 overflow-hidden mb-5 border" style="background: linear-gradient(135deg, #f7f9fc 0%, #e8eef7 54%, #dbe4f1 100%); border-color: rgba(34, 55, 86, .10) !important;">
            <div class="p-4 p-lg-5">
                <div class="rounded-4 p-4 p-lg-5" style="background: rgba(255,255,255,.52); border: 1px solid rgba(34,55,86,.08);">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing: .18em; color: #6a7f9e;">{{ $tenant?->slug ?: 'store' }}</div>
                            <h1 class="display-6 fw-bold mb-3" style="color: {{ $brand['accent'] ?? '#223756' }};">{{ $brand['hero_title'] ?? $brand['name'] }}</h1>
                            <p class="lead mb-4" style="max-width: 760px; color: #526784;">{{ $brand['hero_subtitle'] ?: 'Koleksi produk, layanan, dan pilihan custom order yang bisa langsung dijelajahi dalam satu tempat.' }}</p>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <span class="badge rounded-pill px-3 py-2" style="background: #fff; color: #223756;">{{ $storefrontStats['total'] }} produk aktif</span>
                                <span class="badge rounded-pill px-3 py-2" style="background: rgba(54, 82, 122, .10); color: #36527a;">{{ $storefrontStats['physical_label'] }}</span>
                                <span class="badge rounded-pill px-3 py-2" style="background: rgba(54, 82, 122, .10); color: #36527a;">{{ $storefrontStats['digital_label'] }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-3">
                                <a href="#catalog" class="btn btn-lg px-4 rounded-pill" style="background: {{ $brand['accent'] ?? '#223756' }}; color: #fff; border: 1px solid {{ $brand['accent'] ?? '#223756' }};">Jelajahi Katalog</a>
                                <a href="{{ route('storefront.public.cart') }}" class="btn btn-lg px-4 rounded-pill" style="background: #eef3f9; color: #223756; border: 1px solid rgba(34, 55, 86, .14);">Buka Cart</a>
                                @foreach(($brand['cta_links'] ?? []) as $cta)
                                    <a href="{{ $cta['url'] }}" class="btn btn-lg px-4 rounded-pill btn-outline-secondary">{{ $cta['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="rounded-4 bg-white p-4 shadow-sm h-100" style="border: 1px solid rgba(34,55,86,.08);">
                                <div class="text-uppercase text-muted small fw-semibold mb-2">Cari Produk</div>
                                <form method="GET" action="{{ route('storefront.public.index') }}" class="row g-3">
                                    <div class="col-12">
                                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-lg" placeholder="Cari produk, jasa, atau SKU">
                                    </div>
                                    <div class="col-md-7">
                                        <select name="type" class="form-select form-select-lg">
                                            <option value="">Semua tipe</option>
                                            <option value="simple" @selected(($filters['type'] ?? '') === 'simple')>Produk</option>
                                            <option value="digital" @selected(($filters['type'] ?? '') === 'digital')>Digital</option>
                                            <option value="service" @selected(($filters['type'] ?? '') === 'service')>Jasa</option>
                                            <option value="custom" @selected(($filters['type'] ?? '') === 'custom')>Custom</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 d-grid">
                                        <button type="submit" class="btn btn-lg rounded-pill" style="background:#223756; color:#fff;">Cari</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if(data_get($sections['about'] ?? null, 'enabled', true) && $brand['description'])
            <section class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="text-uppercase text-muted small fw-semibold mb-2">About</div>
                        <h2 class="h3 mb-3">{{ $brand['name'] }}</h2>
                        <div class="text-muted">{{ $brand['description'] }}</div>
                    </div>
                </div>
            </section>
        @endif

        @if(data_get($sections['featured_offers'] ?? null, 'enabled', true) && $featuredProducts->isNotEmpty())
            <section class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold">Pilihan Unggulan</div>
                        <h2 class="h4 mb-0">Pilihan unggulan</h2>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach($featuredProducts as $product)
                        @php($image = $featuredImageUrls[$product->id] ?? null)
                        <div class="col-md-4">
                            @include('storefront::public.partials.product-card', ['product' => $product, 'image' => $image])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(data_get($sections['catalog'] ?? null, 'enabled', true))
        <section id="catalog">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-uppercase text-muted small fw-semibold">Katalog</div>
                    <h2 class="h3 mb-1">Semua produk dan layanan</h2>
                    <div class="text-muted">Temukan produk fisik, digital, jasa, dan kebutuhan custom dalam satu katalog.</div>
                </div>
                @if(($filters['q'] ?? '') !== '' || ($filters['type'] ?? '') !== '')
                    <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary rounded-pill px-3">Reset Filter</a>
                @endif
            </div>

            <div class="row g-4">
            @forelse($products as $product)
                @php($image = $productImageUrls[$product->id] ?? null)
                <div class="col-md-6 col-xl-4">
                    @include('storefront::public.partials.product-card', ['product' => $product, 'image' => $image])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-secondary mb-0">Belum ada produk yang cocok dengan pencarian Anda.</div>
                </div>
            @endforelse
            </div>

            <div class="mt-4">
                {{ $products->withQueryString()->links() }}
            </div>
        </section>
        @endif

        @if(data_get($sections['testimonials'] ?? null, 'enabled', false) && !empty($brand['testimonials']))
            <section class="mt-5">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Testimonials</div>
                <div class="row g-3">
                    @foreach($brand['testimonials'] as $testimonial)
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="mb-3">"{{ $testimonial['quote'] }}"</div>
                                    <div class="small text-muted">{{ $testimonial['author'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(data_get($sections['faq'] ?? null, 'enabled', false) && !empty($brand['faq']))
            <section class="mt-5">
                <div class="text-uppercase text-muted small fw-semibold mb-2">FAQ</div>
                <div class="row g-3">
                    @foreach($brand['faq'] as $faq)
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="fw-semibold mb-2">{{ $faq['question'] }}</div>
                                    <div class="text-muted">{{ $faq['answer'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(data_get($sections['footer_links'] ?? null, 'enabled', true) && !empty($brand['footer_links']))
            <section class="mt-5">
                <div class="d-flex flex-wrap gap-3 justify-content-center pt-4 border-top">
                    @foreach($brand['footer_links'] as $link)
                        <a href="{{ $link['url'] }}" class="text-decoration-none">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-guest-layout>
