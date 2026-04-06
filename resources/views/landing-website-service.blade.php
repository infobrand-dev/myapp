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
    $heroImage = asset('images/landing/website-service-hero-transparent.png');
    $seoKeywords = 'jasa pembuatan website, jasa website bisnis, website company profile, landing page bisnis, website katalog produk, website custom, jasa bikin website, website profesional bisnis';

    $problems = [
        'Tidak menghasilkan leads',
        'Tidak ada yang menghubungi',
        'Hanya jadi brosur online',
        'Tidak jelas apa tujuan websitenya',
    ];

    $services = [
        [
            'icon'  => 'ti-building',
            'title' => 'Website Company Profile',
            'desc'  => 'Tampilkan identitas dan kredibilitas bisnis Anda. Cocok untuk meyakinkan calon klien sebelum mereka menghubungi.',
        ],
        [
            'icon'  => 'ti-target',
            'title' => 'Landing Page Konversi',
            'desc'  => 'Satu halaman fokus dengan CTA yang jelas. Dirancang untuk mendatangkan leads dari iklan atau promosi.',
        ],
        [
            'icon'  => 'ti-shopping-bag',
            'title' => 'Website Katalog',
            'desc'  => 'Tampilkan produk atau jasa dengan rapi. Visitor langsung tahu apa yang Anda jual dan bagaimana membelinya.',
        ],
        [
            'icon'  => 'ti-code',
            'title' => 'Website Custom',
            'desc'  => 'Kebutuhan spesifik yang tidak muat di template standar. Kami kerjakan sesuai alur dan fitur yang Anda butuhkan.',
        ],
    ];

    $outcomes = [
        ['icon' => 'ti-layout-dashboard', 'title' => 'Desain profesional', 'desc' => 'Tampilan modern yang membangun kepercayaan sejak detik pertama visitor membuka halaman.'],
        ['icon' => 'ti-sitemap',          'title' => 'Struktur yang jelas', 'desc' => 'Setiap halaman punya tujuan. Visitor tahu harus ke mana selanjutnya tanpa kebingungan.'],
        ['icon' => 'ti-device-mobile',    'title' => 'Mobile-friendly',     'desc' => 'Tampil sempurna di HP — mayoritas visitor Anda membuka dari perangkat mobile.'],
        ['icon' => 'ti-speakerphone',     'title' => 'Siap untuk promosi',  'desc' => 'Struktur dan konten sudah dipersiapkan untuk mendukung iklan, Google, dan media sosial.'],
        ['icon' => 'ti-click',            'title' => 'CTA yang tepat',      'desc' => 'Tombol dan alur yang mengarahkan visitor untuk menghubungi, memesan, atau mengisi form.'],
        ['icon' => 'ti-trending-up',      'title' => 'Mudah dikembangkan',  'desc' => 'Dibangun dengan struktur yang rapi sehingga mudah ditambah fitur atau halaman baru ke depannya.'],
    ];

    $steps = [
        ['icon' => 'ti-message-circle-search', 'title' => 'Diskusi kebutuhan',      'desc' => 'Kami pahami tujuan bisnis, target pengunjung, dan ekspektasi Anda sebelum mulai.'],
        ['icon' => 'ti-schema',                'title' => 'Struktur & wireframe',    'desc' => 'Rancangan alur halaman dibuat dulu agar struktur dan CTA sudah tepat sebelum desain dimulai.'],
        ['icon' => 'ti-code',                  'title' => 'Desain & development',    'desc' => 'Website dibangun sesuai wireframe yang sudah disetujui, dengan perhatian ke detail visual dan performa.'],
        ['icon' => 'ti-refresh',               'title' => 'Revisi & finalisasi',     'desc' => 'Anda bisa minta penyesuaian sebelum launching. Kami pastikan hasilnya sesuai ekspektasi.'],
        ['icon' => 'ti-rocket',                'title' => 'Launching',               'desc' => 'Website live dan siap dipakai untuk promosi, iklan, atau dibagikan ke calon pelanggan.'],
    ];

    $heroWa   = $waLink('Halo, saya ingin konsultasi gratis untuk jasa pembuatan website bisnis.');
    $quoteWa  = $waLink('Halo, saya ingin tanya harga jasa pembuatan website untuk bisnis saya.');
    $finalWa  = $waLink('Halo, saya ingin mulai buat website bisnis saya. Bisa konsultasi dulu?');

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
    .btn-wa {
        background: #25d366;
        border-color: #25d366;
        color: #fff;
        font-weight: 600;
    }
    .btn-wa:hover, .btn-wa:focus {
        background: #1ebe5d;
        border-color: #1ebe5d;
        color: #fff;
    }
    .btn-wa-outline {
        border: 2px solid #25d366;
        color: #16a34a;
        font-weight: 600;
        background: transparent;
    }
    .btn-wa-outline:hover, .btn-wa-outline:focus {
        background: #25d366;
        color: #fff;
    }
    .wa-float {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 1050;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background: #25d366;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        box-shadow: 0 4px 20px rgba(37,211,102,.45);
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .wa-float:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 28px rgba(37,211,102,.55);
        color: #fff;
    }
    .website-lp-hero-visual img {
        display: block;
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    /* ── Responsive overrides ───────────────────────────── */
    @media (max-width: 991.98px) {
        .website-lp-hero-visual {
            max-width: 32rem;
            margin: 0 auto;
        }
    }
    @media (max-width: 767.98px) {
        /* Hero trust row: wrap on small screens */
        .website-lp-trust-row {
            gap: .35rem .6rem !important;
        }
        /* Process section: CTA button full width on mobile */
        .website-lp-process-cta {
            display: block;
            width: 100%;
            text-align: center;
        }
        /* Section padding tighter on mobile */
        .website-lp-lead {
            font-size: .975rem;
        }
    }
    @media (max-width: 575.98px) {
        /* Hero: reduce gap between heading and image */
        .website-lp-subtitle {
            max-width: 100%;
            font-size: 1rem;
        }
        /* Layanan & hasil cards: match height nicer */
        .landing-panel.h-100 {
            min-height: 0;
        }
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
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                    <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp
                </a>
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
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-wa">
                        <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp Gratis
                    </a>
                    <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-outline-dark">Tanya Harga</a>
                </div>
                <div class="website-lp-trust-row d-flex flex-wrap align-items-center gap-3 mb-4 text-muted" style="font-size:.875rem;">
                    <span><i class="ti ti-check text-success me-1"></i>Gratis konsultasi</span>
                    <span><i class="ti ti-clock text-success me-1"></i>Respon cepat</span>
                    <span><i class="ti ti-lock-open text-success me-1"></i>Tanpa komitmen</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Company Profile</span>
                    <span class="landing-pill">Landing Page</span>
                    <span class="landing-pill">Website Katalog</span>
                    <span class="landing-pill">Website Custom</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="website-lp-hero-visual">
                    <img src="{{ $heroImage }}" alt="Ilustrasi jasa pembuatan website bisnis" loading="eager">
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
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-md-6">
                <div class="landing-eyebrow mb-2">Solusi</div>
                <h2 class="landing-section-title mb-3">Kami mulai dari tujuan bisnis, bukan dari desain.</h2>
                <p class="landing-subtext website-lp-lead mb-4">Sebelum satu baris kode ditulis, kami pastikan dulu: apa yang ingin dicapai website ini? Dari situ baru kami rancang struktur, alur, dan tampilan yang mendukung tujuan tersebut.</p>
                <div class="landing-checklist">
                    <div><i class="ti ti-check text-success"></i> Tujuan website jelas sejak awal</div>
                    <div><i class="ti ti-check text-success"></i> Alur halaman yang mengarahkan visitor ke action</div>
                    <div><i class="ti ti-check text-success"></i> Tampilan yang membangun kepercayaan</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="landing-panel p-4 p-lg-5 website-lp-note">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Pendekatan kami</div>
                    <h3 class="h4 mb-3">Visitor yang bingung tidak akan menghubungi Anda.</h3>
                    <p class="website-lp-text mb-0">Banyak website gagal bukan karena tampilan kurang bagus, tapi karena visitor tidak tahu harus melakukan apa setelah masuk. Kami pastikan setiap halaman punya arah yang jelas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="layanan" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Layanan</div>
            <h2 class="landing-section-title">Jenis website yang kami kerjakan untuk bisnis Anda.</h2>
            <p class="landing-subtext website-lp-lead mx-auto">Setiap jenis website punya tujuan berbeda. Kami bantu tentukan mana yang paling cocok untuk bisnis Anda.</p>
        </div>
        <div class="row g-4 mb-5">
            @foreach($services as $service)
                <div class="col-md-6 col-lg-3">
                    <div class="landing-panel p-4 h-100">
                        <div class="website-lp-icon mb-3"><i class="ti {{ $service['icon'] }}" style="font-size:1.2rem;"></i></div>
                        <div class="fw-semibold mb-2">{{ $service['title'] }}</div>
                        <div class="website-lp-text" style="font-size:.9rem;">{{ $service['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                <i class="ti ti-brand-whatsapp me-1"></i>Diskusikan Kebutuhan Website Saya
            </a>
            <div class="text-muted mt-2" style="font-size:.85rem;">Gratis konsultasi · Tanpa komitmen</div>
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
                        <span class="website-lp-icon mb-3 d-inline-flex"><i class="ti {{ $outcome['icon'] }}" style="font-size:1.15rem;"></i></span>
                        <div class="fw-semibold mb-1">{{ $outcome['title'] }}</div>
                        <div class="website-lp-text" style="font-size:.9rem;">{{ $outcome['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="proses" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-start">
            <div class="col-md-4">
                <div class="sticky-md-top" style="top:5rem;">
                    <div class="landing-eyebrow mb-2">Proses</div>
                    <h2 class="landing-section-title mb-3">Pengerjaan dibuat ringkas, jelas, dan terarah.</h2>
                    <p class="landing-subtext website-lp-lead mb-4">Dari diskusi kebutuhan sampai launching, setiap tahap dibuat supaya hasil akhir tetap relevan dengan tujuan bisnis Anda.</p>
                    <a href="{{ $quoteWa }}" target="_blank" rel="noopener" class="btn btn-wa website-lp-process-cta">
                        <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp Sekarang
                    </a>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row g-3">
                    @foreach($steps as $i => $step)
                        @php
                            $isLastOdd = $loop->last && ($loop->count % 2 !== 0);
                        @endphp
                        <div class="{{ $isLastOdd ? 'col-12' : 'col-sm-6' }}">
                            <div class="landing-panel p-4 h-100" style="border-left:3px solid #93c5fd; position:relative; overflow:hidden;">
                                {{-- Nomor dekoratif --}}
                                <span style="position:absolute;top:-.5rem;right:.75rem;font-size:4rem;font-weight:900;color:rgba(15,23,42,.05);line-height:1;user-select:none;">{{ $i + 1 }}</span>
                                {{-- Ikon --}}
                                <span class="website-lp-icon mb-3 d-inline-flex"><i class="ti {{ $step['icon'] }}" style="font-size:1.15rem;"></i></span>
                                {{-- Judul --}}
                                <div class="fw-bold mb-1" style="font-size:.95rem;">{{ $step['title'] }}</div>
                                {{-- Deskripsi --}}
                                <div class="text-muted" style="font-size:.85rem;line-height:1.6;">{{ $step['desc'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>


<section class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line);">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 website-lp-note" style="box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-md-7 col-lg-7">
                    <div class="landing-eyebrow mb-2">Mulai Sekarang</div>
                    <h2 class="landing-section-title mb-3">Sudah waktunya punya website yang bekerja untuk bisnis Anda.</h2>
                    <p class="website-lp-lead mb-4">Website yang bagus bukan soal tampilan saja — tapi soal seberapa jelas ia mengarahkan visitor untuk menghubungi Anda. Mari diskusikan kebutuhan Anda.</p>
                    <div class="landing-checklist mb-4">
                        <div><i class="ti ti-check text-success"></i> UMKM dan brand yang belum punya website</div>
                        <div><i class="ti ti-check text-success"></i> Bisnis yang ingin menghasilkan lebih banyak leads</div>
                        <div><i class="ti ti-check text-success"></i> Owner yang ingin terlihat lebih profesional di mata klien</div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                            <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp Gratis
                        </a>
                        <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-lg">Tanya Harga Dulu</a>
                    </div>
                    <div class="text-muted" style="font-size:.875rem;">Gratis konsultasi · Tanpa komitmen · Respon cepat</div>
                </div>
                <div class="col-md-5 col-lg-5">
                    <div class="p-4 rounded-4" style="background:#fff; border:1px solid rgba(15,23,42,.07);">
                        <div class="small text-uppercase fw-bold text-muted mb-3">Yang Anda dapatkan</div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <span class="website-lp-icon flex-shrink-0" style="width:2.2rem;height:2.2rem;border-radius:.6rem;"><i class="ti ti-phone-call" style="font-size:.95rem;"></i></span>
                                <div>
                                    <div class="fw-semibold mb-1" style="font-size:.9rem;">Konsultasi gratis</div>
                                    <div class="text-muted" style="font-size:.825rem;">Diskusikan kebutuhan dulu sebelum memutuskan.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-3">
                                <span class="website-lp-icon flex-shrink-0" style="width:2.2rem;height:2.2rem;border-radius:.6rem;"><i class="ti ti-file-description" style="font-size:.95rem;"></i></span>
                                <div>
                                    <div class="fw-semibold mb-1" style="font-size:.9rem;">Penawaran transparan</div>
                                    <div class="text-muted" style="font-size:.825rem;">Harga dan scope jelas sejak awal, tidak ada biaya tersembunyi.</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-3">
                                <span class="website-lp-icon flex-shrink-0" style="width:2.2rem;height:2.2rem;border-radius:.6rem;"><i class="ti ti-adjustments" style="font-size:.95rem;"></i></span>
                                <div>
                                    <div class="fw-semibold mb-1" style="font-size:.9rem;">Revisi sampai sesuai</div>
                                    <div class="text-muted" style="font-size:.825rem;">Ada sesi revisi sebelum website diluncurkan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Floating WhatsApp Button --}}
<a href="{{ $heroWa }}" target="_blank" rel="noopener" class="wa-float" title="Chat WhatsApp">
    <i class="ti ti-brand-whatsapp"></i>
</a>
@endsection
