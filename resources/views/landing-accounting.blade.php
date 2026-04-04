@extends('layouts.landing')

@section('head_title', config('app.name') . ' Accounting - Paket untuk sales, pembayaran, pembelian, stok, dan laporan operasional')
@section('head_description', 'Meetra Accounting membantu bisnis merapikan penjualan, pembayaran, pembelian, kas, produk, kontak, stok, dan laporan operasional dalam satu workspace.')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class);
    $plans = collect($publicPlans ?? [])->map(function ($plan) use ($money) {
        $sales = (array) ($plan->sales_meta ?? []);
        $features = (array) ($plan->features ?? []);
        $limits = (array) ($plan->limits ?? []);
        $isStarter = $plan->code === 'accounting_starter';
        $name = trim((string) $plan->name);
        $price = $money->format((float) ($sales['price'] ?? 0), strtoupper((string) ($sales['currency'] ?? 'IDR')));

        return [
            'name' => $name,
            'code' => $plan->code,
            'price' => $price,
            'caption' => (string) ($sales['description'] ?? ''),
            'summary' => (string) ($sales['tagline'] ?? ''),
            'featured' => (bool) ($sales['recommended'] ?? false),
            'features' => $isStarter
                ? [
                    'Sales untuk transaksi harian',
                    'Payments untuk catat pembayaran masuk dan keluar',
                    'Finance ringan untuk arus kas operasional',
                    'Products dan Contacts sebagai data utama',
                    'Basic reports untuk ringkasan cepat',
                ]
                : [
                    'Semua fitur Accounting Starter',
                    'Purchases untuk pembelian supplier',
                    'Inventory untuk kontrol stok',
                    !empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]) ? 'Full reports untuk pembacaan lebih detail' : 'Basic reports',
                    sprintf(
                        'Kapasitas hingga %s user dan %s branch',
                        number_format((int) ($limits[\App\Support\PlanLimit::USERS] ?? 0), 0, ',', '.'),
                        number_format((int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0), 0, ',', '.')
                    ),
                ],
        ];
    })->values();

    if ($plans->isEmpty()) {
        $plans = collect([
            [
                'name' => 'Starter',
                'code' => 'accounting_starter',
                'price' => 'Rp249.000',
                'caption' => 'Untuk UMKM yang ingin mulai rapi tanpa workflow yang terlalu berat.',
                'summary' => 'Mulai dari penjualan, pembayaran, kas, produk, kontak, dan ringkasan laporan.',
                'features' => [
                    'Sales untuk transaksi harian',
                    'Payments untuk catat pembayaran masuk dan keluar',
                    'Finance ringan untuk arus kas operasional',
                    'Products dan Contacts sebagai data utama',
                    'Basic reports untuk ringkasan cepat',
                ],
            ],
            [
                'name' => 'Growth',
                'code' => 'accounting_growth',
                'price' => 'Rp499.000',
                'caption' => 'Untuk bisnis yang mulai aktif dan butuh workflow operasional yang lebih lengkap.',
                'summary' => 'Semua fitur Starter ditambah pembelian, stok, dan laporan yang lebih lengkap.',
                'featured' => true,
                'features' => [
                    'Semua fitur Accounting Starter',
                    'Purchases untuk pembelian supplier',
                    'Inventory untuk kontrol stok',
                    'Full reports untuk pembacaan lebih detail',
                    'Kapasitas user, branch, produk, dan kontak lebih besar',
                ],
            ],
            [
                'name' => 'Scale',
                'code' => 'accounting_scale',
                'price' => 'Rp999.000',
                'caption' => 'Untuk tim yang lebih padat dengan branch, user, dan data yang lebih besar.',
                'summary' => 'Isi fitur sama dengan Growth, dengan kapasitas yang lebih besar untuk operasional yang lebih ramai.',
                'features' => [
                    'Semua fitur Accounting Growth',
                    'Cocok untuk multi-user dan multi-branch',
                    'Batas produk, kontak, dan storage lebih besar',
                    'Tetap bisa menambahkan POS sesuai kebutuhan',
                    'Lebih aman untuk operasional yang terus tumbuh',
                ],
            ],
        ]);
    }
@endphp

<section id="overview" class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-report-money"></i> Meetra Accounting
                </div>
                <h1 class="landing-headline mb-4">
                    Satu workspace untuk <span>jualan, pembayaran, pembelian, stok, dan laporan operasional</span>.
                </h1>
                <p class="landing-subtext mb-5">
                    Meetra Accounting dibuat untuk bisnis yang ingin kerja harian lebih rapi. Anda bisa mulai dari kebutuhan yang sederhana seperti sales, pembayaran, kas, produk, dan kontak. Saat bisnis berkembang, Anda bisa lanjut ke pembelian supplier, stok, laporan detail, dan POS.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="#plans" class="btn btn-lg btn-dark">Pilih Paket</a>
                    <a href="#features" class="btn btn-lg btn-outline-dark">Lihat Fitur</a>
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

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Cocok Untuk</div>
            <h2 class="landing-section-title">Mudah dipahami, mudah dipakai, dan bisa tumbuh saat bisnis makin rapi.</h2>
            <p class="landing-subtext mx-auto">Meetra Accounting tidak memaksa bisnis langsung memakai alur yang kompleks. Mulai dari yang penting dulu, lalu tambah saat memang dibutuhkan.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Untuk bisnis yang baru mulai rapi</h3>
                    <p class="small text-muted mb-0">Mulai dari penjualan, pembayaran, data produk, data customer atau supplier, lalu baca ringkasan performa tanpa rekap manual yang melelahkan.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Untuk tim yang sudah aktif operasional</h3>
                    <p class="small text-muted mb-0">Saat pembelian supplier dan stok mulai penting, Anda bisa naik ke paket yang lebih lengkap tanpa pindah sistem.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="accounting-easy-card">
                    <h3 class="h5 mb-2">Untuk outlet yang butuh kasir</h3>
                    <p class="small text-muted mb-0">POS disiapkan sebagai add-on, jadi bisnis yang butuh kasir outlet bisa menambahkannya tanpa membuat paket inti jadi terlalu berat.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Fitur Inti</div>
            <h2 class="landing-section-title">Fitur yang paling sering dibutuhkan untuk operasional harian.</h2>
            <p class="landing-subtext mx-auto">Bahasanya sengaja dibuat sederhana, supaya user mudah paham apa yang akan dipakai sejak hari pertama.</p>
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
                <h2 class="landing-section-title mb-3">Saat bisnis berkembang, workflow-nya juga ikut lengkap.</h2>
                <p class="landing-subtext mb-0">Growth dan Scale menambahkan pembelian supplier, stok, dan laporan yang lebih detail. Jadi bisnis bisa mulai dari paket simple, lalu berkembang saat operasional mulai lebih padat.</p>
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

<section id="plans" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Pilih Paket</div>
            <h2 class="landing-section-title">Tinggal pilih plan yang paling pas untuk kondisi bisnis Anda.</h2>
            <p class="landing-subtext mx-auto">Pilih paket yang paling sesuai dan mulai workspace Anda langsung dari sini. Bisa naik ke paket lebih lengkap kapan saja saat bisnis berkembang.</p>
        </div>
        <div class="row g-4">
            @foreach ($plans as $plan)
                <div class="col-lg-4">
                    <div class="accounting-plan-card {{ !empty($plan['featured']) ? 'featured' : '' }}">
                        @if (!empty($plan['featured']))
                            <div class="accounting-plan-badge mb-3">Paling sering dipilih</div>
                        @endif
                        <div class="h3 fw-800 mb-1">Accounting {{ $plan['name'] }}</div>
                        <div class="small text-muted mb-3">{{ $plan['caption'] }}</div>
                        <div class="display-6 fw-bold mb-2">{{ $plan['price'] }}</div>
                        <div class="small text-muted mb-4">per bulan</div>
                        <p class="small mb-4">{{ $plan['summary'] }}</p>
                        <div class="landing-checklist small mb-4">
                            @foreach ($plan['features'] as $feature)
                                <div><i class="ti ti-check text-success"></i> {{ $feature }}</div>
                            @endforeach
                            <div><i class="ti ti-check text-success"></i> POS tersedia sebagai add-on opsional</div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => $plan['code']]) }}" class="btn {{ !empty($plan['featured']) ? 'btn-light' : 'btn-dark' }} btn-lg">
                                Daftar {{ $plan['name'] }}
                            </a>
                            <a href="{{ route('landing.accounting') }}#features" class="btn {{ !empty($plan['featured']) ? 'btn-outline-light' : 'btn-outline-dark' }}">
                                Lihat fitur paket ini
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">Mulai Dengan Paket Yang Tepat</div>
            <h2 class="landing-section-title mb-3">Tidak perlu menunggu sistem yang terlalu rumit untuk mulai rapi.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Mulai dari Starter kalau Anda ingin yang simple. Pilih Growth atau Scale kalau bisnis Anda sudah aktif dengan pembelian supplier, stok, dan kebutuhan laporan yang lebih lengkap.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_starter']) }}" class="btn btn-outline-dark btn-lg">Mulai dari Starter</a>
                <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_growth']) }}" class="btn btn-dark btn-lg">Pilih Growth</a>
            </div>
        </div>
    </div>
</section>
@endsection
