@extends('layouts.landing')

@section('head_title', config('app.name') . ' - Platform Bisnis Modular untuk Omnichannel dan Accounting')
@section('head_description', 'Meetra adalah platform bisnis modular yang menggabungkan omnichannel, CRM, AI, sales, payments, purchases, finance, POS, reports, dan module operasional lain dalam satu workspace.')

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing.meetra') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#platform" class="landing-nav-link">Platform</a>
                <a href="#products" class="landing-nav-link">Product Line</a>
                <a href="#modules" class="landing-nav-link">Module</a>
                <a href="#why" class="landing-nav-link">Kenapa Meetra</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('landing.accounting') }}" class="btn btn-dark btn-sm">Lihat Accounting</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<style>
    .meetra-hero-grid { display:grid; gap:1rem; grid-template-columns:repeat(3,minmax(0,1fr)); }
    .meetra-stack-card { position:relative; overflow:hidden; border-radius:26px; background:#fff; border:1px solid rgba(15,23,42,.08); padding:1rem; box-shadow:0 22px 50px rgba(15,23,42,.08); min-height:165px; }
    .meetra-stack-card::after { content:""; position:absolute; inset:auto -40px -40px auto; width:110px; height:110px; border-radius:999px; background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(16,185,129,.12)); }
    .meetra-stack-icon { width:52px; height:52px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#0f172a,#1d4ed8); color:#fff; margin-bottom:1rem; }
    .meetra-stack-icon svg { width:26px; height:26px; }
    .meetra-stack-icon svg * { stroke: currentColor !important; fill:none !important; }
    .meetra-product-card { border:1px solid rgba(15,23,42,.08); border-radius:30px; background:#fff; box-shadow:0 24px 50px rgba(15,23,42,.08); padding:2rem; height:100%; }
    .meetra-product-card.highlight { background:linear-gradient(180deg,#0f172a 0%,#1e3a8a 100%); color:#fff; }
    .meetra-product-card.highlight .text-muted,
    .meetra-product-card.highlight .small { color:rgba(255,255,255,.72) !important; }
    .meetra-module-card { border:1px solid rgba(15,23,42,.08); border-radius:26px; background:#fff; padding:1.35rem; height:100%; box-shadow:0 18px 38px rgba(15,23,42,.06); }
    .meetra-module-icon { width:54px; height:54px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:#eff6ff; color:#1d4ed8; }
    .meetra-module-icon svg { width:26px; height:26px; }
    .meetra-module-icon svg * { stroke: currentColor !important; fill:none !important; }
    .meetra-why-card { border:1px solid rgba(15,23,42,.08); border-radius:24px; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); padding:1.4rem; height:100%; }
    @media (max-width: 991.98px) {
        .meetra-hero-grid { grid-template-columns:1fr 1fr; }
    }
    @media (max-width: 767.98px) {
        .meetra-hero-grid { grid-template-columns:1fr; }
    }
</style>

<section id="platform" class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-badge mb-4">
                    <i class="ti ti-layout-dashboard"></i> Platform Bisnis Modular
                </div>
                <h1 class="landing-headline mb-4">
                    <span>Meetra</span> menggabungkan banyak module bisnis dalam satu workspace.
                </h1>
                <p class="landing-subtext mb-5">
                    Dari percakapan customer sampai transaksi operasional, Meetra dirancang sebagai platform modular. Anda bisa bergerak dari omnichannel ke accounting, dari CRM ke task execution, tanpa memecah data dan ritme kerja tim.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="#products" class="btn btn-lg btn-dark">Lihat Product Line</a>
                    <a href="#modules" class="btn btn-lg btn-outline-dark">Lihat Module</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Omnichannel</span>
                    <span class="landing-pill">CRM</span>
                    <span class="landing-pill">AI Automation</span>
                    <span class="landing-pill">Accounting</span>
                    <span class="landing-pill">Operations</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="meetra-hero-grid">
                    @foreach (array_slice($featuredModules, 0, 9) as $module)
                        <div class="meetra-stack-card">
                            <div class="meetra-stack-icon">{!! $module['icon_svg'] !!}</div>
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

<section id="products" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Product Line</div>
            <h2 class="landing-section-title">Dua jalur utama yang bisa tumbuh dari platform yang sama.</h2>
            <p class="landing-subtext mx-auto">Meetra bisa diposisikan sesuai kebutuhan tim. Ada jalur untuk customer-facing operation, dan ada jalur untuk ritme transaksi internal.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="meetra-product-card highlight">
                    <div class="landing-badge mb-3" style="background:rgba(255,255,255,.12); color:#fff;">
                        <i class="ti ti-message-circle-2"></i> Omnichannel
                    </div>
                    <h3 class="h2 mb-3">Satukan percakapan, lead, dan channel komunikasi tim.</h3>
                    <p class="small mb-4">Cocok untuk sales, support, dan customer engagement yang butuh inbox terpusat, CRM lite, live chat, social inbox, dan AI.</p>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Shared inbox lintas channel</div>
                        <div><i class="ti ti-check text-success"></i> CRM dan follow up lead</div>
                        <div><i class="ti ti-check text-success"></i> AI, WhatsApp API, dan social inbox</div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('landing.omnichannel') }}" class="btn btn-light">Buka Omnichannel</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="meetra-product-card">
                    <div class="landing-badge mb-3">
                        <i class="ti ti-report-money"></i> Accounting
                    </div>
                    <h3 class="h2 mb-3">Rapikan transaksi, pembayaran, pembelian, kas, dan laporan operasional.</h3>
                    <p class="small text-muted mb-4">Cocok untuk tim internal yang ingin sales, payments, purchases, finance, POS, dan reporting tetap nyambung dalam satu workspace.</p>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> Sales dan purchases harian</div>
                        <div><i class="ti ti-check text-success"></i> Payments, finance, dan POS</div>
                        <div><i class="ti ti-check text-success"></i> Reporting operasional yang lebih cepat dibaca</div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('landing.accounting') }}" class="btn btn-dark">Buka Accounting</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="modules" class="py-5" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-end mb-4">
            <div class="col-lg-7">
                <div class="landing-eyebrow mb-2">Module Catalog</div>
                <h2 class="landing-section-title mb-3">Banyak module, satu arah kerja yang lebih rapi.</h2>
                <p class="landing-subtext mb-0">Landing ini menampilkan sebagian module yang paling mudah dijelaskan ke publik. Fokusnya bukan pada sample data atau tooling internal, tetapi pada capability yang memang membantu tim bekerja setiap hari.</p>
            </div>
            <div class="col-lg-5">
                <div class="landing-panel p-4">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Gambaran cepat</div>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> Customer conversation dan lead follow up</div>
                        <div><i class="ti ti-check text-success"></i> Sales, payment, purchase, finance, dan POS</div>
                        <div><i class="ti ti-check text-success"></i> Reporting, task execution, dan utility pendukung</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            @foreach ($featuredModules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="meetra-module-card">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div class="meetra-module-icon">{!! $module['icon_svg'] !!}</div>
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
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Accounting Preview</div>
                <h2 class="landing-section-title mb-3">Sebagian module di Meetra sudah siap dibundel sebagai Accounting.</h2>
                <p class="landing-subtext mb-0">Jalur Accounting memanfaatkan module existing yang memang relevan untuk transaksi internal. Ini cocok untuk bisnis yang ingin lebih tertib di penjualan, pembelian, pembayaran, cashflow, dan outlet operation.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    @foreach ($accountingModules as $module)
                        <div class="col-md-6 col-xl-4">
                            <div class="landing-feature-card p-4 h-100">
                                <div class="meetra-module-icon mb-3">{!! $module['icon_svg'] !!}</div>
                                <h3 class="h6 mb-2">{{ $module['name'] }}</h3>
                                <p class="small text-muted mb-0">{{ $module['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="why" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kenapa Meetra</div>
            <h2 class="landing-section-title">Bukan sekadar kumpulan fitur, tapi platform yang bisa tumbuh bersama proses bisnis.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Data lebih nyambung</h3>
                    <p class="small text-muted mb-0">Percakapan customer, kontak, transaksi, dan laporan tidak perlu disebar ke terlalu banyak alat yang terpisah.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Bisa mulai dari kebutuhan utama</h3>
                    <p class="small text-muted mb-0">Tim bisa mulai dari Omnichannel atau Accounting lebih dulu, lalu memperluas workflow sesuai ritme operasionalnya.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-why-card">
                    <h3 class="h5 mb-2">Module tetap terasa satu platform</h3>
                    <p class="small text-muted mb-0">Walau banyak capability, pengalaman yang dibangun tetap mengarah ke satu workspace dan satu cara kerja tim.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">Explore Meetra</div>
            <h2 class="landing-section-title mb-3">Pilih jalur yang paling dekat dengan prioritas bisnis Anda sekarang.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Jika fokus Anda ada di komunikasi customer, masuk lewat Omnichannel. Jika yang ingin dirapikan dulu adalah ritme transaksi internal, masuk lewat Accounting.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('landing.omnichannel') }}" class="btn btn-outline-dark btn-lg">Lihat Omnichannel</a>
                <a href="{{ route('landing.accounting') }}" class="btn btn-dark btn-lg">Lihat Accounting</a>
            </div>
        </div>
    </div>
</section>
@endsection
