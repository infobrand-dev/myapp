@extends('layouts.landing')

@section('head_title', config('app.name') . ' Accounting - Sales, Payments, Purchases, Finance, POS, dan Reports')
@section('head_description', 'Product line Accounting dari Meetra untuk merapikan penjualan, pembelian, pembayaran, cashflow, kasir outlet, dan reporting dalam satu workspace.')

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#overview" class="landing-nav-link">Overview</a>
                <a href="#modules" class="landing-nav-link">Module</a>
                <a href="#tiers" class="landing-nav-link">Paket</a>
                <a href="#workflow" class="landing-nav-link">Workflow</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('landing') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Tentang Meetra</a>
                <a href="#tiers" class="btn btn-dark btn-sm">Lihat Paket</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
@php
    $tiers = [
        [
            'name' => 'Starter',
            'caption' => 'Untuk tim yang baru merapikan ritme transaksi.',
            'limits' => ['1 company', '1 branch', '5 users', '1 GB storage'],
        ],
        [
            'name' => 'Growth',
            'caption' => 'Untuk tim aktif yang butuh kapasitas lebih longgar.',
            'limits' => ['1 company', '3 branches', '15 users', '5 GB storage'],
            'featured' => true,
        ],
        [
            'name' => 'Scale',
            'caption' => 'Untuk operasional multi-user dan multi-branch yang lebih padat.',
            'limits' => ['3 companies', '10 branches', '50 users', '20 GB storage'],
        ],
    ];
@endphp

<style>
    .product-hero-grid { display:grid; gap:1rem; grid-template-columns:repeat(2,minmax(0,1fr)); }
    .product-mini-card { border:1px solid rgba(15,23,42,.08); border-radius:24px; background:rgba(255,255,255,.88); padding:1rem; box-shadow:0 20px 45px rgba(15,23,42,.08); min-height:148px; }
    .product-mini-icon { width:48px; height:48px; display:flex; align-items:center; justify-content:center; border-radius:16px; background:linear-gradient(135deg,#0f172a,#1d4ed8); color:#fff; }
    .product-mini-icon svg { width:24px; height:24px; }
    .product-mini-icon svg * { stroke: currentColor !important; fill: none !important; }
    .product-module-card { border:1px solid rgba(15,23,42,.08); border-radius:28px; background:#fff; box-shadow:0 18px 40px rgba(15,23,42,.06); overflow:hidden; }
    .product-module-card .body { padding:1.5rem; }
    .product-module-icon { width:58px; height:58px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#eff6ff,#dbeafe); color:#1d4ed8; }
    .product-module-icon svg { width:28px; height:28px; }
    .product-module-icon svg * { stroke: currentColor !important; fill: none !important; }
    .product-tier-card { border:1px solid rgba(15,23,42,.08); border-radius:28px; background:#fff; padding:1.75rem; height:100%; box-shadow:0 16px 35px rgba(15,23,42,.06); }
    .product-tier-card.featured { background:linear-gradient(180deg,#0f172a 0%,#172554 100%); color:#fff; box-shadow:0 24px 50px rgba(15,23,42,.22); }
    .product-tier-card.featured .text-muted,
    .product-tier-card.featured .small { color:rgba(255,255,255,.72) !important; }
    .product-tier-badge { display:inline-flex; padding:.35rem .75rem; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:.78rem; font-weight:700; }
    .product-flow-card { border:1px solid rgba(15,23,42,.08); border-radius:26px; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); padding:1.4rem; height:100%; }
    .product-flow-step { width:42px; height:42px; border-radius:14px; display:flex; align-items:center; justify-content:center; background:#0f172a; color:#fff; font-weight:800; }
    .support-strip { display:grid; gap:1rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .support-card { border:1px dashed rgba(15,23,42,.18); border-radius:22px; background:#fff; padding:1rem; }
    .support-icon { width:42px; height:42px; border-radius:14px; display:flex; align-items:center; justify-content:center; background:#f8fafc; color:#334155; }
    .support-icon svg { width:22px; height:22px; }
    .support-icon svg * { stroke: currentColor !important; fill:none !important; }
    @media (max-width: 991.98px) {
        .product-hero-grid, .support-strip { grid-template-columns:1fr; }
    }
</style>

<section id="overview" class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-report-money"></i> Meetra Accounting
                </div>
                <h1 class="landing-headline mb-4">
                    Rapikan <span>sales, pembayaran, pembelian, kas, dan laporan</span> dalam satu workspace.
                </h1>
                <p class="landing-subtext mb-5">
                    Product line Accounting di Meetra dirancang untuk tim operasional yang ingin alur transaksi harian lebih tertib. Semua tetap berjalan dari modul existing yang sudah saling terhubung, tanpa perlu menyusun ulang proses dari nol.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="#modules" class="btn btn-lg btn-dark">Lihat Module</a>
                    <a href="#tiers" class="btn btn-lg btn-outline-dark">Bandingkan Paket</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($modules as $module)
                        <span class="landing-pill">{{ $module['name'] }}</span>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="product-hero-grid">
                    @foreach ($modules as $module)
                        <div class="product-mini-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="product-mini-icon">{!! $module['icon_svg'] !!}</div>
                                <div>
                                    <div class="text-uppercase small fw-bold text-muted mb-1">{{ $module['eyebrow'] }}</div>
                                    <div class="fw-bold mb-2">{{ $module['name'] }}</div>
                                    <div class="small text-muted">{{ $module['description'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="modules" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Core Bundle</div>
            <h2 class="landing-section-title">Enam module inti untuk ritme transaksi harian.</h2>
            <p class="landing-subtext mx-auto">Accounting di Meetra dibangun untuk memudahkan alur operasional sehari-hari, dari transaksi masuk sampai ringkasan performa yang siap dibaca tim.</p>
        </div>
        <div class="row g-4">
            @foreach ($modules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="product-module-card h-100">
                        <div class="body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="product-module-icon">{!! $module['icon_svg'] !!}</div>
                                <div class="small text-uppercase fw-bold text-muted">{{ $module['eyebrow'] }}</div>
                            </div>
                            <h3 class="h5 mb-2">{{ $module['name'] }}</h3>
                            <p class="text-muted small mb-3">{{ $module['description'] }}</p>
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

<section id="workflow" class="py-5" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Workflow</div>
            <h2 class="landing-section-title">Satu alur kerja yang saling menyambung.</h2>
            <p class="landing-subtext mx-auto">Tim tidak perlu berpindah-pindah spreadsheet atau aplikasi setiap kali ada transaksi, pembayaran, atau kebutuhan rekap.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="product-flow-card">
                    <div class="product-flow-step mb-3">1</div>
                    <h3 class="h5 mb-2">Catat transaksi</h3>
                    <p class="text-muted small mb-0">Mulai dari sales, purchases, atau kasir outlet dalam workspace yang sama.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="product-flow-card">
                    <div class="product-flow-step mb-3">2</div>
                    <h3 class="h5 mb-2">Pantau pembayaran</h3>
                    <p class="text-muted small mb-0">Status pembayaran lebih jelas agar tim tahu transaksi mana yang sudah beres dan mana yang belum.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="product-flow-card">
                    <div class="product-flow-step mb-3">3</div>
                    <h3 class="h5 mb-2">Jaga cashflow</h3>
                    <p class="text-muted small mb-0">Kas masuk dan kas keluar operasional tetap tercatat untuk pembacaan harian yang lebih tertib.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="product-flow-card">
                    <div class="product-flow-step mb-3">4</div>
                    <h3 class="h5 mb-2">Baca performa</h3>
                    <p class="text-muted small mb-0">Lihat ringkasan laporan agar keputusan tim lebih cepat dan tidak menunggu rekap manual.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Module Pendukung</div>
                <h2 class="landing-section-title mb-3">Masih nyambung dengan data master dan operasional yang lebih luas.</h2>
                <p class="landing-subtext mb-0">Beberapa workflow accounting membutuhkan data pendukung seperti produk, stok, kontak, atau aturan promo. Karena itu Meetra tetap menyiapkan jalur yang nyambung saat operasional Anda berkembang.</p>
            </div>
            <div class="col-lg-7">
                <div class="support-strip">
                    @foreach ($supportingModules as $module)
                        <div class="support-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="support-icon">{!! $module['icon_svg'] !!}</div>
                                <div>
                                    <div class="fw-bold mb-1">{{ $module['name'] }}</div>
                                    <div class="small text-muted">{{ $module['description'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="tiers" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Paket</div>
            <h2 class="landing-section-title">Starter, Growth, dan Scale.</h2>
            <p class="landing-subtext mx-auto">Semua tier membawa bundle inti yang sama. Perbedaannya ada pada kapasitas workspace, user, branch, storage, dan skala operasional.</p>
        </div>
        <div class="row g-4">
            @foreach ($tiers as $tier)
                <div class="col-lg-4">
                    <div class="product-tier-card {{ !empty($tier['featured']) ? 'featured' : '' }}">
                        @if (!empty($tier['featured']))
                            <div class="product-tier-badge mb-3">Paling sering dipilih</div>
                        @endif
                        <div class="h3 fw-800 mb-2">Accounting {{ $tier['name'] }}</div>
                        <div class="text-muted small mb-4">{{ $tier['caption'] }}</div>
                        <div class="landing-checklist small">
                            @foreach ($tier['limits'] as $limit)
                                <div><i class="ti ti-check text-success"></i> {{ $limit }}</div>
                            @endforeach
                            <div><i class="ti ti-check text-success"></i> Sales, payments, purchases, finance, POS, dan reports</div>
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
            <div class="landing-eyebrow mb-2">Accounting by Meetra</div>
            <h2 class="landing-section-title mb-3">Bangun ritme operasional yang lebih rapi tanpa memecah workflow tim.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Jika Anda ingin penjualan, pembelian, pembayaran, kas, dan laporan tetap nyambung dalam satu workspace, product line Accounting adalah jalur yang paling pas untuk mulai.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('landing') }}" class="btn btn-outline-dark btn-lg">Lihat Semua Product</a>
                <a href="{{ route('landing.omnichannel') }}" class="btn btn-dark btn-lg">Lihat Omnichannel</a>
            </div>
        </div>
    </div>
</section>
@endsection
