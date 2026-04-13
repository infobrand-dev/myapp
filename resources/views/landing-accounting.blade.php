@extends('layouts.landing')

@section('head_title', config('app.name') . ' Accounting - Paket sales, pembayaran, pembelian, stok, dan laporan operasional')
@section('head_description', 'Meetra Accounting membantu bisnis merapikan penjualan, pembayaran, pembelian, stok, dan laporan operasional. Promo anniversary ke-2: gunakan kode MEETRA2ND untuk 50% off semua paket.')

@push('head')
<style>
body.landing-page.accounting-anniversary-page .landing-topbar { top: 52px; z-index: 1030; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo { position: sticky; top: 0; z-index: 1040; background: radial-gradient(circle at 15% 50%, rgba(255,255,255,.22), transparent 28%), linear-gradient(90deg, #7f1d1d 0%, #dc2626 36%, #be123c 100%); color: #fff; box-shadow: 0 10px 24px rgba(127, 29, 29, .22); }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__inner { min-height: 52px; display: flex; align-items: center; justify-content: center; gap: .75rem; flex-wrap: wrap; text-align: center; padding: .6rem 0; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .8rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); font-size: .76rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__text { font-size: .95rem; font-weight: 600; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__code { display: inline-flex; align-items: center; padding: .3rem .8rem; border-radius: 999px; background: rgba(17,24,39,.3); border: 1px solid rgba(255,255,255,.26); font-weight: 800; letter-spacing: .08em; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__cta { display: inline-flex; align-items: center; padding: .45rem .95rem; border-radius: 999px; background: #fff; color: #991b1b; font-size: .82rem; font-weight: 800; text-decoration: none; box-shadow: 0 6px 16px rgba(0,0,0,.12); }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__cta:hover { color: #7f1d1d; text-decoration: none; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__close { appearance: none; border: 0; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; background: rgba(17,24,39,.22); color: #fff; font-size: 1rem; font-weight: 800; line-height: 1; cursor: pointer; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo__close:hover { background: rgba(17,24,39,.34); }
body.landing-page.accounting-anniversary-page .accounting-floating-promo.is-hidden { display: none; }
body.landing-page.accounting-anniversary-page .accounting-floating-promo.is-hidden + .landing-topbar { top: 0; }
body.landing-page.accounting-anniversary-page .accounting-plan-pricing { margin-bottom: 1rem; }
body.landing-page.accounting-anniversary-page .accounting-plan-original { color: #94a3b8; font-size: 1rem; font-weight: 700; text-decoration: line-through; text-decoration-thickness: 2px; margin-bottom: .15rem; }
body.landing-page.accounting-anniversary-page .accounting-plan-final { display: flex; align-items: flex-end; gap: .65rem; flex-wrap: wrap; }
body.landing-page.accounting-anniversary-page .accounting-plan-discount { display: inline-flex; align-items: center; padding: .28rem .65rem; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: .75rem; font-weight: 800; letter-spacing: .03em; }
@media (max-width: 991.98px) {
    body.landing-page.accounting-anniversary-page .landing-topbar { top: 0; }
    body.landing-page.accounting-anniversary-page .accounting-floating-promo { position: relative; }
}
</style>
@endpush

@section('topbar')
<div class="accounting-floating-promo" id="accountingFloatingPromo">
    <div class="container">
        <div class="accounting-floating-promo__inner">
            <span class="accounting-floating-promo__badge"><span>🎉</span><span>Meetra 2 Tahun</span></span>
            <span class="accounting-floating-promo__text">Promo anniversary <strong>50% OFF</strong> semua paket Accounting</span>
            <span class="accounting-floating-promo__code">MEETRA2ND</span>
            <a href="#pricing" class="accounting-floating-promo__cta">Lihat Harga Promo</a>
            <button type="button" class="accounting-floating-promo__close" id="accountingFloatingPromoClose" aria-label="Tutup banner promo">×</button>
        </div>
    </div>
</div>
@parent
@endsection

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $plansCollection = collect($publicPlans ?? []);
    $intervalOrder = ['monthly', 'semiannual', 'yearly'];
    $intervalMeta = [
        'monthly'    => ['label' => 'Bulanan',  'desc' => 'Mulai fleksibel tanpa komitmen panjang.', 'badge' => null],
        'semiannual' => ['label' => '6 Bulanan','desc' => 'Lebih hemat untuk komitmen 6 bulan.', 'badge' => 'Hemat ~10%'],
        'yearly'     => ['label' => 'Tahunan',  'desc' => 'Paling hemat untuk komitmen tahunan.', 'badge' => 'Hemat ~17%'],
    ];

    $plansByInterval = $plansCollection
        ->map(function ($plan) use ($money) {
            $sales = (array) ($plan->sales_meta ?? []);
            $features = (array) ($plan->features ?? []);
            $limits = (array) ($plan->limits ?? []);
            $name = trim((string) $plan->name);
            $isStarter = strtolower($name) === 'starter';

            return [
                'name'            => $name,
                'code'            => $plan->code,
                'sort_order'      => (int) ($plan->sort_order ?? 0),
                'interval'        => (string) $plan->billing_interval,
                'interval_label'  => $plan->billing_interval_label,
                'price'           => $money->format((float) ($sales['price'] ?? 0), strtoupper((string) ($sales['currency'] ?? 'IDR'))),
                'price_value'     => (float) ($sales['price'] ?? 0),
                'original_price'  => $money->format(round((float) ($sales['price'] ?? 0) * 2, 2), strtoupper((string) ($sales['currency'] ?? 'IDR'))),
                'original_price_value' => round((float) ($sales['price'] ?? 0) * 2, 2),
                'caption'         => (string) ($sales['description'] ?? ''),
                'summary'         => (string) ($sales['tagline'] ?? ''),
                'featured'        => (bool) ($sales['recommended'] ?? false),
                'users'           => (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0),
                'branches'        => (int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0),
                'products'        => (int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0),
                'contacts'        => (int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0),
                'storage'         => (int) ($limits[\App\Support\PlanLimit::TOTAL_STORAGE_BYTES] ?? 0),
                'purchases'       => !empty($features[\App\Support\PlanFeature::PURCHASES]),
                'inventory'       => !empty($features[\App\Support\PlanFeature::INVENTORY]),
                'advanced_reports'=> !empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]),
                'highlights'      => array_values((array) ($sales['highlights'] ?? [])),
                'features_list'   => $isStarter
                    ? [
                        'Sales untuk transaksi harian',
                        'Payments untuk pembayaran masuk dan keluar',
                        'Finance ringan untuk arus kas operasional',
                        'Products dan Contacts sebagai data utama',
                        'Basic reports untuk ringkasan cepat',
                    ]
                    : [
                        'Semua fitur Accounting Starter',
                        'Purchases untuk pembelian supplier',
                        'Inventory untuk kontrol stok',
                        !empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]) ? 'Full reports untuk pembacaan lebih detail' : 'Basic reports',
                        sprintf('Kapasitas hingga %s user dan %s branch', number_format((int) ($limits[\App\Support\PlanLimit::USERS] ?? 0), 0, ',', '.'), number_format((int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0), 0, ',', '.')),
                    ],
            ];
        })
        ->groupBy('interval')
        ->map(fn ($plans) => collect($plans)->sortBy([
            ['sort_order', 'asc'],
            ['name', 'asc'],
        ])->values());

    if ($plansByInterval->isEmpty()) {
        $plansByInterval = collect([
            'monthly' => collect([
                ['name' => 'Starter', 'code' => 'accounting_starter', 'interval' => 'monthly', 'interval_label' => 'Bulanan', 'price' => 'Rp249.000', 'price_value' => 249000, 'original_price' => 'Rp498.000', 'original_price_value' => 498000, 'original_2year' => null, 'caption' => 'Untuk UMKM yang ingin mulai rapi tanpa workflow berat.', 'summary' => 'Mulai dari sales, payments, finance, products, contacts, dan basic reports.', 'featured' => false, 'is_promo' => false, 'promo_label' => '', 'users' => 5, 'branches' => 1, 'products' => 250, 'contacts' => 1000, 'storage' => 1073741824, 'purchases' => false, 'inventory' => false, 'advanced_reports' => false, 'highlights' => [], 'features_list' => ['Sales untuk transaksi harian', 'Payments untuk pembayaran masuk dan keluar', 'Finance ringan untuk arus kas operasional', 'Products dan Contacts sebagai data utama', 'Basic reports untuk ringkasan cepat']],
                ['name' => 'Growth', 'code' => 'accounting_growth', 'interval' => 'monthly', 'interval_label' => 'Bulanan', 'price' => 'Rp499.000', 'price_value' => 499000, 'original_price' => 'Rp998.000', 'original_price_value' => 998000, 'original_2year' => null, 'caption' => 'Untuk bisnis yang mulai aktif dan butuh operasional lebih lengkap.', 'summary' => 'Semua fitur Starter ditambah purchases, inventory, dan full reports.', 'featured' => true, 'is_promo' => false, 'promo_label' => '', 'users' => 15, 'branches' => 3, 'products' => 2000, 'contacts' => 5000, 'storage' => 5368709120, 'purchases' => true, 'inventory' => true, 'advanced_reports' => true, 'highlights' => [], 'features_list' => ['Semua fitur Accounting Starter', 'Purchases untuk pembelian supplier', 'Inventory untuk kontrol stok', 'Full reports untuk pembacaan lebih detail', 'Kapasitas hingga 15 user dan 3 branch']],
                ['name' => 'Scale', 'code' => 'accounting_scale', 'interval' => 'monthly', 'interval_label' => 'Bulanan', 'price' => 'Rp999.000', 'price_value' => 999000, 'original_price' => 'Rp1.998.000', 'original_price_value' => 1998000, 'original_2year' => null, 'caption' => 'Untuk operasional yang lebih padat dengan kapasitas lebih besar.', 'summary' => 'Isi fitur sama dengan Growth, dengan kapasitas yang lebih longgar.', 'featured' => false, 'is_promo' => false, 'promo_label' => '', 'users' => 50, 'branches' => 10, 'products' => 10000, 'contacts' => 20000, 'storage' => 21474836480, 'purchases' => true, 'inventory' => true, 'advanced_reports' => true, 'highlights' => [], 'features_list' => ['Semua fitur Accounting Growth', 'Cocok untuk multi-user dan multi-branch', 'Batas produk, kontak, dan storage lebih besar', 'Tetap bisa menambahkan POS sesuai kebutuhan', 'Lebih aman untuk operasional yang terus tumbuh']],
            ]),
        ]);
    }

    $defaultInterval = collect($intervalOrder)->first(fn ($i) => $plansByInterval->has($i)) ?? 'monthly';
    $comparisonPlans = $plansByInterval->get('monthly', $plansByInterval->first())->values();
    $testimonials = [
        ['quote' => 'Sebelumnya admin kami catat pembayaran, penjualan, dan stok di tempat yang berbeda. Setelah pakai Meetra Accounting, pekerjaan harian jauh lebih enak dipantau.', 'name' => 'Rina', 'role' => 'Owner, toko bahan bangunan'],
        ['quote' => 'Yang paling terasa itu tim jadi tidak bingung lagi cari data customer, invoice, atau status pembayaran. Semua lebih nyambung.', 'name' => 'Dimas', 'role' => 'Manager Operasional, distributor lokal'],
        ['quote' => 'Kami mulai dari paket simple dulu. Saat transaksi makin ramai, tinggal naik paket tanpa pindah sistem dan tanpa reset cara kerja tim.', 'name' => 'Ayu', 'role' => 'Finance Admin, bisnis retail multi-cabang'],
    ];
@endphp

{{-- ── Promo Banner ─────────────────────────────────────────────── --}}
<section id="overview" class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-report-money"></i> Meetra Accounting
                </div>
                <h1 class="landing-headline mb-4">
                    <span>Satukan pencatatan transaksi</span> tanpa perlu pindah-pindah aplikasi.
                </h1>
                <p class="landing-subtext mb-5">
                    Mulai dari penjualan, pembayaran, dan kas. Tambah pembelian, stok, dan laporan saat bisnis tumbuh — semua dalam satu workspace.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <a href="{{ route('onboarding.create', ['product_line' => 'accounting']) }}" class="btn btn-lg btn-dark">Daftar Sekarang</a>
                    <a href="#pricing" class="btn btn-lg btn-outline-dark">Lihat Semua Paket</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Sales</span>
                    <span class="landing-pill">Payments</span>
                    <span class="landing-pill">Finance</span>
                    <span class="landing-pill">Purchases</span>
                    <span class="landing-pill">Inventory</span>
                    <span class="landing-pill">Reports</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="accounting-hero-grid">
                    @foreach (array_slice(array_merge($modules, $supportingModules), 0, 6) as $module)
                        <div class="accounting-summary-card">
                            <div class="accounting-summary-icon">{!! $module['icon_svg'] !!}</div>
                            <div class="small text-uppercase fw-bold text-muted mb-1">{{ $module['eyebrow'] }}</div>
                            <div class="fw-bold mb-2">{{ $module['name'] }}</div>
                            <div class="small text-muted">{{ $module['description'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="accounting-metric-card">
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:#dc2626;">50%</div>
                    <div class="small text-muted mt-2">Off semua paket dengan kode anniversary <strong>MEETRA2ND</strong></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="accounting-metric-card">
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:#0f766e;">3 Tier</div>
                    <div class="small text-muted mt-2">Starter, Growth, dan Scale sesuai tahap bisnis</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="accounting-metric-card">
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:#7c3aed;">POS</div>
                    <div class="small text-muted mt-2">Tersedia sebagai add-on untuk outlet dan retail</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="accounting-metric-card">
                    <div class="fw-bold" style="font-size:2rem; line-height:1; color:#ea580c;">2nd</div>
                    <div class="small text-muted mt-2">Anniversary Meetra — promo kode aktif untuk semua paket</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kenapa Banyak Tim Mulai Dari Sini</div>
            <h2 class="landing-section-title">Dibuat untuk bisnis yang ingin rapi, tanpa harus langsung masuk ke sistem yang terasa berat.</h2>
            <p class="landing-subtext mx-auto">Fokusnya operasional harian yang benar-benar dipakai user. Bukan istilah teknis yang rumit, tapi alur yang mudah dipahami owner, admin, dan tim operasional.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Mulai dari kebutuhan paling dasar</h3>
                    <p class="small text-muted mb-0">Sales, payments, finance, products, contacts, dan basic reports sudah cukup untuk banyak bisnis UMKM yang ingin mulai rapi.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Naik level tanpa pindah sistem</h3>
                    <p class="small text-muted mb-0">Saat pembelian supplier dan stok mulai jadi kebutuhan utama, Growth dan Scale sudah siap tanpa perlu migrasi ke sistem lain.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Lebih terasa operasional, bukan sekadar pencatatan</h3>
                    <p class="small text-muted mb-0">Meetra menyatukan data customer, produk, transaksi, pembayaran, dan stok agar workflow tim sehari-hari lebih nyambung.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Fitur Inti</div>
            <h2 class="landing-section-title">Fitur yang paling sering dipakai untuk operasional harian.</h2>
            <p class="landing-subtext mx-auto">Bahasanya dibuat sederhana supaya user lebih mudah membayangkan apa yang bisa dipakai sejak hari pertama.</p>
        </div>
        <div class="row g-4">
            @foreach ($modules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="accounting-feature-card">
                        <div class="accounting-feature-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="accounting-feature-icon">{!! $module['icon_svg'] !!}</div>
                                <div class="small text-uppercase fw-bold text-muted">{{ $module['eyebrow'] }}</div>
                            </div>
                            <h3 class="h5 mb-2">{{ $module['name'] }}</h3>
                            <p class="small text-muted mb-3">{{ $module['description'] }}</p>
                            <div class="landing-checklist small text-muted">
                                @foreach ($module['public_points'] as $point)
                                    <div><i class="ti ti-check text-success"></i> {{ $point }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Fitur Lanjutan</div>
                <h2 class="landing-section-title mb-3">Saat bisnis berkembang, workflow-nya ikut lengkap.</h2>
                <p class="landing-subtext mb-0">Growth dan Scale menambahkan pembelian supplier, stok, dan laporan yang lebih detail. Jadi bisnis bisa mulai dari paket simple, lalu berkembang saat operasional makin padat.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    @foreach ($supportingModules as $module)
                        <div class="col-md-4">
                            <div class="accounting-addon-card">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="accounting-feature-icon" style="width:46px; height:46px;">{!! $module['icon_svg'] !!}</div>
                                    <div>
                                        <div class="fw-bold mb-1">{{ $module['name'] }}</div>
                                        <div class="small text-muted">{{ $module['description'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Pricing ──────────────────────────────────────────────────── --}}
<section id="pricing" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Paket & Harga</div>
            <h2 class="landing-section-title">Pilih billing yang paling pas: bulanan, 6 bulanan, atau tahunan.</h2>
            <p class="landing-subtext mx-auto">Gunakan kode <strong>MEETRA2ND</strong> saat daftar untuk promo anniversary 50% off semua paket.</p>
            <div class="d-flex justify-content-center mt-4">
                <div class="accounting-tier-nav">
                    @foreach ($intervalOrder as $interval)
                        @php $meta = $intervalMeta[$interval] ?? null; @endphp
                        @if($meta && $plansByInterval->has($interval))
                            <button type="button"
                                class="accounting-tier-btn {{ $interval === $defaultInterval ? 'active' : '' }}"
                                data-tier-tab="{{ $interval }}">
                                {{ $meta['label'] }}
                                @if($meta['badge'])
                                    <span class="accounting-tier-badge">{{ $meta['badge'] }}</span>
                                @endif
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @foreach ($intervalOrder as $interval)
            @php
                $meta = $intervalMeta[$interval] ?? null;
                $intervalPlans = $plansByInterval->get($interval, collect())->values();
            @endphp
            @if($meta && $intervalPlans->isNotEmpty())
                <div class="accounting-tier-pane {{ $interval === $defaultInterval ? 'active' : '' }}" data-tier-pane="{{ $interval }}">

                    <div class="text-center small text-muted mb-4">{{ $meta['desc'] }}</div>

                    <div class="row g-4">
                        @foreach ($intervalPlans as $plan)
                            <div class="col-lg-4">
                                <div class="accounting-plan-card {{ !empty($plan['featured']) ? 'featured' : '' }}">
                                    @if (!empty($plan['featured']))
                                        <div class="accounting-plan-badge mb-3">Paling sering dipilih</div>
                                    @endif
                                    <div class="h3 fw-800 mb-1">Accounting {{ $plan['name'] }}</div>
                                    <div class="small text-muted mb-3">{{ $plan['caption'] }}</div>
                                    <div class="accounting-plan-pricing">
                                        <div class="accounting-plan-original">{{ $plan['original_price'] }}</div>
                                        <div class="accounting-plan-final">
                                            <div class="display-6 fw-bold mb-0">{{ $plan['price'] }}</div>
                                            <span class="accounting-plan-discount">50% OFF</span>
                                        </div>
                                    </div>
                                    <div class="small text-muted mb-4">tagihan {{ strtolower($plan['interval_label']) }}</div>
                                    <p class="small mb-4">{{ $plan['summary'] }}</p>
                                    <div class="landing-checklist small mb-4">
                                        @foreach ($plan['features_list'] as $feature)
                                            <div><i class="ti ti-check text-success"></i> {{ $feature }}</div>
                                        @endforeach
                                        <div><i class="ti ti-check text-success"></i> POS tersedia sebagai add-on opsional</div>
                                    </div>
                                    <div class="d-grid">
                                        <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => $plan['code']]) }}"
                                            class="btn {{ !empty($plan['featured']) ? 'btn-light' : 'btn-dark' }} btn-lg">
                                            Daftar Paket Ini
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>

<section id="compare" class="py-5" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Perbandingan Paket</div>
            <h2 class="landing-section-title">Bandingkan fitur inti antar paket.</h2>
            <p class="landing-subtext mx-auto">Tabel ini memakai paket Bulanan sebagai pembanding utama. Di layar kecil, tabel bisa digeser ke samping.</p>
        </div>
        <div class="accounting-compare-wrap">
            <table class="accounting-compare-table">
                <thead>
                    <tr>
                        <th style="min-width:220px;">Fitur / Kapasitas</th>
                        @foreach ($comparisonPlans as $plan)
                            <th>
                                <div class="fw-bold">Accounting {{ $plan['name'] }}</div>
                                <div class="small text-muted"><span class="text-decoration-line-through">{{ $plan['original_price'] }}</span> • {{ $plan['price'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Sales, Payments, Finance</td>@foreach ($comparisonPlans as $plan)<td><span class="accounting-value-yes">Ya</span></td>@endforeach</tr>
                    <tr><td>Products dan Contacts</td>@foreach ($comparisonPlans as $plan)<td><span class="accounting-value-yes">Ya</span></td>@endforeach</tr>
                    <tr><td>Purchases</td>@foreach ($comparisonPlans as $plan)<td><span class="{{ $plan['purchases'] ? 'accounting-value-yes' : 'accounting-value-no' }}">{{ $plan['purchases'] ? 'Ya' : 'Belum' }}</span></td>@endforeach</tr>
                    <tr><td>Inventory</td>@foreach ($comparisonPlans as $plan)<td><span class="{{ $plan['inventory'] ? 'accounting-value-yes' : 'accounting-value-no' }}">{{ $plan['inventory'] ? 'Ya' : 'Belum' }}</span></td>@endforeach</tr>
                    <tr><td>Reports</td>@foreach ($comparisonPlans as $plan)<td>{{ $plan['advanced_reports'] ? 'Full reports' : 'Basic reports' }}</td>@endforeach</tr>
                    <tr><td>POS Add-on</td>@foreach ($comparisonPlans as $plan)<td><span class="accounting-value-yes">Tersedia</span></td>@endforeach</tr>
                    <tr><td>Jumlah user</td>@foreach ($comparisonPlans as $plan)<td>{{ number_format($plan['users'], 0, ',', '.') }}</td>@endforeach</tr>
                    <tr><td>Jumlah branch</td>@foreach ($comparisonPlans as $plan)<td>{{ number_format($plan['branches'], 0, ',', '.') }}</td>@endforeach</tr>
                    <tr><td>Produk</td>@foreach ($comparisonPlans as $plan)<td>{{ number_format($plan['products'], 0, ',', '.') }}</td>@endforeach</tr>
                    <tr><td>Kontak</td>@foreach ($comparisonPlans as $plan)<td>{{ number_format($plan['contacts'], 0, ',', '.') }}</td>@endforeach</tr>
                    <tr><td>Storage workspace</td>@foreach ($comparisonPlans as $plan)<td>{{ number_format((int) round($plan['storage'] / 1073741824), 0, ',', '.') }} GB</td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Kenapa Memilih Meetra</div>
                <h2 class="landing-section-title mb-3">Lebih terasa operasional dan lebih mudah dipakai tim sehari-hari.</h2>
                <p class="landing-subtext mb-0">Meetra cocok untuk bisnis yang ingin satu workspace untuk data customer, produk, transaksi, pembayaran, stok, dan laporan. Fokusnya membantu tim bekerja lebih rapi, bukan hanya menyediakan layar pencatatan yang terpisah dari workflow operasional.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="accounting-proof-card">
                            <h3 class="h5 mb-2">Lebih nyambung ke alur kerja</h3>
                            <p class="small text-muted mb-0">Kontak, produk, transaksi, pembayaran, pembelian, dan stok berada dalam alur yang sama sehingga tim tidak perlu terlalu sering pindah alat.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="accounting-proof-card">
                            <h3 class="h5 mb-2">Bisa mulai dari yang simple</h3>
                            <p class="small text-muted mb-0">Tidak semua bisnis perlu workflow yang kompleks sejak hari pertama. Meetra memungkinkan mulai dari paket ringan lalu naik saat bisnis siap.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="accounting-proof-card">
                            <h3 class="h5 mb-2">POS tidak dipaksakan ke semua orang</h3>
                            <p class="small text-muted mb-0">Bagi bisnis non-retail, paket inti tetap ringan. Bagi toko dan outlet, POS tinggal ditambahkan saat memang dibutuhkan.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="accounting-proof-card">
                            <h3 class="h5 mb-2">Lebih mudah dijelaskan ke user</h3>
                            <p class="small text-muted mb-0">Bahasa produk dibuat lebih dekat ke kebutuhan owner, admin, finance, dan operasional, jadi onboarding tim jadi lebih cepat.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="social-proof">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Cerita Pengguna</div>
            <h2 class="landing-section-title">Siapa yang paling cocok menggunakan Meetra Accounting?</h2>
        </div>
        <div class="row g-4">
            @foreach ($testimonials as $testimonial)
                <div class="col-lg-4">
                    <div class="accounting-proof-card">
                        <div class="mb-3" style="font-size:2rem; line-height:1; color:#1d4ed8;">"</div>
                        <p class="small text-muted mb-4">{{ $testimonial['quote'] }}</p>
                        <div class="fw-bold">{{ $testimonial['name'] }}</div>
                        <div class="small text-muted">{{ $testimonial['role'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="addon" class="py-5">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="landing-eyebrow mb-2">POS Add-on</div>
                    <h2 class="landing-section-title mb-3">Butuh kasir outlet? Tambahkan Point of Sale saat diperlukan.</h2>
                    <p class="landing-subtext mb-0">POS dipisahkan sebagai add-on agar paket accounting tetap ringan untuk bisnis umum. Kalau bisnis Anda punya toko, outlet, atau counter kasir, POS bisa ditambahkan ke paket yang Anda pilih.</p>
                </div>
                <div class="col-lg-5">
                    @foreach ($addonModules as $module)
                        <div class="accounting-addon-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="accounting-feature-icon" style="width:50px; height:50px;">{!! $module['icon_svg'] !!}</div>
                                <div>
                                    <div class="fw-bold mb-1">{{ $module['name'] }}</div>
                                    <div class="small text-muted mb-2">{{ $module['description'] }}</div>
                                    <div class="small text-muted">Add-on mulai dari Rp99.000 per bulan, mengikuti paket yang dipilih.</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">🎉 Meetra Anniversary ke-2</div>
            <h2 class="landing-section-title mb-3">Promo 50% off semua paket Accounting.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Dalam rangka anniversary ke-2 Meetra, semua paket Accounting mendapatkan diskon 50%. Gunakan kode promo <strong>MEETRA2ND</strong> saat mengisi form pendaftaran.</p>
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-4">
                <div class="px-4 py-2 rounded fw-bold" style="font-size:1.5rem; letter-spacing:.12em; background:#1e293b; color:#fff; border-radius:8px;">MEETRA2ND</div>
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('onboarding.create', ['product_line' => 'accounting']) }}" class="btn btn-dark btn-lg">Daftar &amp; Pakai Kode Promo</a>
                <a href="#pricing" class="btn btn-outline-dark btn-lg">Lihat Semua Paket</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    document.body.classList.add('accounting-anniversary-page');

    var promoBanner = document.getElementById('accountingFloatingPromo');
    var promoBannerClose = document.getElementById('accountingFloatingPromoClose');
    var promoBannerStorageKey = 'landing-accounting-promo-hidden';

    if (promoBanner && window.sessionStorage && sessionStorage.getItem(promoBannerStorageKey) === '1') {
        promoBanner.classList.add('is-hidden');
    }

    if (promoBanner && promoBannerClose) {
        promoBannerClose.addEventListener('click', function () {
            promoBanner.classList.add('is-hidden');

            if (window.sessionStorage) {
                sessionStorage.setItem(promoBannerStorageKey, '1');
            }
        });
    }

    var tabButtons = document.querySelectorAll('[data-tier-tab]');
    var tabPanes   = document.querySelectorAll('[data-tier-pane]');

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-tier-tab');

            tabButtons.forEach(function (item) {
                item.classList.toggle('active', item === button);
            });

            tabPanes.forEach(function (pane) {
                pane.classList.toggle('active', pane.getAttribute('data-tier-pane') === target);
            });
        });
    });
})();
</script>
@endpush
