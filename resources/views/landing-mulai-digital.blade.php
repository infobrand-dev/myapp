@extends('layouts.landing')

@section('head_title', config('app.name') . ' Mulai Digital - Rapikan Operasional dan Keuangan Bisnis Anda')
@section('head_description', 'Kami bantu rapikan operasional dan keuangan bisnis menjadi sistem digital yang terstruktur, mudah dipakai tim, dan bisa dipantau owner setiap hari dengan pendampingan hingga 1 tahun penuh.')

@php
    $whatsAppNumber = '6281222229815';
    $waLink = function (string $message) use ($whatsAppNumber): string {
        return 'https://wa.me/' . $whatsAppNumber . '?text=' . urlencode($message);
    };

    $problems = [
        'Data tersebar di chat, Excel, dan catatan manual',
        'Sulit mengetahui kondisi bisnis secara real-time',
        'Laporan lambat dan tidak konsisten',
        'Operasional bergantung pada orang tertentu',
        'Tidak yakin bisnis benar-benar untung atau tidak',
    ];

    $outcomes = [
        ['icon' => 'ti-database', 'text' => 'Data bisnis yang terpusat dan tidak tercecer'],
        ['icon' => 'ti-settings-cog', 'text' => 'Sistem operasional yang lebih rapi dan tidak bergantung pada individu'],
        ['icon' => 'ti-layout-dashboard', 'text' => 'Dashboard bisnis yang mudah dipantau oleh owner'],
        ['icon' => 'ti-calculator', 'text' => 'Sistem akuntansi online untuk pencatatan keuangan harian'],
        ['icon' => 'ti-report-analytics', 'text' => 'Laporan keuangan yang bisa dibaca tanpa harus paham akuntansi'],
        ['icon' => 'ti-cash', 'text' => 'Kontrol yang lebih jelas terhadap pemasukan, pengeluaran, dan profit'],
    ];

    $steps = [
        ['no' => '1', 'icon' => 'ti-search', 'title' => 'Audit Proses', 'text' => 'Memahami bagaimana bisnis Anda berjalan saat ini dan titik macet yang paling sering menghambat.'],
        ['no' => '2', 'icon' => 'ti-route-square', 'title' => 'Mapping & Perbaikan Alur', 'text' => 'Menyusun ulang proses agar lebih efisien, lebih jelas, dan lebih mudah dijalankan tim.'],
        ['no' => '3', 'icon' => 'ti-database-import', 'title' => 'Migrasi & Perapihan Data', 'text' => 'Memilih, membersihkan, dan merapikan data yang benar-benar penting untuk operasional.'],
        ['no' => '4', 'icon' => 'ti-adjustments-horizontal', 'title' => 'Setup Sistem', 'text' => 'Mengimplementasikan sistem operasional dan akuntansi online sesuai kebutuhan bisnis Anda.'],
        ['no' => '5', 'icon' => 'ti-users-group', 'title' => 'Onboarding Tim', 'text' => 'Membantu tim memahami alur baru dan mulai menggunakan sistem untuk pekerjaan harian.'],
        ['no' => '6', 'icon' => 'ti-sparkles', 'title' => 'Evaluasi & Penyempurnaan', 'text' => 'Memastikan sistem berjalan, dipakai tim, dan terus diperbaiki sesuai kebutuhan lapangan.'],
    ];

    $results = [
        ['icon' => 'ti-eye', 'text' => 'Anda bisa melihat kondisi bisnis setiap hari'],
        ['icon' => 'ti-wallet', 'text' => 'Pemasukan dan pengeluaran tercatat dengan jelas'],
        ['icon' => 'ti-message-off', 'text' => 'Tidak perlu lagi rekap manual atau cek chat satu per satu'],
        ['icon' => 'ti-building-factory-2', 'text' => 'Tim bekerja dengan sistem yang rapi'],
        ['icon' => 'ti-target-arrow', 'text' => 'Keputusan bisnis bisa diambil lebih cepat dan tepat'],
    ];

    $fitFor = [
        ['icon' => 'ti-briefcase-2', 'text' => 'Bisnis yang sudah berjalan tapi operasional masih manual'],
        ['icon' => 'ti-chart-arcs', 'text' => 'Owner yang ingin bisnis lebih rapi dan scalable'],
        ['icon' => 'ti-users', 'text' => 'Tim yang mulai kewalahan dengan data dan proses'],
        ['icon' => 'ti-building-community', 'text' => 'Perusahaan yang ingin digitalisasi tanpa chaos'],
    ];

    $faqs = [
        [
            'q' => 'Apakah saya harus paham teknologi?',
            'a' => 'Tidak. Kami bantu dari nol hingga sistem bisa digunakan oleh owner maupun tim.',
        ],
        [
            'q' => 'Apakah semua data lama harus dibuang?',
            'a' => 'Tidak. Data akan dipilih, dirapikan, lalu dipakai kembali sesuai kebutuhan operasional dan keuangan.',
        ],
        [
            'q' => 'Berapa lama prosesnya?',
            'a' => 'Tergantung kompleksitas bisnis, namun implementasi dijalankan bertahap agar tetap realistis dan tidak mengganggu operasional.',
        ],
        [
            'q' => 'Apakah tim saya akan kesulitan adaptasi?',
            'a' => 'Kami bantu onboarding dan pendampingan agar tim bisa langsung memakai sistem untuk pekerjaan harian yang paling penting.',
        ],
        [
            'q' => 'Apakah saya akan ditinggalkan setelah setup?',
            'a' => 'Tidak. Anda akan didampingi hingga 1 tahun penuh agar sistem benar-benar berjalan dan menjadi bagian dari operasional bisnis Anda.',
        ],
    ];

    $heroWa = $waLink('Halo, saya ingin konsultasi gratis untuk merapikan operasional dan keuangan bisnis saya menjadi sistem digital yang terkontrol.');
    $processWa = $waLink('Halo, saya ingin lihat alur pendampingan Mulai Digital dan diskusi audit proses bisnis saya.');
    $pricingWa = $waLink('Halo, saya ingin konsultasi paket Mulai Digital. Bisa dibantu estimasi sesuai kompleksitas bisnis saya?');
    $finalWa = $waLink('Halo, saya ingin mulai merapikan sistem bisnis saya. Bisa jadwalkan konsultasi gratis?');
@endphp

@push('head')
<style>
    .digital-hero-title {
        font-size: clamp(2.35rem, 5vw, 4.35rem);
        line-height: 1.06;
        letter-spacing: -0.04em;
    }
    .digital-hero-subtitle {
        font-size: clamp(1.05rem, 1.8vw, 1.3rem);
        line-height: 1.75;
        color: #475569;
        max-width: 44rem;
    }
    .digital-lead {
        font-size: 1.075rem;
        line-height: 1.8;
        color: #475569;
    }
    .digital-card-text {
        font-size: 1rem;
        line-height: 1.75;
        color: #475569;
    }
    .digital-icon-badge {
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
    .digital-module-badge {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #2563eb;
        overflow: hidden;
    }
    .digital-module-badge svg {
        width: 1.5rem;
        height: 1.5rem;
        display: block;
    }
    .digital-soft-panel {
        background: linear-gradient(180deg, #fffdf8 0%, #fff7ed 100%);
        border: 1px solid rgba(249, 115, 22, .14);
    }
    .digital-note-panel {
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
                <a href="#solusi" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-bulb"></i><span>Solusi</span></a>
                <a href="#hasil" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-checklist"></i><span>Hasil</span></a>
                <a href="#proses" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-route"></i><span>Proses</span></a>
                <a href="#pricing" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-receipt-2"></i><span>Pricing</span></a>
                <a href="#faq" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-help-circle"></i><span>FAQ</span></a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Konsultasi Gratis Sekarang</a>
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm">Mulai Rapikan Sistem</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<section class="landing-hero py-5 py-lg-6" style="background:radial-gradient(circle at top left, rgba(249,115,22,.18), transparent 34%), linear-gradient(180deg,#fff7ed 0%,#fff 54%,#f8fafc 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-building-store"></i> Program pendampingan operasional dan keuangan bisnis
                </div>
                <h1 class="landing-headline digital-hero-title mb-4">
                    <span>Operasional bisnis masih berantakan?</span>
                    <br>
                    Kami bantu rapikan.
                </h1>
                <p class="landing-subtext digital-hero-subtitle mb-4">
                    Kami bantu mengubah data tercecer dan proses manual menjadi sistem operasional dan keuangan yang rapi, mudah dipakai tim, dan bisa Anda pantau setiap hari. Bukan sekadar pasang sistem, tapi kami pastikan benar-benar berjalan.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-lg btn-dark">Konsultasi Gratis Sekarang</a>
                    <a href="#proses" class="btn btn-lg btn-outline-dark">Lihat Alur Pendampingan</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Operasional lebih rapi</span>
                    <span class="landing-pill">Keuangan lebih jelas</span>
                    <span class="landing-pill">Dashboard owner</span>
                    <span class="landing-pill">Pendampingan 1 tahun</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-uppercase fw-bold text-muted mb-2">Sebelum dan sesudah sistem dibenahi</div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff7ed; border:1px solid rgba(249,115,22,.12);">
                                <div class="fw-semibold mb-1">Sebelum</div>
                                <div class="small text-muted">Data tercecer, laporan lambat, owner sulit memantau, dan tim bergantung pada orang tertentu.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#eff6ff; border:1px solid rgba(29,78,216,.12);">
                                <div class="fw-semibold mb-1">Sesudah</div>
                                <div class="small text-muted">Operasional dan keuangan lebih tertata, dashboard lebih mudah dibaca, dan keputusan lebih cepat diambil.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4 digital-soft-panel">
                                <div class="text-uppercase fw-bold small text-muted mb-2">Nilai utama program</div>
                                <div class="h4 mb-2">Bukan hanya digital, tapi bisnis Anda benar-benar terkendali.</div>
                                <div class="digital-card-text mb-0">Kami bantu dari audit, perapihan alur, setup sistem, onboarding, sampai evaluasi supaya perubahan ini tidak berhenti di tahap implementasi.</div>
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
            <div class="landing-eyebrow mb-2">Masalah Utama</div>
            <h2 class="landing-section-title">Banyak bisnis sebenarnya siap tumbuh, tapi operasional dan keuangannya belum siap menopang.</h2>
            <p class="landing-subtext digital-lead mx-auto">Jika saat ini Anda mengalami kondisi di bawah ini, biasanya yang kurang bukan kerja keras, tapi sistem yang tepat.</p>
        </div>
        <div class="landing-panel p-4 p-lg-5">
            <div class="row g-3">
                @foreach($problems as $problem)
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3 rounded-4 p-3 h-100" style="background:#fff8ef;">
                            <span class="digital-icon-badge"><i class="ti ti-alert-triangle" style="font-size:1.3rem;"></i></span>
                            <div class="digital-card-text">{{ $problem }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <div class="fw-semibold mb-2">Ini bukan masalah kerja keras.</div>
                <div class="text-muted">Ini masalah belum punya sistem yang tepat.</div>
            </div>
        </div>
    </div>
</section>

<section id="solusi" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Solusi</div>
                <h2 class="landing-section-title mb-3">Kami bantu membangun sistem yang benar-benar dipakai tim.</h2>
                <p class="landing-subtext digital-lead mb-3">Bukan hanya mulai digital, tapi membangun operasional dan keuangan yang rapi, terstruktur, dan mudah dijalankan setiap hari.</p>
                <p class="landing-subtext digital-lead mb-4">Pendekatan kami bertahap dan realistis: tidak langsung lempar tools, tidak memaksa perubahan drastis, dan disesuaikan dengan kondisi bisnis Anda saat ini.</p>
                <div class="landing-checklist mb-4">
                    <div><i class="ti ti-check text-success"></i> Tidak langsung lempar tools</div>
                    <div><i class="ti ti-check text-success"></i> Tidak memaksa perubahan drastis</div>
                    <div><i class="ti ti-check text-success"></i> Disesuaikan dengan kondisi bisnis Anda saat ini</div>
                </div>
                <div class="landing-panel p-4 digital-note-panel">
                    <div class="fw-semibold mb-2">Pendampingan hingga 1 tahun penuh</div>
                    <div class="digital-card-text">Kami memastikan sistem benar-benar digunakan oleh tim dan menjadi bagian dari operasional harian, bukan hanya sekadar dipasang.</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($modules as $module)
                        <div class="col-md-6">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="digital-module-badge">
                                        @if(!empty($module['icon_svg']))
                                            {!! $module['icon_svg'] !!}
                                        @else
                                            <i class="ti ti-box" style="font-size:1.35rem;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold mb-1">{{ $module['name'] }}</div>
                                        <div class="digital-card-text">{{ $module['description'] }}</div>
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

<section id="hasil" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Apa yang Anda Dapatkan</div>
            <h2 class="landing-section-title">Setelah program ini berjalan, bisnis Anda akan jauh lebih mudah dikendalikan.</h2>
        </div>
        <div class="row g-4">
            @foreach($outcomes as $outcome)
                <div class="col-lg-4 col-md-6">
                    <div class="landing-panel p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <span class="digital-icon-badge"><i class="ti {{ $outcome['icon'] }}" style="font-size:1.25rem;"></i></span>
                            <div class="digital-card-text">{{ $outcome['text'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="col-12">
                <div class="landing-panel p-4 p-lg-5" style="background:#fff7ed; border:1px solid rgba(249,115,22,.14);">
                    <div class="h5 mb-2">Yang membedakan: pendampingan hingga 1 tahun penuh</div>
                    <p class="digital-card-text mb-0">Kami memastikan sistem benar-benar digunakan oleh tim dan menjadi bagian dari operasional harian, bukan berhenti di tahap setup.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="proses" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4">
                <div class="landing-eyebrow mb-2">Alur Pendampingan</div>
                <h2 class="landing-section-title mb-3">Kami bantu Anda tahap demi tahap, bukan langsung lompat ke sistem.</h2>
                <p class="landing-subtext digital-lead mb-4">Setiap tahap dibuat agar bisnis tetap bisa berjalan sambil sistem pelan-pelan dibenahi dan dipakai tim.</p>
                <a href="{{ $processWa }}" target="_blank" rel="noopener" class="btn btn-dark">Lihat Alur Pendampingan</a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($steps as $step)
                        <div class="col-md-6">
                            <div class="landing-panel p-4 h-100" style="border-left:4px solid #f59e0b;">
                                <div class="small text-uppercase fw-bold text-muted mb-2">Tahap {{ $step['no'] }}</div>
                                <div class="digital-icon-badge mb-3"><i class="ti {{ $step['icon'] }}" style="font-size:1.25rem;"></i></div>
                                <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                                <p class="digital-card-text mb-0">{{ $step['text'] }}</p>
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
                <div class="landing-eyebrow mb-2">Hasil Akhir</div>
                <h2 class="landing-section-title mb-3">Bayangkan kondisi bisnis yang lebih mudah Anda lihat, baca, dan kendalikan setiap hari.</h2>
                <p class="landing-subtext digital-lead mb-4">Bukan sekadar digital, tapi bisnis Anda benar-benar terkendali dan keputusan bisa diambil dengan dasar yang lebih jelas.</p>
                <a href="{{ $heroWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark">Konsultasi Gratis Sekarang</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($results as $result)
                        <div class="col-12">
                            <div class="landing-panel p-3 h-100">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="digital-icon-badge"><i class="ti {{ $result['icon'] }}" style="font-size:1.2rem;"></i></span>
                                    <div class="digital-card-text">{{ $result['text'] }}</div>
                                </div>
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
                <div class="landing-eyebrow mb-2">Siapa yang Cocok</div>
                <h2 class="landing-section-title mb-3">Program ini cocok untuk bisnis yang sudah berjalan, tetapi sistemnya belum menopang pertumbuhan.</h2>
                <p class="landing-subtext digital-lead mb-4">Kalau Anda ingin digitalisasi yang realistis tanpa membuat tim chaos, program ini dirancang untuk kondisi seperti itu.</p>
                <div class="landing-checklist">
                    @foreach($fitFor as $item)
                        <div><i class="ti {{ $item['icon'] }} text-success"></i> {{ $item['text'] }}</div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5 h-100" style="background:linear-gradient(180deg,#fff 0%,#eff6ff 100%);">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Kontrol yang lebih jelas</div>
                    <h3 class="h4 mb-3">Owner bisa memantau bisnis tanpa menunggu rekap manual.</h3>
                    <p class="digital-card-text mb-4">Pemasukan, pengeluaran, profit, dan aktivitas operasional bisa dibaca lebih mudah karena sistemnya memang dirancang untuk dipakai sehari-hari.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="landing-pill">Operasional</span>
                        <span class="landing-pill">Keuangan</span>
                        <span class="landing-pill">Dashboard</span>
                        <span class="landing-pill">Pendampingan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="pricing" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Pricing</div>
            <h2 class="landing-section-title">Paket Mulai Digital</h2>
            <p class="landing-subtext mx-auto">Mulai dari Rp2.500.000 dan menyesuaikan kompleksitas bisnis Anda.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="landing-panel p-4 p-lg-5 text-center" style="border:1px solid rgba(249,115,22,.16); box-shadow:0 24px 60px rgba(15,23,42,.08);">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Mulai dari</div>
                    <div class="display-5 fw-bold mb-2" style="color:#ea580c;">Rp2.500.000</div>
                    <div class="text-muted mb-4">Harga menyesuaikan kompleksitas bisnis</div>
                    <div class="row g-3 text-start mb-4">
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Audit proses bisnis</div></div>
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Mapping operasional</div></div>
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Setup sistem digital</div></div>
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Setup sistem akuntansi online</div></div>
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Onboarding tim</div></div>
                        <div class="col-md-6"><div class="digital-card-text"><i class="ti ti-check text-success"></i> Pendampingan hingga 1 tahun penuh</div></div>
                    </div>
                    <a href="{{ $pricingWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Konsultasi Gratis Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan yang biasanya muncul sebelum memulai program ini.</h2>
        </div>
        <div class="row g-4">
            @foreach($faqs as $faq)
                <div class="col-lg-6">
                    <div class="landing-panel p-4 h-100">
                        <h3 class="h5 mb-2">{{ $faq['q'] }}</h3>
                        <p class="digital-card-text mb-0">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center digital-note-panel" style="box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <div class="landing-eyebrow mb-2">Saatnya Rapikan Sistem Bisnis</div>
            <h2 class="landing-section-title mb-3">Banyak bisnis tidak berkembang bukan karena tidak laku, tapi karena tidak punya sistem dan kontrol yang jelas.</h2>
            <p class="landing-subtext digital-lead mx-auto mb-4" style="max-width:760px;">Kami bantu Anda membangun sistem operasional dan keuangan yang rapi, terukur, dan siap untuk berkembang.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg">Konsultasi Gratis Sekarang</a>
                <a href="{{ $finalWa }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-lg">Mulai Rapikan Sistem Bisnis Anda</a>
            </div>
        </div>
    </div>
</section>
@endsection
