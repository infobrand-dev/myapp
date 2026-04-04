@extends('layouts.landing')

@section('head_title', 'Tentang Kami — ' . config('app.name'))
@section('head_description', 'Meetra dibangun untuk membantu bisnis Indonesia bekerja lebih rapi — dari customer, transaksi, sampai workflow tim — dalam satu workspace.')

@section('content')

{{-- ══ HERO ════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#f8fafc 0%,#eef1ff 100%);border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-3">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="landing-badge mb-4 mx-auto" style="display:inline-flex;">
                    <i class="ti ti-info-circle"></i> Tentang Meetra
                </div>
                <h1 class="landing-headline mb-4">
                    Dibuat untuk bisnis Indonesia yang ingin <span>kerja lebih rapi</span>.
                </h1>
                <p class="landing-subtext mx-auto">
                    Meetra adalah platform bisnis yang menyatukan customer, transaksi, dan workflow tim dalam satu workspace — supaya data tidak tersebar ke terlalu banyak alat terpisah.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ══ STORY ════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 justify-content-center">
            <div class="col-lg-7">
                <div class="landing-eyebrow mb-2">Kenapa Kami Membangun Ini</div>
                <h2 class="landing-section-title mb-4">Masalah ini nyata, dan kami melihatnya setiap hari.</h2>
                <p class="landing-subtext mb-4">Banyak bisnis Indonesia — dari toko, jasa, sampai distribusi — masih mengelola pelanggan lewat HP pribadi admin, mencatat transaksi di spreadsheet yang terpisah, dan me-rekap laporan secara manual setiap minggu.</p>
                <p class="landing-subtext mb-4">Data pelanggan tidak nyambung ke data transaksi. Tim sales tidak tahu status pembayaran. Stok tidak update real-time. Dan setiap kali ada pergantian admin, semua harus dimulai dari awal lagi.</p>
                <p class="landing-subtext mb-0">Meetra kami bangun sebagai jawaban untuk masalah itu — bukan dengan membuat tool yang paling rumit, tapi dengan membuat workspace yang paling mudah dipakai dan langsung berguna sejak hari pertama.</p>
            </div>
            <div class="col-lg-4">
                <div class="landing-panel rounded-4 p-4">
                    <div class="landing-checklist">
                        <div><i class="ti ti-check text-success"></i> Data terpusat, tidak tersebar</div>
                        <div><i class="ti ti-check text-success"></i> Aktif langsung setelah bayar</div>
                        <div><i class="ti ti-check text-success"></i> Mulai simple, tumbuh sesuai kebutuhan</div>
                        <div><i class="ti ti-check text-success"></i> Bahasa Indonesia, support lokal</div>
                        <div><i class="ti ti-check text-success"></i> Bayar dengan metode lokal</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ VALUES ═══════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Prinsip Kami</div>
            <h2 class="landing-section-title">Apa yang kami pegang saat membangun Meetra.</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="meetra-why-card">
                    <div class="mb-3"><i class="ti ti-focus-2" style="font-size:1.5rem;color:var(--landing-blue);"></i></div>
                    <h3 class="h5 mb-2">Fokus ke yang penting dulu</h3>
                    <p class="small text-muted mb-0">Kami tidak memaksa bisnis langsung pakai semua fitur. Mulai dari yang paling dibutuhkan, tambah saat sudah siap.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="meetra-why-card">
                    <div class="mb-3"><i class="ti ti-eye" style="font-size:1.5rem;color:var(--landing-blue);"></i></div>
                    <h3 class="h5 mb-2">Harga dan fitur yang transparan</h3>
                    <p class="small text-muted mb-0">Tidak ada biaya tersembunyi. Harga tertera jelas di halaman produk. Anda tahu persis apa yang dibayar dan apa yang didapat.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="meetra-why-card">
                    <div class="mb-3"><i class="ti ti-map-pin" style="font-size:1.5rem;color:var(--landing-blue);"></i></div>
                    <h3 class="h5 mb-2">Dibangun untuk Indonesia</h3>
                    <p class="small text-muted mb-0">Server di Indonesia, antarmuka bahasa Indonesia, metode pembayaran lokal, dan support yang berbicara bahasa yang sama dengan Anda.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="meetra-why-card">
                    <div class="mb-3"><i class="ti ti-headset" style="font-size:1.5rem;color:var(--landing-blue);"></i></div>
                    <h3 class="h5 mb-2">Support yang bisa dihubungi</h3>
                    <p class="small text-muted mb-0">Tim kami bisa dihubungi lewat WhatsApp. Anda tidak akan mendapat balasan bot saja — ada manusia yang baca dan merespons.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ TRUST ════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Infrastruktur</div>
                <h2 class="landing-section-title mb-3">Data Anda ada di Indonesia, bukan di server luar negeri.</h2>
                <p class="landing-subtext mb-4">Setiap workspace berjalan di atas infrastruktur cloud Tier-1 yang berlokasi di Indonesia. Data bisnis Anda tidak keluar dari wilayah hukum Indonesia.</p>
                <a href="{{ route('security') }}" class="btn btn-outline-dark">
                    <i class="ti ti-shield-check me-1"></i>Baca halaman keamanan data
                </a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-server-2 mb-3 d-block" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                            <div class="fw-semibold mb-1">Server di Indonesia</div>
                            <div class="small text-muted">Data center cloud Tier-1 berlokasi di Indonesia. Data tidak keluar dari wilayah Indonesia.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-lock mb-3 d-block" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                            <div class="fw-semibold mb-1">Enkripsi & Isolasi</div>
                            <div class="small text-muted">Setiap workspace terisolasi penuh. Koneksi dienkripsi TLS. Data satu bisnis tidak bisa diakses bisnis lain.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-database-heart mb-3 d-block" style="font-size:1.5rem;color:var(--landing-teal);"></i>
                            <div class="fw-semibold mb-1">Backup Otomatis</div>
                            <div class="small text-muted">Data dicadangkan otomatis setiap hari. High-availability untuk meminimalkan downtime operasional.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-credit-card mb-3 d-block" style="font-size:1.5rem;color:var(--landing-teal);"></i>
                            <div class="fw-semibold mb-1">Bayar Lokal</div>
                            <div class="small text-muted">Transfer bank, virtual account, atau QRIS. Tidak perlu kartu kredit atau akun PayPal.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CONTACT ══════════════════════════════════════════════ --}}
<section id="kontak" class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Hubungi Kami</div>
                <h2 class="landing-section-title mb-3">Ada pertanyaan? Langsung hubungi tim kami.</h2>
                <p class="landing-subtext mb-0">Kami lebih suka berbicara langsung daripada membiarkan pertanyaan tak terjawab. Chat WhatsApp adalah cara tercepat untuk mendapat respons dari tim kami.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-12">
                        <a href="https://wa.me/6281222229815" target="_blank" rel="noopener"
                           class="landing-panel rounded-4 p-4 d-flex align-items-center gap-4 text-decoration-none"
                           style="transition:box-shadow .15s;">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3"
                                 style="width:56px;height:56px;background:#dcfce7;">
                                <i class="ti ti-brand-whatsapp" style="font-size:1.5rem;color:#16a34a;"></i>
                            </div>
                            <div>
                                <div class="fw-semibold mb-1">WhatsApp — Cara Tercepat</div>
                                <div class="small text-muted">+62 812-222-9815 · Biasanya dibalas dalam hitungan jam</div>
                            </div>
                            <div class="ms-auto flex-shrink-0">
                                <i class="ti ti-arrow-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-12">
                        <a href="mailto:support@meetra.id"
                           class="landing-panel rounded-4 p-4 d-flex align-items-center gap-4 text-decoration-none">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3"
                                 style="width:56px;height:56px;background:#eff6ff;">
                                <i class="ti ti-mail" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                            </div>
                            <div>
                                <div class="fw-semibold mb-1">Email Support</div>
                                <div class="small text-muted">support@meetra.id · Untuk pertanyaan teknis dan billing</div>
                            </div>
                            <div class="ms-auto flex-shrink-0">
                                <i class="ti ti-arrow-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-12">
                        <div class="landing-panel rounded-4 p-4 d-flex align-items-center gap-4">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3"
                                 style="width:56px;height:56px;background:#f0fdf4;">
                                <i class="ti ti-clock" style="font-size:1.5rem;color:#16a34a;"></i>
                            </div>
                            <div>
                                <div class="fw-semibold mb-1">Jam Dukungan</div>
                                <div class="small text-muted">Senin–Sabtu, 08.00–21.00 WIB · Untuk darurat teknis tersedia 24 jam</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel rounded-4 p-4 p-lg-5 text-center">
            <h2 class="landing-section-title mb-3">Produk terbaik adalah yang bisa dicoba langsung.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:640px;">Tidak perlu percaya kata-kata kami saja. Daftar, buat workspace, dan rasakan sendiri apakah Meetra sesuai dengan cara bisnis Anda bekerja.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_growth', 'trial' => 1]) }}" class="btn btn-dark btn-lg">Coba Gratis 14 Hari</a>
                <a href="{{ route('landing.accounting') }}" class="btn btn-outline-dark btn-lg">Lihat Produk</a>
            </div>
        </div>
    </div>
</section>

@endsection
