@extends('layouts.landing')

@section('head_title', 'Jasa Pembuatan Website Bisnis | Company Profile, Landing Page, Website Custom')
@section('head_description', 'Jasa pembuatan website bisnis yang profesional, cepat, mobile-friendly, dan dirancang untuk mendatangkan leads atau penjualan. Cocok untuk company profile, landing page, katalog, dan website custom.')

@php
    $whatsAppNumber = '6281222229815';
    $waLink = function (string $message) use ($whatsAppNumber): string {
        return 'https://wa.me/' . $whatsAppNumber . '?text=' . urlencode($message);
    };

    $canonicalUrl = route('landing.website-service');
    $ogImage = asset('brand/logo-default.png');
    $seoKeywords = 'jasa pembuatan website, jasa website bisnis, website company profile, landing page bisnis, website katalog produk, website custom, jasa bikin website, website profesional bisnis';

    $problems = [
        'Tidak menghasilkan leads',
        'Tidak ada yang menghubungi',
        'Hanya jadi brosur online',
        'Tidak jelas apa tujuan websitenya',
    ];

    $services = [
        'Website Company Profile',
        'Landing Page yang fokus conversion',
        'Website katalog produk atau jasa',
        'Website custom sesuai kebutuhan',
    ];

    $outcomes = [
        ['icon' => 'ti-layout-dashboard', 'text' => 'Desain profesional dan modern'],
        ['icon' => 'ti-sitemap', 'text' => 'Struktur halaman yang jelas dan mudah dipahami'],
        ['icon' => 'ti-device-mobile', 'text' => 'Mobile-friendly dan cepat'],
        ['icon' => 'ti-speakerphone', 'text' => 'Siap digunakan untuk promosi dan ads'],
        ['icon' => 'ti-click', 'text' => 'CTA yang jelas untuk meningkatkan konversi'],
    ];

    $steps = [
        ['icon' => 'ti-message-circle-search', 'title' => 'Diskusi kebutuhan'],
        ['icon' => 'ti-schema', 'title' => 'Struktur dan wireframe'],
        ['icon' => 'ti-code', 'title' => 'Desain dan development'],
        ['icon' => 'ti-refresh', 'title' => 'Revisi dan finalisasi'],
        ['icon' => 'ti-rocket', 'title' => 'Launching'],
    ];

    $fitFor = [
        'UMKM dan brand yang belum punya website',
        'Bisnis yang ingin meningkatkan leads',
        'Owner yang ingin terlihat lebih profesional',
    ];

    $heroWa = $waLink('Halo, saya ingin konsultasi gratis untuk jasa pembuatan website bisnis.');
    $quoteWa = $waLink('Halo, saya ingin buat website untuk bisnis saya. Bisa diskusikan kebutuhannya?');
    $finalWa = $waLink('Halo, saya ingin mulai buat website bisnis saya. Bisa konsultasi dulu?');

    $serviceSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => 'Jasa Pembuatan Website Bisnis',
        'description' => 'Jasa pembuatan website bisnis untuk company profile, landing page, katalog produk atau jasa, dan website custom yang dirancang untuk mendatangkan leads dan meningkatkan konversi.',
        'serviceType' => [
            'Jasa Pembuatan Website',
            'Website Company Profile',
            'Landing Page',
            'Website Katalog',
            'Website Custom',
        ],
        'provider' => [
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('brand/favicon-512.png'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+62-812-222-9815',
                'contactType' => 'sales',
                'availableLanguage' => ['id', 'en'],
            ],
        ],
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Indonesia',
        ],
        'offers' => [
            '@type' => 'Offer',
            'name' => 'Jasa Pembuatan Website',
            'price' => '2500000',
            'priceCurrency' => 'IDR',
            'url' => $canonicalUrl,
        ],
        'url' => $canonicalUrl,
    ];

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('landing'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Jasa Pembuatan Website',
                'item' => $canonicalUrl,
            ],
        ],
    ];
@endphp

@push('head_meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
<meta name="keywords" content="{{ $seoKeywords }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="Jasa Pembuatan Website Bisnis | Company Profile, Landing Page, Website Custom">
<meta property="og:description" content="Jasa pembuatan website bisnis yang profesional, cepat, mobile-friendly, dan dirancang untuk mendatangkan leads atau penjualan.">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ config('app.name') }} - Jasa Pembuatan Website Bisnis">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Jasa Pembuatan Website Bisnis | Company Profile, Landing Page, Website Custom">
<meta name="twitter:description" content="Buat website bisnis yang bukan sekadar tampil, tapi membantu leads, promosi, dan pertumbuhan bisnis Anda.">
<meta name="twitter:image" content="{{ $ogImage }}">
<script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('head')
<style>
    .website-lp-hero-title {
        font-size: clamp(2.35rem, 5vw, 4.15rem);
        line-height: 1.05;
        letter-spacing: -0.04em;
    }
    .website-lp-subtitle {
        font-size: clamp(1.05rem, 1.8vw, 1.22rem);
        line-height: 1.76;
        color: #475569;
        max-width: 44rem;
    }
    .website-lp-lead {
        font-size: 1.05rem;
        line-height: 1.76;
        color: #475569;
    }
    .website-lp-text {
        font-size: 1rem;
        line-height: 1.72;
        color: #475569;
    }
    .website-lp-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
        color: #ea580c;
    }
    .website-lp-soft {
        background: linear-gradient(180deg, #fffdf8 0%, #fff7ed 100%);
        border: 1px solid rgba(249, 115, 22, .14);
    }
    .website-lp-note {
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        border: 1px solid rgba(15, 23, 42, .08);
    }
</style>
@endpush

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1 landing-nav-shell">
                <a href="#masalah" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-alert-circle"></i><span>Masalah</span></a>
                <a href="#layanan" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-browser"></i><span>Layanan</span></a>
                <a href="#hasil" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-checklist"></i><span>Hasil</span></a>
                <a href="#proses" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-route"></i><span>Proses</span></a>
                <a href="#pricing" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-receipt-2"></i><span>Pricing</span></a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Konsultasi Gratis Sekarang</a>
                <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm">Buat Website Saya</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<section class="landing-hero py-5 py-lg-6" style="background:radial-gradient(circle at top left, rgba(59,130,246,.14), transparent 34%), linear-gradient(180deg,#f8fbff 0%,#fff 56%,#f8fafc 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-browser-check"></i> Jasa pembuatan website untuk bisnis
                </div>
                <h1 class="landing-headline website-lp-hero-title mb-4">
                    <span>Website bisnis yang</span>
                    <br>
                    bukan sekadar tampil.
                </h1>
                <p class="landing-subtext website-lp-subtitle mb-4">
                    Kami bantu Anda membuat website yang profesional, cepat, dan dirancang untuk mendatangkan leads atau penjualan. Bukan hanya jadi, tapi punya tujuan bisnis yang jelas.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-dark">Konsultasi Gratis Sekarang</a>
                    <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-outline-dark">Buat Website Saya</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Company Profile</span>
                    <span class="landing-pill">Landing Page</span>
                    <span class="landing-pill">Website Katalog</span>
                    <span class="landing-pill">Website Custom</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff7ed; border:1px solid rgba(249,115,22,.12);">
                                <div class="fw-semibold mb-1">Website biasa</div>
                                <div class="website-lp-text">Tampil, tetapi tidak jelas ingin mengarahkan visitor ke mana.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#eff6ff; border:1px solid rgba(59,130,246,.12);">
                                <div class="fw-semibold mb-1">Website yang tepat</div>
                                <div class="website-lp-text">Punya struktur, CTA, dan alur yang jelas untuk mendukung tujuan bisnis.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4 website-lp-soft">
                                <div class="text-uppercase fw-bold small text-muted mb-2">Fokus utama</div>
                                <div class="h4 mb-2">Website yang bekerja untuk bisnis Anda.</div>
                                <div class="website-lp-text mb-0">Bukan hanya terlihat rapi, tapi membantu promosi, membangun kepercayaan, dan mengarahkan visitor ke action yang Anda butuhkan.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="masalah" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Masalah</div>
            <h2 class="landing-section-title">Banyak bisnis sudah punya website, tapi tidak berdampak.</h2>
            <p class="landing-subtext website-lp-lead mx-auto">Akhirnya website ada, tetapi tidak membantu leads, tidak ada yang menghubungi, dan tidak jelas tujuannya.</p>
        </div>
        <div class="landing-panel p-4 p-lg-5">
            <div class="row g-3">
                @foreach($problems as $problem)
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 rounded-4 p-3 h-100" style="background:#fff8ef;">
                            <span class="website-lp-icon"><i class="ti ti-alert-triangle" style="font-size:1.25rem;"></i></span>
                            <div class="website-lp-text">{{ $problem }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <div class="fw-semibold">Website ada, tapi tidak berdampak.</div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Solusi</div>
                <h2 class="landing-section-title mb-3">Kami tidak hanya membuat website.</h2>
                <p class="landing-subtext website-lp-lead mb-3">Kami bantu membangun struktur halaman yang jelas, alur user yang mengarah ke action, dan tampilan profesional yang dipercaya.</p>
                <p class="landing-subtext website-lp-lead mb-4">Fokus kami sederhana: website yang bekerja untuk bisnis Anda, bukan sekadar pajangan.</p>
                <div class="landing-checklist">
                    <div><i class="ti ti-check text-success"></i> Struktur halaman yang jelas</div>
                    <div><i class="ti ti-check text-success"></i> Alur user yang mengarah ke action</div>
                    <div><i class="ti ti-check text-success"></i> Tampilan profesional yang dipercaya</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5 h-100 website-lp-note">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Yang dibenahi</div>
                    <h3 class="h4 mb-3">Tujuan website dibuat lebih jelas sejak awal.</h3>
                    <p class="website-lp-text mb-0">Apakah website Anda ingin membangun kredibilitas, mengumpulkan leads, mendukung promosi, atau mendorong penjualan. Struktur dan CTA akan mengikuti tujuan itu.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="layanan" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Layanan</div>
            <h2 class="landing-section-title">Jenis website yang kami kerjakan untuk kebutuhan bisnis.</h2>
        </div>
        <div class="row g-4">
            @foreach($services as $service)
                <div class="col-md-6 col-lg-3">
                    <div class="landing-panel p-4 h-100 text-center">
                        <div class="website-lp-icon mx-auto mb-3"><i class="ti ti-browser" style="font-size:1.2rem;"></i></div>
                        <div class="fw-semibold mb-2">{{ $service }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="hasil" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Yang Anda Dapatkan</div>
            <h2 class="landing-section-title">Website bukan sekadar tampil, tapi punya fungsi yang jelas.</h2>
        </div>
        <div class="row g-4">
            @foreach($outcomes as $outcome)
                <div class="col-lg-4 col-md-6">
                    <div class="landing-panel p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <span class="website-lp-icon"><i class="ti {{ $outcome['icon'] }}" style="font-size:1.15rem;"></i></span>
                            <div class="website-lp-text">{{ $outcome['text'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="proses" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4">
                <div class="landing-eyebrow mb-2">Proses</div>
                <h2 class="landing-section-title mb-3">Pengerjaan dibuat ringkas, jelas, dan terarah.</h2>
                <p class="landing-subtext website-lp-lead mb-4">Dari diskusi kebutuhan sampai launching, setiap tahap dibuat supaya hasil akhir tetap relevan dengan tujuan bisnis Anda.</p>
                <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-dark">Buat Website Saya</a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($steps as $step)
                        <div class="col-md-6">
                            <div class="landing-panel p-4 h-100" style="border-left:4px solid #60a5fa;">
                                <div class="website-lp-icon mb-3"><i class="ti {{ $step['icon'] }}" style="font-size:1.15rem;"></i></div>
                                <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section id="pricing" class="py-5 py-lg-6" style="background:#fdfaf5; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Pricing</div>
            <h2 class="landing-section-title">Mulai dari Rp2.500.000</h2>
            <p class="landing-subtext website-lp-lead mx-auto">Harga menyesuaikan jumlah halaman, kompleksitas, dan fitur tambahan yang dibutuhkan.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="landing-panel p-4 p-lg-5 text-center" style="border:1px solid rgba(59,130,246,.14); box-shadow:0 24px 60px rgba(15,23,42,.08);">
                    <div class="website-lp-icon mx-auto mb-3"><i class="ti ti-browser-check" style="font-size:1.2rem;"></i></div>
                    <div class="display-5 fw-bold mb-2" style="color:#2563eb;">Rp2.500.000</div>
                    <div class="website-lp-text mb-4">Titik awal untuk website bisnis yang profesional dan siap dipakai untuk promosi.</div>
                    <div class="row g-3 text-start">
                        <div class="col-md-4"><div class="website-lp-text"><i class="ti ti-check text-success"></i> Jumlah halaman</div></div>
                        <div class="col-md-4"><div class="website-lp-text"><i class="ti ti-check text-success"></i> Kompleksitas</div></div>
                        <div class="col-md-4"><div class="website-lp-text"><i class="ti ti-check text-success"></i> Fitur tambahan</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Siapa yang Cocok</div>
                <h2 class="landing-section-title mb-3">Cocok untuk bisnis yang ingin tampil lebih serius dan mendapatkan lebih banyak peluang.</h2>
                <div class="landing-checklist">
                    @foreach($fitFor as $item)
                        <div><i class="ti ti-check text-success"></i> {{ $item }}</div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5 h-100 website-lp-note">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Nilai bisnis</div>
                    <h3 class="h4 mb-3">Website seharusnya membantu bisnis berkembang, bukan sekadar pajangan.</h3>
                    <p class="website-lp-text mb-0">Jika tujuan websitenya jelas, visitor lebih mudah memahami value bisnis Anda dan lebih mudah diarahkan untuk menghubungi atau melakukan action.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center website-lp-note" style="box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <div class="landing-eyebrow mb-2">Mulai Sekarang</div>
            <h2 class="landing-section-title mb-3">Website Anda seharusnya membantu bisnis berkembang, bukan sekadar pajangan.</h2>
            <p class="landing-subtext website-lp-lead mx-auto mb-4" style="max-width:760px;">Kalau Anda ingin website yang lebih profesional, lebih jelas tujuannya, dan lebih siap mendatangkan leads, mari diskusikan kebutuhannya.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Konsultasi Gratis Sekarang</a>
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-lg">Mulai Buat Website Anda</a>
            </div>
        </div>
    </div>
</section>
@endsection
