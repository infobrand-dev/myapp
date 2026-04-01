<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keamanan Data — {{ config('app.name') }}</title>
    <meta name="description" content="Bagaimana Meetra melindungi data bisnis dan pelanggan Anda — infrastruktur, enkripsi, isolasi tenant, dan praktik keamanan yang kami terapkan.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="landing-page">
<div class="landing-shell">

{{-- ══ TOPBAR ══════════════════════════════════════════════ --}}
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="{{ route('landing') }}#solutions" class="landing-nav-link">Fitur</a>
                <a href="{{ route('landing') }}#pricing"   class="landing-nav-link">Harga</a>
                <a href="{{ route('landing') }}#faq"       class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('onboarding.create') }}" class="btn btn-dark btn-sm">Daftar Gratis</a>
            </div>
        </div>
    </div>
</header>

<main>

{{-- ══ HERO ════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background: linear-gradient(135deg, #f8fafc 0%, #eef1ff 100%); border-bottom: 1px solid var(--landing-line);">
    <div class="container py-lg-3">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <div class="landing-badge mb-4">
                    <i class="ti ti-shield-check"></i> Keamanan &amp; Privasi Data
                </div>
                <h1 class="landing-headline mb-4">
                    Data bisnis Anda <span>dilindungi</span> dengan serius.
                </h1>
                <p class="landing-subtext mb-0">
                    Kami membangun Meetra di atas infrastruktur kelas enterprise dengan isolasi data penuh antar workspace, enkripsi end-to-end, dan pemantauan aktif 24 jam. Tidak ada kompromi untuk keamanan.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-lock"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.5rem;color:var(--landing-blue);">TLS 1.3</div>
                            <div class="small text-muted">Semua koneksi dienkripsi</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-building-bank"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.5rem;color:var(--landing-teal);">100%</div>
                            <div class="small text-muted">Isolasi data per workspace</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-map-pin"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.5rem;color:var(--landing-blue);">🇮🇩</div>
                            <div class="small text-muted">Server di Indonesia</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-clock-24"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.5rem;color:var(--landing-teal);">24/7</div>
                            <div class="small text-muted">Monitoring &amp; dukungan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ INFRASTRUCTURE ══════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Infrastruktur</div>
            <h2 class="landing-section-title">Dibangun di atas fondasi yang kokoh.</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-server-2"></i></div>
                    <h3 class="h5 mb-2">Data Center di Indonesia</h3>
                    <p class="text-muted small mb-0">Seluruh data Anda disimpan di data center yang berlokasi di wilayah Indonesia — mematuhi regulasi penyimpanan data lokal dan memastikan latensi rendah untuk pengguna di Indonesia.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-database-heart"></i></div>
                    <h3 class="h5 mb-2">Database PostgreSQL Enterprise</h3>
                    <p class="text-muted small mb-0">Kami menggunakan database PostgreSQL managed kelas enterprise dengan replikasi otomatis, high-availability built-in, dan point-in-time recovery. Data tidak pernah berjalan di server tunggal.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-world-check"></i></div>
                    <h3 class="h5 mb-2">Jaringan Cloud Tier-1</h3>
                    <p class="text-muted small mb-0">Infrastruktur jaringan kami dioperasikan oleh penyedia cloud Tier-1 di Indonesia dengan jaminan uptime tinggi, redundansi jalur, dan perlindungan DDoS aktif.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-refresh"></i></div>
                    <h3 class="h5 mb-2">Backup Harian Otomatis</h3>
                    <p class="text-muted small mb-0">Backup dilakukan secara otomatis setiap hari. Kami menyimpan riwayat backup hingga 30 hari ke belakang sehingga data dapat dipulihkan kapan saja bila terjadi insiden.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-activity"></i></div>
                    <h3 class="h5 mb-2">Monitoring 24/7</h3>
                    <p class="text-muted small mb-0">Sistem pemantauan berjalan sepanjang waktu untuk mendeteksi anomali, lonjakan traffic tidak wajar, dan potensi insiden keamanan — dengan eskalasi otomatis ke tim kami.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-card p-4 h-100">
                    <div class="security-card-icon mb-3"><i class="ti ti-lock-access"></i></div>
                    <h3 class="h5 mb-2">Akses Terkontrol</h3>
                    <p class="text-muted small mb-0">Akses ke infrastruktur produksi dibatasi dengan prinsip least-privilege. Audit log tersimpan untuk setiap akses ke sistem dan database produksi.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ DATA SECURITY ════════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background: #f8fafc; border-top: 1px solid var(--landing-line); border-bottom: 1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Keamanan Data</div>
                <h2 class="landing-section-title mb-3">Data Anda tidak bisa dilihat siapapun selain tim Anda.</h2>
                <p class="landing-subtext mb-4">Setiap workspace berjalan dalam ruang data yang sepenuhnya terisolasi. Tidak ada bisnis lain yang bisa mengakses data Anda — dan tim kami pun tidak bisa membacanya tanpa izin eksplisit dari Anda.</p>
                <div class="landing-checklist">
                    <div><i class="ti ti-check text-success"></i> Percakapan, kontak, dan data pelanggan hanya bisa dilihat oleh tim di workspace Anda</div>
                    <div><i class="ti ti-check text-success"></i> Workspace bisnis lain tidak bisa mengakses data Anda dalam kondisi apapun</div>
                    <div><i class="ti ti-check text-success"></i> Semua koneksi ke platform dienkripsi — data tidak bisa disadap di perjalanan</div>
                    <div><i class="ti ti-check text-success"></i> Password tidak bisa dibaca oleh siapapun, termasuk tim kami</div>
                    <div><i class="ti ti-check text-success"></i> Data Anda tidak dijual, tidak dipinjamkan, tidak digunakan untuk keperluan lain</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="security-trust-card p-4 d-flex align-items-start gap-4">
                            <div class="security-trust-icon"><i class="ti ti-building-community"></i></div>
                            <div>
                                <div class="fw-bold mb-1" style="font-size:1.05rem;">Aman dari workspace lain</div>
                                <div class="text-muted">Data bisnis Anda sepenuhnya terisolasi. Tidak ada pengguna dari workspace lain — baik sengaja maupun tidak — yang bisa melihat percakapan, kontak, atau pengaturan Anda.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="security-trust-card p-4 d-flex align-items-start gap-4">
                            <div class="security-trust-icon"><i class="ti ti-lock-bolt"></i></div>
                            <div>
                                <div class="fw-bold mb-1" style="font-size:1.05rem;">Aman saat berpindah</div>
                                <div class="text-muted">Setiap data yang bergerak antara perangkat Anda dan server kami dienkripsi penuh. Tidak ada celah untuk disadap di tengah perjalanan, dari manapun Anda mengakses.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="security-trust-card p-4 d-flex align-items-start gap-4">
                            <div class="security-trust-icon"><i class="ti ti-eye-off"></i></div>
                            <div>
                                <div class="fw-bold mb-1" style="font-size:1.05rem;">Aman dari kami sendiri</div>
                                <div class="text-muted">Tim Meetra tidak memantau isi percakapan Anda. Akses ke data pengguna sangat dibatasi, tercatat, dan hanya dilakukan jika Anda meminta bantuan teknis secara langsung.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ PRACTICES ════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Perlindungan Aktif</div>
            <h2 class="landing-section-title">Lapisan keamanan yang bekerja terus-menerus.</h2>
            <p class="landing-subtext mx-auto">Kami tidak hanya mengandalkan satu lapisan perlindungan. Server dan data Anda dijaga oleh beberapa teknologi keamanan yang berjalan bersamaan.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-cloud-lock"></i></div>
                    <div>
                        <h3 class="mb-1">Proteksi Jaringan Global</h3>
                        <p class="text-muted mb-0">Seluruh traffic ke platform Meetra melewati lapisan proteksi jaringan global. Serangan DDoS, bot berbahaya, dan ancaman jaringan diblokir secara otomatis sebelum menyentuh server kami.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-virus-search"></i></div>
                    <div>
                        <h3 class="mb-1">Keamanan Server Real-time Berbasis AI</h3>
                        <p class="text-muted mb-0">Server kami dilindungi oleh sistem keamanan berbasis kecerdasan buatan yang memindai, mendeteksi, dan memblokir ancaman secara real-time — mulai dari malware, brute force, hingga eksploitasi celah keamanan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-eye-off"></i></div>
                    <div>
                        <h3 class="mb-1">Akses Internal yang Sangat Terbatas</h3>
                        <p class="text-muted mb-0">Tidak ada anggota tim yang memiliki akses bebas ke data pengguna. Setiap akses ke sistem produksi tercatat, dibatasi sesuai kebutuhan, dan diaudit secara berkala.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-file-alert"></i></div>
                    <div>
                        <h3 class="mb-1">Respons Insiden yang Jelas</h3>
                        <p class="text-muted mb-0">Jika terjadi insiden yang berdampak pada data Anda, kami berkomitmen untuk memberi tahu Anda dengan cepat dan transparan — bukan pernyataan abu-abu. Anda berhak tahu apa yang terjadi.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-activity"></i></div>
                    <div>
                        <h3 class="mb-1">Pemantauan 24/7 Tanpa Jeda</h3>
                        <p class="text-muted mb-0">Sistem monitoring berjalan sepanjang waktu memantau kesehatan server, aktivitas tidak wajar, dan potensi ancaman — dengan notifikasi otomatis ke tim kami bila ada yang perlu ditangani.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-practice p-4 d-flex gap-3">
                    <div class="security-practice-icon flex-shrink-0"><i class="ti ti-logs"></i></div>
                    <div>
                        <h3 class="mb-1">Rekaman Aktivitas Lengkap</h3>
                        <p class="text-muted mb-0">Setiap aksi penting di dalam workspace tercatat — dari login, perubahan pengaturan, hingga akses ke data. Log ini disimpan aman dan tidak bisa dihapus oleh siapapun sembarangan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ YOUR RESPONSIBILITY ══════════════════════════════════ --}}
<section class="py-5" style="background: #f8fafc; border-top: 1px solid var(--landing-line);">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 rounded-4">
            <div class="row g-4 align-items-start">
                <div class="col-lg-6">
                    <div class="landing-eyebrow mb-2">Yang bisa Anda lakukan</div>
                    <h2 class="h4 mb-3">Keamanan adalah tanggung jawab bersama.</h2>
                    <p class="text-muted small mb-0">Selain langkah-langkah yang kami ambil, ada hal-hal yang bisa Anda lakukan sebagai admin workspace untuk memperkuat keamanan tim Anda.</p>
                </div>
                <div class="col-lg-6">
                    <div class="landing-checklist">
                        <div><i class="ti ti-check text-success"></i> Gunakan password yang kuat dan unik untuk setiap akun pengguna</div>
                        <div><i class="ti ti-check text-success"></i> Hapus atau nonaktifkan akun pengguna yang sudah tidak aktif</div>
                        <div><i class="ti ti-check text-success"></i> Batasi role pengguna sesuai kebutuhan — jangan berikan akses admin sembarangan</div>
                        <div><i class="ti ti-check text-success"></i> Jangan bagikan credential API WhatsApp atau platform lain ke pihak tidak dikenal</div>
                        <div><i class="ti ti-check text-success"></i> Laporkan segera jika mencurigai aktivitas tidak wajar di workspace Anda</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CONTACT ══════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Laporan Keamanan</div>
            <h2 class="landing-section-title">Temukan celah keamanan? Hubungi kami.</h2>
            <p class="landing-subtext mx-auto">Jika Anda menemukan potensi kerentanan atau masalah keamanan di platform kami, kami sangat menghargai laporan yang bertanggung jawab. Kami akan merespons dalam waktu 24 jam.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="security-contact-card p-4 text-center">
                    <div class="security-contact-icon mb-3"><i class="ti ti-mail"></i></div>
                    <h3 class="h6 mb-1">Email</h3>
                    <a href="mailto:support@meetra.id" class="text-muted small text-decoration-none">support@meetra.id</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="security-contact-card p-4 text-center">
                    <div class="security-contact-icon mb-3"><i class="ti ti-brand-whatsapp"></i></div>
                    <h3 class="h6 mb-1">WhatsApp</h3>
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="text-muted small text-decoration-none">+62 812-222-9815</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="security-contact-card p-4 text-center">
                    <div class="security-contact-icon mb-3"><i class="ti ti-clock-24"></i></div>
                    <h3 class="h6 mb-1">Waktu Respons</h3>
                    <span class="text-muted small">Kurang dari 24 jam</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════ --}}
<section class="py-5">
    <div class="container">
        <div class="landing-cta-band p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h2 class="landing-section-title text-white mb-2">Siap mulai dengan platform yang aman?</h2>
                    <p class="mb-0 opacity-75">Daftar workspace dan rasakan sendiri infrastruktur omnichannel yang dibangun dengan standar keamanan enterprise.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('onboarding.create') }}" class="btn btn-light btn-lg me-2 mb-2">Mulai Sekarang</a>
                    <a href="{{ route('landing') }}" class="btn btn-outline-light btn-lg mb-2">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

{{-- ══ FOOTER ══════════════════════════════════════════════ --}}
<footer class="landing-footer">
    <div class="container">
        <div class="landing-footer-inner row g-5">
            <div class="col-lg-4">
                <div class="mb-3">
                    <x-app-logo variant="default" :height="30" />
                </div>
                <p class="landing-footer-tagline mb-4">Platform omnichannel untuk tim sales, support, dan marketing — semua percakapan pelanggan dalam satu workspace.</p>
                <div class="landing-footer-contact">
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="landing-footer-contact-item">
                        <i class="ti ti-brand-whatsapp"></i>
                        <span>+62 812-222-9815</span>
                        <span class="landing-footer-contact-badge">Chat WhatsApp</span>
                    </a>
                    <a href="mailto:support@meetra.id" class="landing-footer-contact-item">
                        <i class="ti ti-mail"></i>
                        <span>support@meetra.id</span>
                    </a>
                    <div class="landing-footer-contact-item">
                        <i class="ti ti-headset"></i>
                        <span>Dukungan tersedia 24 jam</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Produk</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('landing') }}#solutions">Fitur</a>
                    <a href="{{ route('landing') }}#pricing">Harga</a>
                    <a href="{{ route('landing') }}#ai-credits">AI Credits</a>
                    <a href="{{ route('landing') }}#faq">FAQ</a>
                    <a href="{{ route('workspace.finder') }}">Login Workspace</a>
                    <a href="{{ route('onboarding.create') }}">Daftar Gratis</a>
                </nav>
            </div>
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Perusahaan</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('affiliate.program') }}">Program Partner</a>
                    <a href="{{ route('security') }}">Keamanan Data</a>
                    <a href="{{ route('privacy') }}">Kebijakan Privasi</a>
                    <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
                </nav>
            </div>
            <div class="col-lg-4">
                <div class="landing-footer-heading">Infrastruktur &amp; Keamanan</div>
                <div class="landing-footer-trust-cards">
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-server-2"></i>
                        <div>
                            <div class="fw-semibold">Server di Indonesia</div>
                            <div>Data center cloud Tier-1 berlokasi di Indonesia.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div>
                            <div class="fw-semibold">Koneksi Terenkripsi</div>
                            <div>TLS aktif di semua koneksi. Isolasi data per workspace.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div>
                            <div class="fw-semibold">Database PostgreSQL</div>
                            <div>Managed enterprise · Backup harian otomatis.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <div class="landing-footer-copy">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Hak cipta dilindungi.
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="landing-footer-trust-pill"><i class="ti ti-shield-check"></i> Data Aman</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-brand-whatsapp"></i> WhatsApp API Resmi</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-credit-card"></i> Bayar Lokal</span>
            </div>
        </div>
    </div>
</footer>

</div>{{-- .landing-shell --}}
</body>
</html>
