@extends('layouts.landing')

@section('head_title', config('app.name') . ' Commerce - Storefront, order, shipping, dan fulfillment')
@section('head_description', 'Commerce membantu bisnis menerima order lebih rapi lewat storefront, payment status, shipping, fulfillment, affiliate, dan wallet dalam satu workspace.')

@section('content')
@php
    $plans = collect($publicPlans ?? []);
@endphp

<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#fff7ed 0%,#fffbeb 52%,#f8fafc 100%);border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <div class="landing-badge mb-4">
                    <i class="ti ti-shopping-bag"></i> Commerce
                </div>
                <h1 class="landing-headline mb-4">
                    Jalankan <span>storefront dan operasional order</span> dalam satu workspace yang lebih rapi.
                </h1>
                <p class="landing-subtext mb-4">
                    Commerce dirancang untuk bisnis yang ingin menerima order dari katalog publik, memantau pembayaran, mengelola pengiriman, dan menjalankan fulfillment tanpa memecah proses ke banyak tools.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-lg btn-dark">Daftar Commerce</a>
                    <a href="#pricing" class="btn btn-lg btn-outline-dark">Lihat Paket</a>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-light text-dark border">Storefront</span>
                    <span class="badge bg-light text-dark border">Orders</span>
                    <span class="badge bg-light text-dark border">Payment Status</span>
                    <span class="badge bg-light text-dark border">Shipping</span>
                    <span class="badge bg-light text-dark border">Fulfillment</span>
                    <span class="badge bg-light text-dark border">Affiliate</span>
                    <span class="badge bg-light text-dark border">Wallet</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="landing-panel p-4 p-lg-5">
                    <div class="text-uppercase text-muted small fw-bold mb-3">Cocok untuk</div>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Bisnis yang ingin mulai menerima order dari katalog publik</div>
                        <div><i class="ti ti-check text-success"></i> Tim order yang perlu payment, shipping, dan fulfillment lebih rapi</div>
                        <div><i class="ti ti-check text-success"></i> Seller yang ingin membuka distribusi affiliate sederhana</div>
                    </div>
                    <div class="alert alert-warning mt-4 mb-0 small">
                        Commerce fokus ke kanal order dan operasionalnya. Untuk finance formal, purchases, dan reports accounting, gunakan business suite accounting.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            @foreach($modules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="landing-panel p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="accounting-summary-icon" style="width:52px;height:52px;">
                                {!! $module['icon_svg'] !!}
                            </div>
                            <div>
                                <div class="landing-eyebrow mb-1">{{ $module['eyebrow'] }}</div>
                                <div class="h5 mb-0">{{ $module['name'] }}</div>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">{{ $module['description'] }}</p>
                        <div class="small">
                            @foreach($module['public_points'] as $point)
                                <div class="mb-1">- {{ $point }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="pricing" class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Paket Commerce</div>
            <h2 class="landing-section-title mb-3">Pilih paket yang sesuai dengan ritme order bisnis Anda.</h2>
            <p class="landing-subtext mx-auto" style="max-width:760px;">Pendaftaran publik dimulai dari satu pilihan yang sederhana: pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk aktivasi.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($plans as $plan)
                @php
                    $sales = (array) ($plan->sales_meta ?? []);
                    $limits = (array) ($plan->limits ?? []);
                    $price = app(\App\Support\MoneyFormatter::class)->format((float) ($sales['price'] ?? 0), (string) ($sales['currency'] ?? 'IDR'));
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="landing-panel p-4 h-100 {{ !empty($sales['recommended']) ? 'border-dark shadow-sm' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="h4 mb-1">Commerce {{ $plan->name }}</div>
                                <div class="text-muted small">{{ $plan->billingIntervalLabel() }}</div>
                            </div>
                            @if(!empty($sales['recommended']))
                                <span class="badge bg-dark text-white">Paling populer</span>
                            @endif
                        </div>
                        <div class="display-6 fw-bold mb-2">{{ $price }}</div>
                        <p class="text-muted small mb-3">{{ $sales['description'] ?? $sales['tagline'] ?? '' }}</p>

                        <div class="small mb-3">
                            <div class="mb-1"><strong>{{ number_format((int) ($limits[\App\Support\PlanLimit::USERS] ?? 0), 0, ',', '.') }}</strong> user</div>
                            <div class="mb-1"><strong>{{ number_format((int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0), 0, ',', '.') }}</strong> branch</div>
                            <div class="mb-1"><strong>{{ number_format((int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }}</strong> produk</div>
                            <div><strong>{{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }}</strong> kontak</div>
                        </div>

                        <div class="small text-muted mb-4">
                            @foreach((array) ($sales['highlights'] ?? []) as $highlight)
                                <div class="mb-1">- {{ $highlight }}</div>
                            @endforeach
                        </div>

                        <a href="{{ route('onboarding.create', ['product_line' => 'commerce', 'plan' => $plan->code]) }}" class="btn {{ !empty($sales['recommended']) ? 'btn-dark' : 'btn-outline-dark' }} w-100">Pilih Paket Ini</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Yang sudah termasuk</div>
                    <h2 class="landing-section-title mb-3">Siap untuk order flow yang terhubung.</h2>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Storefront publik tenant-subdomain</div>
                        <div><i class="ti ti-check text-success"></i> Order masuk ke workspace yang sama</div>
                        <div><i class="ti ti-check text-success"></i> Payment status commerce terpusat</div>
                        <div><i class="ti ti-check text-success"></i> Queue shipping dan fulfillment lebih jelas</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Boundary</div>
                    <h2 class="landing-section-title mb-3">Jangan campur dengan accounting formal kalau belum perlu.</h2>
                    <p class="landing-subtext mb-0" style="max-width:none;">
                        Commerce tetap memakai modul shared seperti products, sales, payments, dan contacts. Tetapi fokusnya adalah order capture dan operasional order. Jika bisnis Anda juga butuh purchases, inventory governance, dan finance formal, jalankan accounting sebagai business suite terpisah.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
