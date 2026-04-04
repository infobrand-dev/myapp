@extends('layouts.landing')

@section('head_title', config('app.name') . ' - Platform bisnis untuk operasional, penjualan, customer, dan workflow tim')
@section('head_description', 'Meetra adalah platform bisnis yang membantu tim menjalankan penjualan, customer handling, operasional transaksi, dan workflow kerja dalam satu workspace.')

@section('content')

<section id="platform" class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-badge mb-4">
                    <i class="ti ti-layout-dashboard"></i> Meetra Platform
                </div>
                <h1 class="landing-headline mb-4">
                    <span>Meetra</span> membantu bisnis menjalankan customer, transaksi, dan workflow tim dalam satu tempat.
                </h1>
                <p class="landing-subtext mb-5">
                    Meetra adalah platform bisnis. Bukan hanya untuk chat, bukan hanya untuk transaksi, dan bukan hanya untuk laporan. Meetra membantu bisnis bekerja lebih rapi dari sisi customer-facing sampai operasional internal, tanpa memecah data ke terlalu banyak alat.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('landing.accounting') }}" class="btn btn-lg btn-dark">Lihat Accounting</a>
                    <a href="{{ route('landing.omnichannel') }}" class="btn btn-lg btn-outline-dark">Lihat Omnichannel</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Customer Inbox</span>
                    <span class="landing-pill">Sales & Payments</span>
                    <span class="landing-pill">Purchases & Stock</span>
                    <span class="landing-pill">Task & Workflow</span>
                    <span class="landing-pill">Reporting</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="meetra-hero-showcase">
                    @foreach (array_slice($featuredModules, 0, 6) as $module)
                        <div class="meetra-showcase-card">
                            <div class="meetra-showcase-icon">{!! $module['icon_svg'] !!}</div>
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
            <div class="landing-eyebrow mb-2">Apa Itu Meetra</div>
            <h2 class="landing-section-title">Meetra adalah workspace bisnis yang bisa dipakai sesuai tahap pertumbuhan usaha Anda.</h2>
            <p class="landing-subtext mx-auto">Ada bisnis yang butuh fokus ke komunikasi customer lebih dulu. Ada juga yang lebih butuh membereskan transaksi, pembayaran, stok, dan laporan. Meetra menyiapkan keduanya dalam platform yang sama.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Mulai dari kebutuhan terdekat</h3>
                    <p class="small text-muted mb-0">Anda tidak harus memakai semua capability sekaligus. Mulai dari yang paling terasa penting untuk tim hari ini.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Data lebih nyambung</h3>
                    <p class="small text-muted mb-0">Kontak, transaksi, dan ritme kerja tim tidak perlu tersebar ke terlalu banyak alat terpisah.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Bisa bertumbuh tanpa pindah arah</h3>
                    <p class="small text-muted mb-0">Saat proses bisnis berkembang, workspace yang sama tetap bisa dipakai tanpa memulai ulang dari nol.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="products" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Solusi Utama</div>
            <h2 class="landing-section-title">Dua jalur utama yang paling siap dijalankan sekarang.</h2>
            <p class="landing-subtext mx-auto">Keduanya berjalan di atas platform yang sama, tetapi menjawab kebutuhan yang berbeda.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="meetra-solution-card primary">
                    <div class="landing-badge mb-3" style="background:rgba(255,255,255,.12); color:#fff;">
                        <i class="ti ti-report-money"></i> Accounting
                    </div>
                    <h3 class="h2 mb-3">Untuk bisnis yang ingin merapikan transaksi dan operasional harian.</h3>
                    <p class="small mb-4">Cocok untuk alur penjualan, pembayaran, pembelian, kas, stok, produk, kontak, dan laporan operasional. Bisa mulai dari paket simple, lalu berkembang sesuai kebutuhan.</p>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Sales, payments, finance, products, contacts</div>
                        <div><i class="ti ti-check text-success"></i> Purchases, inventory, dan full reports di paket lebih lengkap</div>
                        <div><i class="ti ti-check text-success"></i> POS tersedia sebagai add-on</div>
                    </div>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <a href="{{ route('landing.accounting') }}" class="btn btn-light">Lihat Accounting</a>
                        <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_growth']) }}" class="btn btn-outline-light">Daftar Growth</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="meetra-solution-card">
                    <div class="landing-badge mb-3">
                        <i class="ti ti-message-circle-2"></i> Omnichannel
                    </div>
                    <h3 class="h2 mb-3">Untuk tim yang fokus ke komunikasi customer dan follow up lead.</h3>
                    <p class="small text-muted mb-4">Cocok untuk social inbox, live chat, CRM lite, WhatsApp, dan automation. Jalur ini tetap ada untuk bisnis yang memang butuh customer-facing workflow.</p>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> Shared inbox dan social media conversation</div>
                        <div><i class="ti ti-check text-success"></i> CRM lite, live chat, AI, dan WhatsApp</div>
                        <div><i class="ti ti-check text-success"></i> Cocok untuk sales dan support team</div>
                    </div>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <a href="{{ route('landing.omnichannel') }}" class="btn btn-dark">Lihat Omnichannel</a>
                        <a href="{{ route('onboarding.create', ['product_line' => 'omnichannel', 'plan' => 'growth-v2']) }}" class="btn btn-outline-dark">Daftar Omnichannel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="capabilities" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-end mb-4">
            <div class="col-lg-7">
                <div class="landing-eyebrow mb-2">Capability</div>
                <h2 class="landing-section-title mb-3">Contoh capability yang tersedia di dalam platform Meetra.</h2>
                <p class="landing-subtext mb-0">Bukan semua harus dipakai sekaligus. Bagian ini hanya menunjukkan gambaran bahwa Meetra bisa membantu lebih dari satu jenis workflow bisnis.</p>
            </div>
            <div class="col-lg-5">
                <div class="landing-panel p-4">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Ringkasan cepat</div>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> Customer conversation dan lead follow up</div>
                        <div><i class="ti ti-check text-success"></i> Sales, pembayaran, pembelian, dan stok</div>
                        <div><i class="ti ti-check text-success"></i> Workflow tim dan reporting operasional</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            @foreach ($featuredModules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="meetra-capability-card">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div class="meetra-capability-icon">{!! $module['icon_svg'] !!}</div>
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
            @endforeach
        </div>
    </div>
</section>

<section id="why" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kenapa Meetra</div>
            <h2 class="landing-section-title">Lebih general sebagai platform, tapi tetap jelas saat dipakai untuk kebutuhan spesifik.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Bukan tool yang terlalu sempit</h3>
                    <p class="small text-muted mb-0">Meetra tidak berhenti di satu fungsi saja. Ia bisa dipakai untuk customer-facing, transaksi, dan workflow operasional.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Tetap mudah dipahami user</h3>
                    <p class="small text-muted mb-0">Produk dijelaskan per jalur penggunaan, bukan dengan istilah teknis yang membingungkan calon user.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Siap untuk grow step by step</h3>
                    <p class="small text-muted mb-0">Mulai dari jalur yang paling penting sekarang, lalu tambah kebutuhan lain saat bisnis sudah siap.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">Mulai dari Yang Paling Relevan</div>
            <h2 class="landing-section-title mb-3">Kalau ingin go-live cepat, mulai dari jalur yang paling dekat dengan kebutuhan bisnis Anda.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Untuk sekarang, jalur Accounting paling siap untuk bisnis yang ingin merapikan transaksi dan operasional. Kalau fokus Anda ada di percakapan customer dan lead handling, Omnichannel tetap tersedia sebagai jalur lain.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('landing.accounting') }}" class="btn btn-dark btn-lg">Lihat Accounting</a>
                <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_starter']) }}" class="btn btn-outline-dark btn-lg">Daftar Accounting</a>
            </div>
        </div>
    </div>
</section>
@endsection
