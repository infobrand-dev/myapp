@extends('layouts.landing')

@section('head_title', config('app.name') . ' Commerce — Terima Order, Proses Pengiriman, Jalankan Affiliate dari Satu Workspace')
@section('head_description', 'Order makin ramai tapi operasional masih berantakan? Commerce merapikan storefront, order, pembayaran, pengiriman, fulfillment, dan affiliate dalam satu workspace yang terhubung.')

@push('head')
<style>
    .commerce-hero-title {
        font-size: clamp(2.35rem, 5vw, 4.2rem);
        line-height: 1.07;
        letter-spacing: -0.04em;
    }
    .commerce-hero-subtitle {
        font-size: clamp(1.05rem, 1.8vw, 1.28rem);
        line-height: 1.75;
        color: #475569;
        max-width: 44rem;
    }
    .commerce-lead {
        font-size: 1.075rem;
        line-height: 1.8;
        color: #475569;
    }
    .commerce-card-text {
        font-size: 1rem;
        line-height: 1.75;
        color: #475569;
    }
    .commerce-icon-badge {
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
    .commerce-stat-number {
        font-size: 2.25rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1;
        color: #0f172a;
    }
    .commerce-flow-step {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    .commerce-flow-num {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 999px;
        background: #ea580c;
        color: #fff;
        font-size: 0.875rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .commerce-img-placeholder {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 60%, #fed7aa 100%);
        border: 2px dashed #f97316;
        border-radius: 1.25rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #ea580c;
        text-align: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .commerce-module-badge {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fff7ed;
        color: #ea580c;
        overflow: hidden;
    }
    .commerce-module-badge svg {
        width: 1.85rem;
        height: 1.85rem;
        display: block;
    }
    .commerce-testimonial-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 999px;
        overflow: hidden;
        background: #ffedd5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ea580c;
        flex-shrink: 0;
    }
    .commerce-faq-item {
        border-radius: 1rem;
        border: 1px solid var(--landing-line);
        background: rgba(255,255,255,0.82);
    }
    .commerce-faq-item summary {
        list-style: none;
        cursor: pointer;
        padding: 1.1rem 1.25rem;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .commerce-faq-item summary::-webkit-details-marker { display: none; }
    .commerce-faq-item summary::after {
        content: '\ea6e';
        font-family: 'tabler-icons';
        font-size: 1.15rem;
        color: #ea580c;
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .commerce-faq-item[open] summary::after { transform: rotate(180deg); }
    .commerce-faq-body {
        padding: 0 1.25rem 1.1rem;
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.75;
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
                <a href="#fitur" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-sparkles"></i><span>Fitur</span></a>
                <a href="#cara-kerja" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-route"></i><span>Cara Kerja</span></a>
                <a href="#pricing" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-receipt-2"></i><span>Harga</span></a>
                <a href="#faq" class="landing-nav-link d-inline-flex align-items-center gap-2"><i class="ti ti-help-circle"></i><span>FAQ</span></a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-dark btn-sm">Daftar Sekarang</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
@php
    $plans = collect($publicPlans ?? []);
@endphp

{{-- ═══════════════════════════════════════════════════════════
     SECTION 1 — HERO
     ═══════════════════════════════════════════════════════════ --}}
<section class="landing-hero py-5 py-lg-6" style="background:radial-gradient(circle at top left,rgba(249,115,22,.22),transparent 36%), linear-gradient(160deg,#fff7ed 0%,#fffbeb 52%,#f8fafc 100%); border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-shopping-bag"></i> Commerce — Platform Order & Operasional
                </div>
                <h1 class="landing-headline commerce-hero-title mb-4">
                    <span>Order makin ramai,</span><br>operasional Anda masih bisa ikutinya?
                </h1>
                <p class="landing-subtext commerce-hero-subtitle mb-4">
                    Commerce menyatukan storefront publik, order masuk, konfirmasi pembayaran, pengiriman, fulfillment, dan affiliate dalam <strong>satu workspace yang rapi</strong> — tanpa bolak-balik antar aplikasi, tanpa rekap manual.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-lg btn-dark">
                        Mulai Pakai Commerce
                    </a>
                    <a href="#cara-kerja" class="btn btn-lg btn-outline-dark">Lihat Cara Kerjanya</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill"><i class="ti ti-check"></i> Storefront publik siap pakai</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Order & payment terhubung</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Shipping & fulfillment lebih rapi</span>
                    <span class="landing-pill"><i class="ti ti-check"></i> Affiliate bawaan</span>
                </div>
            </div>

            <div class="col-lg-6">
                {{-- RIGHT PANEL: sebelum/sesudah + stats --}}
                <div class="landing-panel p-4 p-lg-5" style="border:1px solid rgba(15,23,42,.08); box-shadow:0 28px 60px rgba(15,23,42,.08);">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#fff8ef; border:1px solid rgba(249,115,22,.14);">
                                <div class="fw-semibold mb-1 small text-danger">❌ Tanpa Commerce</div>
                                <ul class="small text-muted mb-0 ps-3 mt-2">
                                    <li>Order masuk via chat, DM, form terpisah</li>
                                    <li>Cek pembayaran manual satu per satu</li>
                                    <li>Update stok dan pengiriman via chat</li>
                                    <li>Affiliate dikelola pakai spreadsheet</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 h-100" style="background:#f0fdf4; border:1px solid rgba(22,163,74,.14);">
                                <div class="fw-semibold mb-1 small text-success">✅ Dengan Commerce</div>
                                <ul class="small text-muted mb-0 ps-3 mt-2">
                                    <li>Storefront publik, order langsung masuk</li>
                                    <li>Payment dikonfirmasi otomatis di sistem</li>
                                    <li>Queue shipping & fulfillment terpusat</li>
                                    <li>Affiliate tracking real-time, wallet terhubung</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12">
                            {{-- Image Placeholder 1 --}}
                            <div class="commerce-img-placeholder" style="height:160px;">
                                <i class="ti ti-device-desktop-analytics" style="font-size:2.5rem; opacity:0.6;"></i>
                                <div><strong>[IMAGE 1]</strong> — Dashboard overview Commerce: storefront live, order baru masuk, status pembayaran, antrian fulfillment</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 2 — TRUST STRIP
     ═══════════════════════════════════════════════════════════ --}}
<section class="py-4" style="border-bottom:1px solid var(--landing-line); background:#fff;">
    <div class="container">
        <div class="landing-trust-strip">
            <div class="landing-trust-item"><i class="ti ti-shopping-cart-check"></i> Order terpusat</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-credit-card"></i> Konfirmasi bayar otomatis</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-truck-delivery"></i> Shipping & fulfillment queue</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-affiliate"></i> Affiliate + wallet</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-server-2"></i> Server Indonesia</div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-headset"></i> Support 24 jam</div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 3 — PROBLEM / PAIN POINT
     ═══════════════════════════════════════════════════════════ --}}
<section id="masalah" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Masalah yang Kami Pahami</div>
                <h2 class="landing-section-title mb-3">Kalau order bisnis Anda makin banyak, tapi ini masih terjadi setiap hari…</h2>
                <p class="commerce-lead mb-4">Ini bukan salah Anda. Ini tanda sistem operasional Anda belum tumbuh secepat bisnis Anda.</p>

                <div class="d-flex flex-column gap-3">
                    @foreach([
                        ['icon' => 'ti-message-circle-x', 'text' => 'Order masuk dari WhatsApp, DM Instagram, dan form terpisah — susah dipantau semua sekaligus'],
                        ['icon' => 'ti-file-search',       'text' => 'Cek bukti bayar manual satu per satu, sering ketinggalan atau tertunda'],
                        ['icon' => 'ti-box-seam',          'text' => 'Update stok dan antrian pengiriman masih pakai Excel atau chat grup tim'],
                        ['icon' => 'ti-receipt-off',       'text' => 'Affiliate dan komisi dihitung manual, rentan salah dan makan waktu'],
                        ['icon' => 'ti-eye-off',           'text' => 'Owner tidak bisa melihat kondisi order real-time tanpa tanya tim dulu'],
                    ] as $pain)
                    <div class="d-flex align-items-start gap-3 p-3 rounded-4" style="background:#fff8ef; border:1px solid rgba(249,115,22,.12);">
                        <span class="commerce-icon-badge"><i class="ti {{ $pain['icon'] }}" style="font-size:1.2rem;"></i></span>
                        <div class="commerce-card-text">{{ $pain['text'] }}</div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 rounded-4" style="background:#fff7ed; border:2px solid rgba(249,115,22,.18);">
                    <div class="fw-bold mb-1">Ini bukan masalah kerja keras.</div>
                    <div class="commerce-card-text">Bisnis Anda sudah terbukti bisa jualan. Yang kurang adalah sistem yang tumbuh bersama order Anda.</div>
                </div>
            </div>
            <div class="col-lg-6">
                {{-- Image Placeholder 2 --}}
                <div class="commerce-img-placeholder" style="min-height:400px;">
                    <i class="ti ti-layout-grid-remove" style="font-size:3rem; opacity:0.5;"></i>
                    <div><strong>[IMAGE 2]</strong> — Ilustrasi bisnis tanpa sistem: banyak tab browser terbuka, notifikasi WhatsApp membanjir, sticky notes di mana-mana, ekspresi kewalahan. Gaya flat illustration, palet warm orange.</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 4 — SOLUSI / MODULES
     ═══════════════════════════════════════════════════════════ --}}
<section id="fitur" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Apa yang Ada di Commerce</div>
            <h2 class="landing-section-title mb-3">Satu workspace. Semua yang Anda butuhkan untuk jalankan order dari awal sampai selesai.</h2>
            <p class="landing-subtext mx-auto" style="max-width:640px;">Setiap modul di Commerce saling terhubung. Order yang masuk otomatis ada di antrian pembayaran. Pembayaran dikonfirmasi, lanjut ke shipping. Shipping selesai, fulfillment tercatat.</p>
        </div>

        <div class="row g-4">
            @foreach($modules as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="landing-feature-card p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="commerce-module-badge">
                                {!! $module['icon_svg'] !!}
                            </div>
                            <div>
                                <div class="landing-eyebrow mb-1">{{ $module['eyebrow'] }}</div>
                                <div class="h5 mb-0">{{ $module['name'] }}</div>
                            </div>
                        </div>
                        <p class="commerce-card-text mb-3">{{ $module['description'] }}</p>
                        <div class="small d-flex flex-column gap-1">
                            @foreach($module['public_points'] as $point)
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-check text-success flex-shrink-0 mt-1" style="font-size:0.9rem;"></i>
                                    <span class="text-muted">{{ $point }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Visual connector — menjelaskan keterhubungan antar modul --}}
        <div class="mt-5">
            {{-- Image Placeholder 3 --}}
            <div class="commerce-img-placeholder" style="min-height:220px;">
                <i class="ti ti-sitemap" style="font-size:2.5rem; opacity:0.5;"></i>
                <div><strong>[IMAGE 3]</strong> — Diagram alur terhubung: Storefront → Order Masuk → Pembayaran Dikonfirmasi → Antrian Shipping → Fulfillment → Selesai. Gaya diagram modern dengan panah dan icon masing-masing tahap. Warna: orange ke teal gradient.</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 5 — CARA KERJA (HOW IT WORKS)
     ═══════════════════════════════════════════════════════════ --}}
<section id="cara-kerja" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4">
                <div class="landing-eyebrow mb-2">Cara Kerja Commerce</div>
                <h2 class="landing-section-title mb-3">Dari produk tayang ke order selesai — semua dalam satu alur yang jelas.</h2>
                <p class="commerce-lead mb-4">Tidak perlu integrasi pihak ketiga. Tidak perlu switch antar aplikasi. Semua berjalan dalam satu workspace.</p>
                <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-dark">
                    Coba Sekarang — Gratis 14 Hari
                </a>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach([
                        ['no' => '1', 'icon' => 'ti-world-upload',     'color' => '#ea580c', 'title' => 'Publish Katalog & Storefront',
                         'text' => 'Upload produk, atur harga dan stok, lalu aktifkan storefront publik Anda. URL langsung bisa dibagikan ke pelanggan, iklan, atau bio media sosial.'],
                        ['no' => '2', 'icon' => 'ti-shopping-cart',    'color' => '#f97316', 'title' => 'Order Masuk Otomatis ke Workspace',
                         'text' => 'Setiap order dari storefront langsung muncul di antrian Commerce Anda — dengan detail produk, alamat, dan nominal yang sudah tersusun rapi.'],
                        ['no' => '3', 'icon' => 'ti-credit-card',      'color' => '#fb923c', 'title' => 'Konfirmasi Pembayaran Terpusat',
                         'text' => 'Sistem payment Commerce memantau status pembayaran setiap order. Dikonfirmasi atau ditolak — tim Anda tahu persis dari satu tampilan, tanpa cek manual.'],
                        ['no' => '4', 'icon' => 'ti-truck-delivery',   'color' => '#fdba74', 'title' => 'Proses Pengiriman & Fulfillment',
                         'text' => 'Order yang sudah terbayar masuk ke antrian shipping. Update resi, tandai selesai, catat fulfillment — semuanya tercatat di workspace yang sama.'],
                    ] as $step)
                    <div class="col-md-6">
                        <div class="landing-panel p-4 h-100" style="border-left:4px solid {{ $step['color'] }};">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div style="width:2rem;height:2rem;border-radius:999px;background:{{ $step['color'] }};color:#fff;font-size:0.8rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $step['no'] }}</div>
                                <i class="ti {{ $step['icon'] }}" style="font-size:1.25rem;color:{{ $step['color'] }};"></i>
                            </div>
                            <h3 class="h5 mb-2">{{ $step['title'] }}</h3>
                            <p class="commerce-card-text mb-0">{{ $step['text'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{-- Image Placeholder 4 --}}
                    <div class="commerce-img-placeholder" style="min-height:180px;">
                        <i class="ti ti-timeline" style="font-size:2.2rem; opacity:0.5;"></i>
                        <div><strong>[IMAGE 4]</strong> — Screenshot atau mockup layar: tampilan daftar order Commerce, menampilkan kolom nama produk, nama pembeli, status (Menunggu Bayar / Diproses / Dikirim / Selesai), badge warna-warni per status. Clean, professional UI screenshot.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 6 — BENEFIT STATS / KEY WINS
     ═══════════════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background: linear-gradient(135deg,#fff7ed 0%,#fffbeb 52%,#f8fafc 100%); border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                {{-- Image Placeholder 5 --}}
                <div class="commerce-img-placeholder" style="min-height:380px;">
                    <i class="ti ti-device-laptop" style="font-size:3rem; opacity:0.5;"></i>
                    <div><strong>[IMAGE 5]</strong> — Foto seorang pemilik bisnis (pria/wanita, usia 28-40, rapi profesional) sedang tersenyum melihat laptop yang menampilkan dashboard Commerce. Background: meja kerja modern minimalis. Pencahayaan terang, nuansa warm. Photographi style, bukan ilustrasi.</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="landing-eyebrow mb-2">Dampak Nyata</div>
                <h2 class="landing-section-title mb-4">Lebih sedikit waktu untuk administrasi. Lebih banyak waktu untuk jualan.</h2>
                <div class="row g-3 mb-4">
                    @foreach([
                        ['stat' => '0 menit', 'label' => 'waktu cek pembayaran manual per order', 'desc' => 'Konfirmasi otomatis, tidak perlu recek bukti transfer satu per satu'],
                        ['stat' => 'Satu tab', 'label' => 'untuk semua yang berhubungan dengan order', 'desc' => 'Storefront, order, payment, shipping, affiliate — satu workspace'],
                        ['stat' => 'Real-time', 'label' => 'visibilitas status order untuk tim dan owner', 'desc' => 'Tidak perlu tanya ke tim dulu untuk tahu kondisi order hari ini'],
                        ['stat' => '24/7',     'label' => 'storefront Anda buka menerima order', 'desc' => 'Pelanggan bisa order kapan saja tanpa butuh respons manual dari Anda'],
                    ] as $win)
                    <div class="col-sm-6">
                        <div class="landing-panel p-3 h-100">
                            <div class="commerce-stat-number mb-1" style="color:#ea580c;">{{ $win['stat'] }}</div>
                            <div class="fw-semibold small mb-1">{{ $win['label'] }}</div>
                            <div class="text-muted" style="font-size:0.82rem;">{{ $win['desc'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-dark">Mulai Pakai Commerce</a>
                    <a href="#pricing" class="btn btn-outline-dark">Lihat Harga</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 7 — TESTIMONIALS
     ═══════════════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kata Mereka</div>
            <h2 class="landing-section-title">Bisnis yang sudah merapikan operasional ordernya.</h2>
        </div>
        <div class="row g-4">
            @foreach([
                [
                    'quote' => '"Dulu saya harus cek WhatsApp, DM, dan email bergantian buat tahu ada order baru. Sekarang satu halaman sudah cukup. Tim saya lebih fokus dan saya lebih tenang."',
                    'name'  => 'Rizky A.',
                    'role'  => 'Owner, brand skincare lokal — 200+ order/bulan',
                    'img_no'=> '6a',
                ],
                [
                    'quote' => '"Yang paling bantu itu bagian shipping queue. Dulu tim sering terlewat karena data tersebar. Sekarang jelas siapa yang harus diproses duluan."',
                    'name'  => 'Dian M.',
                    'role'  => 'Manajer Operasional, toko fashion online',
                    'img_no'=> '6b',
                ],
                [
                    'quote' => '"Affiliate kami dulu dikelola pakai spreadsheet manual, sering ada komplain soal komisi. Sekarang semuanya ada di sistem, transparan, dan tidak ada drama lagi."',
                    'name'  => 'Bimo S.',
                    'role'  => 'Co-founder, marketplace produk lokal',
                    'img_no'=> '6c',
                ],
            ] as $t)
            <div class="col-lg-4">
                <div class="landing-panel p-4 h-100">
                    <div class="mb-3" style="color:#ea580c; font-size:1.5rem;">❝</div>
                    <p class="commerce-card-text mb-4">{{ $t['quote'] }}</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="commerce-testimonial-avatar">
                            {{-- Image Placeholder 6a/6b/6c --}}
                            <i class="ti ti-user" style="font-size:1.3rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">{{ $t['name'] }}</div>
                            <div class="text-muted" style="font-size:0.8rem;">{{ $t['role'] }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-muted" style="font-size:0.7rem; border-top:1px solid var(--landing-line); padding-top:0.75rem;">
                        [IMAGE {{ $t['img_no'] }}] — Foto portrait profesional {{ $t['name'] }}: headshot ramah, background blur netral, pencahayaan natural. Usia 25-40.
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 8 — PRICING
     ═══════════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Harga Commerce</div>
            <h2 class="landing-section-title mb-3">Mulai dari yang Anda butuhkan sekarang. Scale sesuai pertumbuhan order.</h2>
            <p class="landing-subtext mx-auto" style="max-width:600px;">Tidak ada biaya tersembunyi. Tidak ada kontrak panjang. Pilih paket, buat workspace, aktifkan — dan operasional order Anda langsung lebih rapi.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @forelse($plans as $plan)
                @php
                    $sales     = (array) ($plan->sales_meta ?? []);
                    $limits    = (array) ($plan->limits ?? []);
                    $price     = app(\App\Support\MoneyFormatter::class)->format((float) ($sales['price'] ?? 0), (string) ($sales['currency'] ?? 'IDR'));
                    $isFeatured = !empty($sales['recommended']);
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="landing-plan-card {{ $isFeatured ? 'featured' : '' }} p-4 h-100">
                        @if($isFeatured)
                            <div class="landing-plan-popular">Paling Populer</div>
                        @endif
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="h4 mb-1">Commerce {{ $plan->name }}</div>
                                <div class="text-muted small">{{ $plan->billingIntervalLabel() }}</div>
                            </div>
                        </div>
                        <div class="landing-price mb-1">{{ $price }}</div>
                        <p class="text-muted small mb-4">{{ $sales['description'] ?? $sales['tagline'] ?? '' }}</p>

                        <div class="landing-limit-table mb-4">
                            <div class="landing-limit-row">
                                <span class="text-muted small">User</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::USERS] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Branch</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Produk</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div class="landing-limit-row">
                                <span class="text-muted small">Kontak</span>
                                <span class="fw-semibold small">{{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @if(!empty($sales['highlights']))
                        <div class="small d-flex flex-column gap-1 mb-4">
                            @foreach((array) $sales['highlights'] as $highlight)
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-check text-success flex-shrink-0" style="font-size:0.9rem; margin-top:0.15rem;"></i>
                                    <span class="text-muted">{{ $highlight }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <a href="{{ route('onboarding.create', ['product_line' => 'commerce', 'plan' => $plan->code]) }}"
                           class="btn {{ $isFeatured ? 'btn-dark' : 'btn-outline-dark' }} w-100">
                            Pilih Paket Ini
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="text-muted">Informasi paket sedang diperbarui. Hubungi kami untuk detail harga.</div>
                    <a href="{{ route('landing') }}" class="btn btn-dark mt-3">Hubungi Kami</a>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-5">
            <div class="landing-panel p-4 d-inline-block text-start" style="max-width:640px;">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-shield-check text-success flex-shrink-0" style="font-size:1.5rem; margin-top:0.1rem;"></i>
                    <div>
                        <div class="fw-semibold mb-1">Tidak yakin mulai dari mana?</div>
                        <div class="commerce-card-text mb-2">Tim kami siap bantu Anda memilih paket yang tepat sesuai volume order dan ukuran tim Anda saat ini.</div>
                        <a href="https://wa.me/6281222229815?text={{ urlencode('Halo, saya ingin konsultasi paket Commerce yang tepat untuk bisnis saya.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark">
                            <i class="ti ti-brand-whatsapp me-1"></i>Chat WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 9 — YANG SUDAH TERMASUK vs BATASAN (jujur, tapi positif)
     ═══════════════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Transparansi Produk</div>
            <h2 class="landing-section-title">Commerce cocok untuk Anda? Ini panduan jujurnya.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100" style="border-left:4px solid #22c55e;">
                    <div class="landing-eyebrow mb-3" style="color:#16a34a;">Commerce adalah pilihan tepat jika…</div>
                    <div class="landing-checklist">
                        <div><i class="ti ti-check text-success"></i> Bisnis Anda menerima order dari katalog / storefront publik</div>
                        <div><i class="ti ti-check text-success"></i> Anda butuh payment status, shipping queue, dan fulfillment yang terhubung</div>
                        <div><i class="ti ti-check text-success"></i> Anda ingin membuka jalur affiliate dengan tracking komisi otomatis</div>
                        <div><i class="ti ti-check text-success"></i> Tim Anda butuh visibilitas order real-time tanpa bolak-balik chat</div>
                        <div><i class="ti ti-check text-success"></i> Anda belum butuh purchase order formal, atau laporan akuntansi lengkap</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100" style="border-left:4px solid #f59e0b;">
                    <div class="landing-eyebrow mb-3" style="color:#d97706;">Pertimbangkan Business Suite jika…</div>
                    <div class="landing-checklist">
                        <div><i class="ti ti-alert-triangle text-warning"></i> Bisnis Anda juga membutuhkan purchase order dan invoice formal</div>
                        <div><i class="ti ti-alert-triangle text-warning"></i> Anda perlu laporan neraca, laba rugi, dan audit trail akuntansi</div>
                        <div><i class="ti ti-alert-triangle text-warning"></i> Bisnis Anda punya gudang dengan inventory governance ketat</div>
                    </div>
                    <div class="mt-3 p-3 rounded-3 small" style="background:#fffbeb;">
                        Commerce tetap menggunakan shared modul products, sales, payments, dan contacts — sehingga jika suatu saat bisnis berkembang, upgrade ke Business Suite tidak perlu setup ulang dari nol.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 10 — FAQ
     ═══════════════════════════════════════════════════════════ --}}
<section id="faq" class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="landing-eyebrow mb-2">FAQ</div>
                    <h2 class="landing-section-title">Pertanyaan yang sering ditanyakan.</h2>
                </div>
                <div class="d-flex flex-column gap-3">
                    @foreach([
                        [
                            'q' => 'Apakah pelanggan saya bisa langsung order dari storefront tanpa perlu daftar akun?',
                            'a' => 'Ya. Storefront Commerce bersifat publik. Pelanggan bisa browse produk dan melakukan order tanpa perlu membuat akun. URL storefront bisa langsung Anda bagikan ke media sosial, iklan, atau link bio.',
                        ],
                        [
                            'q' => 'Bagaimana konfirmasi pembayaran bekerja?',
                            'a' => 'Commerce memiliki modul payment status terpusat. Setiap order yang masuk memiliki status pembayaran yang bisa Anda konfirmasi atau tolak langsung dari workspace — tanpa perlu cek bukti transfer manual ke satu per satu.',
                        ],
                        [
                            'q' => 'Apakah bisa dipakai untuk lebih dari satu toko atau branch?',
                            'a' => 'Ya, tergantung paket yang dipilih. Setiap paket memiliki batas jumlah branch yang bisa Anda buat. Semua branch tetap berada dalam satu workspace yang sama, sehingga laporan dan data tetap terpusat.',
                        ],
                        [
                            'q' => 'Apakah ada biaya setup atau biaya tersembunyi lainnya?',
                            'a' => 'Tidak ada biaya setup. Harga yang tercantum adalah harga final per periode billing. Anda bisa mulai, berhenti, atau upgrade kapan saja tanpa penalti.',
                        ],
                        [
                            'q' => 'Kalau bisnis saya berkembang dan butuh fitur akuntansi, apakah harus mulai dari nol?',
                            'a' => 'Tidak. Commerce menggunakan shared module dengan Business Suite (products, contacts, sales, payments). Jika Anda kemudian membutuhkan fitur akuntansi formal, upgrade dilakukan tanpa kehilangan data yang sudah ada.',
                        ],
                        [
                            'q' => 'Berapa lama setup sampai storefront bisa live?',
                            'a' => 'Proses onboarding dirancang agar cepat. Setelah workspace aktif, Anda bisa upload produk dan mengaktifkan storefront dalam hitungan menit. Tidak perlu keahlian teknis apapun.',
                        ],
                    ] as $faq)
                    <details class="commerce-faq-item">
                        <summary>{{ $faq['q'] }}</summary>
                        <div class="commerce-faq-body">{{ $faq['a'] }}</div>
                    </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SECTION 11 — FINAL CTA BAND
     ═══════════════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-cta-band p-5 p-lg-6">
            <div class="row g-5 align-items-center">
                <div class="col-lg-7">
                    <div class="landing-eyebrow mb-3" style="color:rgba(255,255,255,0.6);">Siap dimulai?</div>
                    <h2 class="mb-3" style="font-size:clamp(1.8rem,3.5vw,3rem); font-weight:800; letter-spacing:-0.03em; color:#fff; line-height:1.1;">
                        Rapi sekarang. Bukan nanti.
                    </h2>
                    <p class="mb-4" style="font-size:1.1rem; color:rgba(255,255,255,0.8); max-width:500px; line-height:1.7;">
                        Setiap order yang masuk hari ini tanpa sistem adalah potensi yang terlewat. Buat workspace Commerce Anda sekarang dan mulai rapi dari order pertama.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('onboarding.create', ['product_line' => 'commerce']) }}" class="btn btn-lg" style="background:#fff; color:#0f172a; font-weight:700; border-radius:999px;">
                            <i class="ti ti-arrow-right me-1"></i>Buat Workspace Commerce
                        </a>
                        <a href="https://wa.me/6281222229815?text={{ urlencode('Halo, saya ingin tahu lebih lanjut tentang Commerce sebelum daftar.') }}" target="_blank" rel="noopener"
                           class="btn btn-lg btn-outline-light" style="border-radius:999px;">
                            <i class="ti ti-brand-whatsapp me-1"></i>Tanya Dulu via WA
                        </a>
                    </div>
                    <div class="mt-3 small" style="color:rgba(255,255,255,0.55);">
                        Tidak ada kontrak. Tidak ada biaya setup. Batalkan kapan saja.
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    {{-- Image Placeholder 7 --}}
                    <div class="rounded-4 d-flex flex-column align-items-center justify-content-center gap-2 text-center" style="min-height:220px; background:rgba(255,255,255,0.1); border:2px dashed rgba(255,255,255,0.3); color:rgba(255,255,255,0.7); font-size:0.82rem; font-weight:600; padding:1.5rem;">
                        <i class="ti ti-rocket" style="font-size:2.5rem; opacity:0.5;"></i>
                        <div><strong>[IMAGE 7]</strong> — Ilustrasi sederhana: toko online yang ramai, paket-paket yang tersusun rapi, panah ke atas menandakan pertumbuhan. Gaya flat illustration modern, warna dominan putih dan oranye cerah di atas background gelap/gradient biru tua.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
