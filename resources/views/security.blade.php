<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keamanan Data — {{ config('app.name') }}</title>
    <meta name="description" content="Bagaimana Meetra melindungi data bisnis dan pelanggan Anda. Server Indonesia, database enterprise, enkripsi, dan isolasi data per workspace.">
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
                    <i class="ti ti-shield-check"></i> Keamanan &amp; Privasi
                </div>
                <h1 class="landing-headline mb-4">
                    Data bisnis Anda adalah <span>milik Anda</span> sepenuhnya.
                </h1>
                <p class="landing-subtext mb-0">
                    Kami tidak menjual, meminjamkan, atau mengakses data percakapan dan pelanggan Anda tanpa izin. Data Anda tersimpan di server Indonesia, dan Anda bisa memintanya kapan saja.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-map-pin"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.3rem;color:var(--landing-blue);">Indonesia</div>
                            <div class="small text-muted">Lokasi server & data</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-lock"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.3rem;color:var(--landing-teal);">TLS + enkripsi</div>
                            <div class="small text-muted">Semua koneksi aman</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-building-community"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.3rem;color:var(--landing-blue);">Terisolasi</div>
                            <div class="small text-muted">Data tidak bercampur</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="security-stat-card p-3 text-center">
                            <div class="security-stat-icon mb-2"><i class="ti ti-headset"></i></div>
                            <div class="fw-bold mb-1" style="font-size:1.3rem;color:var(--landing-teal);">24 jam</div>
                            <div class="small text-muted">Dukungan tersedia</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ PERTANYAAN NYATA ═════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Yang sering ditanyakan</div>
            <h2 class="landing-section-title">Hal-hal yang wajar Anda khawatirkan.</h2>
            <p class="landing-subtext mx-auto">Bukan basa-basi. Berikut jawaban jujur untuk pertanyaan yang sebenarnya paling penting.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-eye-off"></i></div>
                    <h3 class="h5 mb-2">Apakah bisnis lain bisa melihat data saya?</h3>
                    <p class="text-muted small mb-0">Tidak. Setiap workspace benar-benar terisolasi satu sama lain di level sistem. Percakapan, kontak, dan data pelanggan Anda hanya bisa diakses oleh pengguna di workspace Anda sendiri — tidak ada yang lain, termasuk kami, kecuali Anda mengizinkan.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-map-pin"></i></div>
                    <h3 class="h5 mb-2">Data saya disimpan di mana?</h3>
                    <p class="text-muted small mb-0">Seluruh data tersimpan di Indonesia — di atas infrastruktur <strong>Biznet Gio</strong>, penyedia cloud lokal Tier-1 yang digunakan banyak perusahaan besar Indonesia. Database kami menggunakan <strong>PostgreSQL</strong> managed via Supabase dengan replikasi otomatis dan backup harian.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-database-off"></i></div>
                    <h3 class="h5 mb-2">Kalau saya berhenti berlangganan, data saya hilang?</h3>
                    <p class="text-muted small mb-0">Data Anda tidak langsung dihapus. Kami memberikan masa grace period setelah langganan berakhir. Dalam masa itu Anda bisa menghubungi tim kami untuk mengekspor data sebelum workspace dinonaktifkan. Data adalah milik Anda — kami hanya menyimpannya.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-spy"></i></div>
                    <h3 class="h5 mb-2">Apakah tim Meetra bisa membaca percakapan saya?</h3>
                    <p class="text-muted small mb-0">Secara teknis, akses ke data produksi sangat dibatasi dan hanya untuk keperluan dukungan teknis bila Anda memintanya. Kami tidak memantau isi percakapan secara aktif. Tidak ada anggota tim yang mengakses data workspace tanpa alasan yang jelas dan tercatat.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-whisk"></i></div>
                    <h3 class="h5 mb-2">Apakah data saya dipakai untuk melatih AI?</h3>
                    <p class="text-muted small mb-0">Tidak. Data percakapan di workspace Anda tidak kami gunakan untuk melatih model AI kami atau pihak ketiga manapun. Fitur chatbot AI di Meetra menggunakan provider AI eksternal (seperti OpenAI atau Anthropic) dengan API key yang Anda pasang sendiri — data dikirim langsung ke provider pilihan Anda, bukan melalui sistem kami sebagai perantara penyimpanan.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-qa-card p-4 h-100">
                    <div class="security-qa-icon mb-3"><i class="ti ti-alert-triangle"></i></div>
                    <h3 class="h5 mb-2">Kalau ada masalah atau kebocoran data, saya akan tahu?</h3>
                    <p class="text-muted small mb-0">Ya. Jika terjadi insiden yang berdampak pada data Anda, kami berkomitmen untuk memberi tahu Anda sesegera mungkin — dengan penjelasan yang jelas, bukan pernyataan PR yang abu-abu. Anda berhak tahu apa yang terjadi dengan data Anda.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ INFRASTRUCTURE ══════════════════════════════════════ --}}
<section class="py-5 py-lg-6" style="background: #f8fafc; border-top: 1px solid var(--landing-line); border-bottom: 1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Infrastruktur</div>
            <h2 class="landing-section-title">Dibangun di atas teknologi yang sudah terbukti.</h2>
            <p class="landing-subtext mx-auto">Kami tidak membangun data center sendiri — kami menggunakan infrastruktur terbaik yang tersedia agar Anda tidak perlu khawatir soal uptime dan keandalan.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="security-infra-card p-4 h-100 text-center">
                    <div class="security-infra-icon mx-auto mb-3"><i class="ti ti-server-2"></i></div>
                    <div class="fw-bold mb-1">Biznet Gio Cloud</div>
                    <div class="security-infra-label mb-3">Server & Jaringan</div>
                    <p class="text-muted small mb-0">Infrastruktur server kami berjalan di Biznet Gio, cloud provider lokal Tier-1 Indonesia dengan data center bersertifikat, redundansi jaringan, dan perlindungan DDoS aktif.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-infra-card p-4 h-100 text-center">
                    <div class="security-infra-icon mx-auto mb-3"><i class="ti ti-database"></i></div>
                    <div class="fw-bold mb-1">Supabase + PostgreSQL</div>
                    <div class="security-infra-label mb-3">Database</div>
                    <p class="text-muted small mb-0">Database menggunakan PostgreSQL yang dikelola oleh Supabase — platform database enterprise-grade dengan replikasi otomatis, point-in-time recovery, dan backup harian yang tersimpan aman.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="security-infra-card p-4 h-100 text-center">
                    <div class="security-infra-icon mx-auto mb-3"><i class="ti ti-lock"></i></div>
                    <div class="fw-bold mb-1">Enkripsi TLS</div>
                    <div class="security-infra-label mb-3">Keamanan Koneksi</div>
                    <p class="text-muted small mb-0">Semua koneksi antara browser Anda dan server kami dienkripsi menggunakan TLS. Data tidak pernah berpindah dalam kondisi tidak terenkripsi, baik dari sisi klien maupun antar-layanan internal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ KOMITMEN ═════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Komitmen kami</div>
                <h2 class="landing-section-title mb-3">Yang kami janjikan dan pegang.</h2>
                <p class="landing-subtext">Kami platform kecil yang tumbuh bersama bisnis-bisnis yang mempercayai kami. Reputasi kami bergantung pada kepercayaan Anda — bukan sekadar slogan.</p>
            </div>
            <div class="col-lg-7">
                <div class="security-commit-list">
                    <div class="security-commit-item">
                        <div class="security-commit-dot"></div>
                        <div>
                            <div class="fw-semibold mb-1">Data Anda tidak dijual atau dipinjamkan ke siapapun</div>
                            <div class="text-muted small">Kami tidak memiliki bisnis iklan atau data brokering. Satu-satunya pendapatan kami adalah dari langganan Anda.</div>
                        </div>
                    </div>
                    <div class="security-commit-item">
                        <div class="security-commit-dot"></div>
                        <div>
                            <div class="fw-semibold mb-1">Password Anda tidak bisa dibaca siapapun, termasuk kami</div>
                            <div class="text-muted small">Password disimpan dalam bentuk hash bcrypt — sistem satu arah yang tidak bisa dibalik. Tim kami tidak bisa melihat password Anda.</div>
                        </div>
                    </div>
                    <div class="security-commit-item">
                        <div class="security-commit-dot"></div>
                        <div>
                            <div class="fw-semibold mb-1">Backup otomatis setiap hari</div>
                            <div class="text-muted small">Data di-backup setiap hari secara otomatis. Riwayat backup disimpan sehingga data bisa dipulihkan ke titik waktu tertentu bila diperlukan.</div>
                        </div>
                    </div>
                    <div class="security-commit-item">
                        <div class="security-commit-dot"></div>
                        <div>
                            <div class="fw-semibold mb-1">Akses internal sangat dibatasi</div>
                            <div class="text-muted small">Tidak ada anggota tim Meetra yang memiliki akses bebas ke data produksi. Semua akses ke sistem dicatat dan dibatasi sesuai kebutuhan tugasnya.</div>
                        </div>
                    </div>
                    <div class="security-commit-item">
                        <div class="security-commit-dot"></div>
                        <div>
                            <div class="fw-semibold mb-1">Notifikasi jujur bila terjadi insiden</div>
                            <div class="text-muted small">Bila ada insiden yang berdampak pada data Anda, kami akan memberi tahu Anda dengan cepat, jelas, dan jujur tentang apa yang terjadi dan apa yang kami lakukan.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CONTACT ══════════════════════════════════════════════ --}}
<section class="py-5" style="background: #f8fafc; border-top: 1px solid var(--landing-line);">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 rounded-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="landing-eyebrow mb-2">Ada pertanyaan?</div>
                    <h2 class="h4 mb-2">Hubungi kami langsung.</h2>
                    <p class="text-muted mb-0">Jika Anda punya pertanyaan soal keamanan data, ingin tahu lebih detail tentang cara kami mengelola data Anda, atau menemukan potensi masalah — kami siap menjawab dalam waktu 24 jam.</p>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-column gap-3">
                        <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="security-contact-btn">
                            <span class="security-contact-btn-icon"><i class="ti ti-brand-whatsapp"></i></span>
                            <div>
                                <div class="fw-semibold">WhatsApp</div>
                                <div class="small text-muted">+62 812-222-9815 · Respons cepat</div>
                            </div>
                        </a>
                        <a href="mailto:support@meetra.id" class="security-contact-btn">
                            <span class="security-contact-btn-icon"><i class="ti ti-mail"></i></span>
                            <div>
                                <div class="fw-semibold">Email</div>
                                <div class="small text-muted">support@meetra.id · Maks. 24 jam</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CTA ══════════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-cta-band p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h2 class="landing-section-title text-white mb-2">Siap mulai dengan platform yang bisa dipercaya?</h2>
                    <p class="mb-0 opacity-75">Ribuan percakapan dikelola setiap hari oleh tim bisnis yang mempercayakan datanya ke Meetra.</p>
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
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat &amp; Ketentuan</a>
                </nav>
            </div>
            <div class="col-lg-4">
                <div class="landing-footer-heading">Infrastruktur &amp; Keamanan</div>
                <div class="landing-footer-trust-cards">
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-server-2"></i>
                        <div>
                            <div class="fw-semibold">Server di Indonesia</div>
                            <div>Biznet Gio Cloud · Data center lokal Tier-1.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div>
                            <div class="fw-semibold">Koneksi Terenkripsi</div>
                            <div>TLS aktif di semua koneksi. Data tidak berpindah tanpa enkripsi.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div>
                            <div class="fw-semibold">Database PostgreSQL</div>
                            <div>Supabase managed · Backup harian otomatis.</div>
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
