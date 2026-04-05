@extends('layouts.landing')

@section('head_title', config('app.name') . ' Jasa Website & Aplikasi Bisnis')
@section('head_description', 'Kami membantu bisnis membangun website dan aplikasi yang profesional, mudah digunakan, mendukung operasional, dan siap membantu pertumbuhan bisnis.')

@php
    $whatsAppNumber = '6281222229815';
    $waLink = function (string $message) use ($whatsAppNumber): string {
        return 'https://wa.me/' . $whatsAppNumber . '?text=' . urlencode($message);
    };
    $canonicalUrl = route('landing.website-apps');
    $ogImage = asset('brand/logo-default.png');
    $seoKeywords = 'jasa website bisnis, jasa aplikasi bisnis, jasa pembuatan website, jasa pembuatan aplikasi, website company profile, landing page bisnis, aplikasi custom bisnis, sistem internal perusahaan, integrasi CRM WhatsApp, developer website bisnis';

    $problems = [
        'Tidak menghasilkan leads atau penjualan',
        'Hanya jadi brosur online',
        'Sulit di-update dan tidak terintegrasi',
        'Tidak membantu operasional bisnis',
        'Tidak punya sistem di baliknya',
    ];

    $solutionPoints = [
        'Mendukung operasional bisnis',
        'Mempermudah alur kerja tim',
        'Membantu menghasilkan leads atau transaksi',
        'Mudah digunakan dan scalable',
    ];

    $services = [
        [
            'icon' => 'ti-world-www',
            'title' => 'Website Bisnis',
            'items' => [
                'Company Profile',
                'Landing Page yang fokus konversi',
                'Website katalog produk atau jasa',
                'Website custom sesuai kebutuhan',
            ],
        ],
        [
            'icon' => 'ti-device-desktop-code',
            'title' => 'Aplikasi Bisnis',
            'items' => [
                'Sistem operasional internal',
                'Dashboard dan reporting',
                'Sistem manajemen data',
                'Aplikasi custom sesuai alur bisnis',
            ],
        ],
        [
            'icon' => 'ti-plug-connected',
            'title' => 'Integrasi Sistem',
            'items' => [
                'Integrasi ke WhatsApp atau CRM',
                'Integrasi data dan dashboard',
                'Integrasi dengan sistem akuntansi',
                'Alur data lebih rapi antar tools',
            ],
        ],
    ];

    $outcomes = [
        ['icon' => 'ti-checklist', 'text' => 'Website atau aplikasi yang sesuai kebutuhan bisnis'],
        ['icon' => 'ti-layout-grid', 'text' => 'Tampilan profesional dan user-friendly'],
        ['icon' => 'ti-bolt', 'text' => 'Struktur yang mendukung konversi lead atau transaksi'],
        ['icon' => 'ti-users-group', 'text' => 'Sistem yang benar-benar bisa digunakan oleh tim'],
        ['icon' => 'ti-arrows-up-right', 'text' => 'Solusi yang scalable untuk pertumbuhan bisnis'],
    ];

    $steps = [
        ['no' => '1', 'icon' => 'ti-search', 'title' => 'Discovery & Kebutuhan', 'text' => 'Memahami bisnis, target, dan kebutuhan sistem yang ingin Anda capai.'],
        ['no' => '2', 'icon' => 'ti-sitemap', 'title' => 'Perencanaan & Struktur', 'text' => 'Menyusun flow, halaman, integrasi, dan fitur yang paling relevan untuk bisnis Anda.'],
        ['no' => '3', 'icon' => 'ti-code', 'title' => 'Development', 'text' => 'Pembuatan website atau aplikasi dengan pendekatan yang terarah dan mudah dikembangkan.'],
        ['no' => '4', 'icon' => 'ti-bug-search', 'title' => 'Testing & Revisi', 'text' => 'Memastikan sistem berjalan optimal dan memperbaiki detail penting sebelum digunakan.'],
        ['no' => '5', 'icon' => 'ti-rocket', 'title' => 'Launching', 'text' => 'Website atau aplikasi siap dipakai sebagai aset digital bisnis Anda.'],
        ['no' => '6', 'icon' => 'ti-lifebuoy', 'title' => 'Support', 'text' => 'Pendampingan setelah launch untuk penyesuaian, perbaikan, dan pengembangan lanjutan.'],
    ];

    $results = [
        ['icon' => 'ti-badge-ad', 'text' => 'Bisnis Anda memiliki aset digital yang profesional'],
        ['icon' => 'ti-settings', 'text' => 'Proses bisnis lebih efisien'],
        ['icon' => 'ti-users-plus', 'text' => 'Lebih mudah mendapatkan leads atau customer'],
        ['icon' => 'ti-briefcase', 'text' => 'Operasional lebih terstruktur'],
        ['icon' => 'ti-chart-line', 'text' => 'Siap untuk scale'],
    ];

    $fitFor = [
        ['icon' => 'ti-browser', 'text' => 'Bisnis yang ingin punya website profesional'],
        ['icon' => 'ti-click', 'text' => 'Brand yang ingin meningkatkan konversi dari online'],
        ['icon' => 'ti-building-cog', 'text' => 'Perusahaan yang butuh sistem internal'],
        ['icon' => 'ti-layout-dashboard', 'text' => 'Owner yang ingin bisnis lebih terstruktur'],
    ];

    $faqs = [
        [
            'q' => 'Apakah bisa request custom?',
            'a' => 'Ya. Semua solusi kami disesuaikan dengan kebutuhan bisnis, alur kerja, dan target yang ingin Anda capai.',
        ],
        [
            'q' => 'Apakah bisa sekadar website sederhana?',
            'a' => 'Bisa, namun kami tetap sarankan struktur yang mendukung bisnis Anda agar website tidak berhenti di tampilan saja.',
        ],
        [
            'q' => 'Apakah ada support setelah selesai?',
            'a' => 'Ya. Kami menyediakan support dan pengembangan lanjutan setelah website atau aplikasi diluncurkan.',
        ],
    ];

    $heroWa = $waLink('Halo, saya ingin konsultasi gratis untuk kebutuhan website atau aplikasi bisnis saya.');
    $discussWa = $waLink('Halo, saya ingin diskusikan kebutuhan website atau aplikasi untuk bisnis saya.');
    $pricingWa = $waLink('Halo, saya ingin tanya estimasi biaya website atau aplikasi bisnis sesuai kebutuhan saya.');
    $finalWa = $waLink('Halo, saya ingin mulai bangun sistem digital untuk bisnis saya. Bisa konsultasi?');

    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(
            fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ],
            $faqs
        ),
    ];

    $serviceSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => 'Jasa Website & Aplikasi Bisnis',
        'description' => 'Jasa pembuatan website bisnis, landing page, company profile, aplikasi custom, dashboard, sistem internal, dan integrasi sistem untuk kebutuhan bisnis.',
        'serviceType' => [
            'Website Bisnis',
            'Landing Page',
            'Company Profile',
            'Aplikasi Bisnis',
            'Dashboard & Reporting',
            'Integrasi Sistem',
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
            [
                '@type' => 'Offer',
                'name' => 'Website Bisnis',
                'price' => '2500000',
                'priceCurrency' => 'IDR',
                'url' => $canonicalUrl,
            ],
            [
                '@type' => 'Offer',
                'name' => 'Aplikasi Bisnis',
                'price' => '5000000',
                'priceCurrency' => 'IDR',
                'url' => $canonicalUrl,
            ],
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
                'name' => 'Jasa Website & Aplikasi Bisnis',
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
<meta property="og:title" content="Jasa Website & Aplikasi Bisnis | Landing Page, Company Profile, Sistem Internal">
<meta property="og:description" content="Jasa pembuatan website bisnis, landing page, company profile, aplikasi custom, dashboard, dan integrasi sistem yang benar-benar mendukung operasional dan pertumbuhan bisnis.">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ config('app.name') }} - Jasa Website & Aplikasi Bisnis">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Jasa Website & Aplikasi Bisnis | Landing Page, Company Profile, Sistem Internal">
<meta name="twitter:description" content="Bangun website atau aplikasi yang bukan sekadar jadi, tapi membantu leads, operasional, dan pertumbuhan bisnis Anda.">
<meta name="twitter:image" content="{{ $ogImage }}">
<script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('head')
<style>
    .website-hero-title {
        font-size: clamp(2.35rem, 5vw, 4.2rem);
        line-height: 1.06;
        letter-spacing: -0.04em;
    }
    .website-hero-subtitle {
        font-size: clamp(1.04rem, 1.8vw, 1.24rem);
        line-height: 1.78;
        color: #475569;
        max-width: 45rem;
    }
    .website-lead {
        font-size: 1.06rem;
        line-height: 1.78;
        color: #475569;
    }
    .website-card-text {
        font-size: 1rem;
        line-height: 1.74;
        color: #475569;
    }
    .website-icon-badge {
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
    .website-module-badge {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #eff6ff;
        color: #2563eb;
        overflow: hidden;
    }
    .website-module-badge svg {
        width: 1.85rem;
        height: 1.85rem;
        display: block;
    }
    .website-soft-panel {
        background: linear-gradient(180deg, #fffdf8 0%, #fff7ed 100%);
        border: 1px solid rgba(249, 115, 22, .14);
    }
    .website-note-panel {
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
                <a href="#layanan" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-stack-2"></i><span>Layanan</span></a>
                <a href="#hasil" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-checklist"></i><span>Hasil</span></a>
                <a href="#proses" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-route"></i><span>Proses</span></a>
                <a href="#pricing" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-receipt-2"></i><span>Pricing</span></a>
                <a href="#faq" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-help-circle"></i><span>FAQ</span></a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Konsultasi Gratis Sekarang</a>
                <a href="{{ $discussWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm">Diskusikan Kebutuhan Anda</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<section class="landing-hero py-5 py-lg-6" style="background:radial-gradient(circle at top left, rgba(59,130,246,.16), transparent 32%), linear-gradient(180deg,#f8fbff 0%,#fff 54%,#f8fafc 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-device-desktop-analytics"></i> Website dan aplikasi untuk kebutuhan bisnis
                </div>
                <h1 class="landing-headline website-hero-title mb-4">
                    <span>Butuh website atau aplikasi</span>
                    <br>
                    untuk bisnis?
                </h1>
                <p class="landing-subtext website-hero-subtitle mb-4">
                    Bukan sekadar jadi, tapi harus menghasilkan. Kami bantu Anda membangun website atau aplikasi yang terlihat profesional, mendukung operasional, dan siap membantu pertumbuhan bisnis. Mulai dari company profile, landing page, hingga sistem aplikasi internal.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-dark">Konsultasi Gratis Sekarang</a>
                    <a href="{{ $discussWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-outline-dark">Diskusikan Kebutuhan Anda</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Website profesional</span>
                    <span class="landing-pill">Aplikasi custom</span>
                    <span class="landing-pill">Integrasi sistem</span>
                    <span class="landing-pill">Support setelah launch</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-uppercase fw-bold text-muted mb-2">Aset digital yang seharusnya bekerja untuk bisnis</div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff7ed; border:1px solid rgba(249,115,22,.12);">
                                <div class="fw-semibold mb-1">Sering terjadi</div>
                                <div class="website-card-text">Website hanya jadi brosur online dan tidak benar-benar membantu penjualan atau operasional.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#eff6ff; border:1px solid rgba(59,130,246,.12);">
                                <div class="fw-semibold mb-1">Yang kami bangun</div>
                                <div class="website-card-text">Website atau aplikasi yang dipakai bisnis setiap hari, mudah dikembangkan, dan siap mendukung scale.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4 website-soft-panel">
                                <div class="text-uppercase fw-bold small text-muted mb-2">Nilai utama</div>
                                <div class="h4 mb-2">Bukan sekadar tampilan, tapi fondasi sistem bisnis Anda.</div>
                                <div class="website-card-text mb-0">Project dirancang agar terlihat profesional di depan dan tetap kuat untuk dipakai dalam proses bisnis di belakang layar.</div>
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
            <h2 class="landing-section-title">Banyak bisnis sudah punya website, tapi belum memberi dampak nyata.</h2>
            <p class="landing-subtext website-lead mx-auto">Akhirnya website ada, tetapi tidak membantu leads, penjualan, atau operasional bisnis.</p>
        </div>
        <div class="landing-panel p-4 p-lg-5">
            <div class="row g-3">
                @foreach($problems as $problem)
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 rounded-4 p-3 h-100" style="background:#fff8ef;">
                            <span class="website-icon-badge"><i class="ti ti-alert-triangle" style="font-size:1.3rem;"></i></span>
                            <div class="website-card-text">{{ $problem }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <div class="fw-semibold mb-2">Website ada, tapi tidak memberikan dampak nyata.</div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Solusi</div>
                <h2 class="landing-section-title mb-3">Kami tidak hanya membuat website atau aplikasi.</h2>
                <p class="landing-subtext website-lead mb-3">Kami membantu Anda membangun sistem digital yang benar-benar digunakan dalam bisnis.</p>
                <p class="landing-subtext website-lead mb-4">Setiap project kami dirancang untuk mendukung operasional, mempermudah kerja tim, membantu konversi, dan tetap mudah dikembangkan saat bisnis bertumbuh.</p>
                <div class="landing-checklist">
                    @foreach($solutionPoints as $point)
                        <div><i class="ti ti-check text-success"></i> {{ $point }}</div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($modules as $module)
                        <div class="col-md-6">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="website-module-badge">
                                        @if(!empty($module['icon_svg']))
                                            {!! $module['icon_svg'] !!}
                                        @else
                                            <i class="ti ti-box" style="font-size:1.5rem;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold mb-1">{{ $module['name'] }}</div>
                                        <div class="website-card-text">{{ $module['description'] }}</div>
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

<section id="layanan" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Layanan Kami</div>
            <h2 class="landing-section-title">Layanan dirancang untuk website, aplikasi, dan integrasi sistem yang mendukung bisnis.</h2>
        </div>
        <div class="row g-4">
            @foreach($services as $service)
                <div class="col-lg-4">
                    <div class="landing-panel p-4 h-100">
                        <div class="website-icon-badge mb-3"><i class="ti {{ $service['icon'] }}" style="font-size:1.3rem;"></i></div>
                        <h3 class="h5 mb-3">{{ $service['title'] }}</h3>
                        <div class="d-grid gap-2">
                            @foreach($service['items'] as $item)
                                <div class="website-card-text"><i class="ti ti-check text-success"></i> {{ $item }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="hasil" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Apa yang Anda Dapatkan</div>
            <h2 class="landing-section-title">Bukan sekadar jadi, tapi berfungsi untuk bisnis Anda.</h2>
        </div>
        <div class="row g-4">
            @foreach($outcomes as $outcome)
                <div class="col-lg-4 col-md-6">
                    <div class="landing-panel p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <span class="website-icon-badge"><i class="ti {{ $outcome['icon'] }}" style="font-size:1.2rem;"></i></span>
                            <div class="website-card-text">{{ $outcome['text'] }}</div>
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
                <div class="landing-eyebrow mb-2">Proses Kerja</div>
                <h2 class="landing-section-title mb-3">Kami memastikan setiap project berjalan terarah.</h2>
                <p class="landing-subtext website-lead mb-4">Alurnya jelas dari tahap discovery sampai support setelah launch, supaya hasil akhir tetap rapi dan relevan dengan kebutuhan bisnis.</p>
                <a href="{{ $discussWa }}" target="_blank" rel="noopener" class="btn btn-dark">Diskusikan Kebutuhan Anda</a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($steps as $step)
                        <div class="col-md-6">
                            <div class="landing-panel p-4 h-100" style="border-left:4px solid #60a5fa;">
                                <div class="small text-uppercase fw-bold text-muted mb-2">Tahap {{ $step['no'] }}</div>
                                <div class="website-icon-badge mb-3"><i class="ti {{ $step['icon'] }}" style="font-size:1.2rem;"></i></div>
                                <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                                <p class="website-card-text mb-0">{{ $step['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#fdfaf5; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Hasil yang Diharapkan</div>
                <h2 class="landing-section-title mb-3">Setelah project selesai, bisnis Anda punya aset digital yang siap bekerja.</h2>
                <p class="landing-subtext website-lead mb-4">Website atau aplikasi tidak berhenti di tampilan saja, tetapi memberi fondasi yang lebih rapi untuk pertumbuhan bisnis berikutnya.</p>
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark">Konsultasi Gratis Sekarang</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($results as $result)
                        <div class="col-12">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="website-icon-badge"><i class="ti {{ $result['icon'] }}" style="font-size:1.2rem;"></i></span>
                                    <div class="website-card-text">{{ $result['text'] }}</div>
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
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Siapa yang Cocok</div>
                <h2 class="landing-section-title mb-3">Cocok untuk bisnis yang ingin aset digitalnya benar-benar membantu pertumbuhan.</h2>
                <p class="landing-subtext website-lead mb-4">Baik untuk kebutuhan website profesional, sistem internal, maupun project custom yang membutuhkan fondasi digital yang lebih serius.</p>
                <div class="landing-checklist">
                    @foreach($fitFor as $item)
                        <div><i class="ti {{ $item['icon'] }} text-success"></i> {{ $item['text'] }}</div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5 h-100 website-note-panel">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Project yang sehat</div>
                    <h3 class="h4 mb-3">Bukan hanya cantik di depan, tapi juga rapi untuk tim di belakang layar.</h3>
                    <p class="website-card-text mb-4">Kami menjaga agar struktur project tetap enak dipakai, enak dikembangkan, dan tetap relevan dengan kebutuhan operasional bisnis Anda.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="landing-pill">Lead</span>
                        <span class="landing-pill">Operasional</span>
                        <span class="landing-pill">Integrasi</span>
                        <span class="landing-pill">Scalable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="pricing" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Pricing</div>
            <h2 class="landing-section-title">Estimasi awal untuk kebutuhan umum.</h2>
            <p class="landing-subtext website-lead mx-auto">Harga akhir tetap menyesuaikan kompleksitas, alur kerja, integrasi, dan cakupan fitur yang Anda butuhkan.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4">
                <div class="landing-panel p-4 p-lg-5 text-center h-100" style="border:1px solid rgba(59,130,246,.14); box-shadow:0 24px 60px rgba(15,23,42,.08);">
                    <div class="website-icon-badge mx-auto mb-3"><i class="ti ti-browser" style="font-size:1.25rem;"></i></div>
                    <div class="small text-uppercase fw-bold text-muted mb-2">Website</div>
                    <div class="display-6 fw-bold mb-2" style="color:#2563eb;">Rp2.500.000</div>
                    <div class="website-card-text">Mulai dari company profile, landing page, atau website bisnis yang fokus hasil.</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="landing-panel p-4 p-lg-5 text-center h-100" style="border:1px solid rgba(249,115,22,.14); box-shadow:0 24px 60px rgba(15,23,42,.08);">
                    <div class="website-icon-badge mx-auto mb-3"><i class="ti ti-device-desktop-code" style="font-size:1.25rem;"></i></div>
                    <div class="small text-uppercase fw-bold text-muted mb-2">Aplikasi</div>
                    <div class="display-6 fw-bold mb-2" style="color:#ea580c;">Rp5.000.000</div>
                    <div class="website-card-text">Mulai dari dashboard, sistem data, aplikasi internal, atau alur custom untuk bisnis.</div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="{{ $pricingWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Tanya Estimasi Project</a>
        </div>
    </div>
</section>

<section id="faq" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan yang paling sering muncul sebelum project dimulai.</h2>
        </div>
        <div class="row g-4">
            @foreach($faqs as $faq)
                <div class="col-lg-4">
                    <div class="landing-panel p-4 h-100">
                        <h3 class="h5 mb-2">{{ $faq['q'] }}</h3>
                        <p class="website-card-text mb-0">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center website-note-panel" style="box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <div class="landing-eyebrow mb-2">Bangun Dengan Benar</div>
            <h2 class="landing-section-title mb-3">Website atau aplikasi bukan sekadar tampilan, tapi fondasi sistem bisnis Anda.</h2>
            <p class="landing-subtext website-lead mx-auto mb-4" style="max-width:760px;">Pastikan Anda membangunnya dengan struktur yang tepat, pengalaman yang enak dipakai, dan sistem yang benar-benar mendukung bisnis.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Konsultasi Gratis Sekarang</a>
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-lg">Mulai Bangun Sistem Digital Anda</a>
            </div>
        </div>
    </div>
</section>
@endsection
