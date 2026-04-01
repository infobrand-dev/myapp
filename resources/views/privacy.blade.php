<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi — {{ config('app.name') }}</title>
    <meta name="description" content="Kebijakan privasi {{ config('app.name') }} — bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.">
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
<section class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #eef1ff 100%); border-bottom: 1px solid var(--landing-line);">
    <div class="container py-3">
        <div class="legal-page-header">
            <div class="landing-badge mb-3"><i class="ti ti-file-description"></i> Dokumen Legal</div>
            <h1 class="landing-headline mb-3">Kebijakan Privasi</h1>
            <p class="landing-subtext mb-3">Terakhir diperbarui: <strong>{{ date('d F Y') }}</strong></p>
            <p class="landing-subtext mb-0">Kami menghormati privasi Anda. Dokumen ini menjelaskan secara jelas data apa yang kami kumpulkan, mengapa, dan bagaimana kami melindunginya.</p>
        </div>
    </div>
</section>

{{-- ══ CONTENT ═════════════════════════════════════════════ --}}
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- Nav --}}
                <div class="legal-toc mb-5">
                    <div class="legal-toc-title">Daftar Isi</div>
                    <ol class="legal-toc-list">
                        <li><a href="#p1">Data yang Kami Kumpulkan</a></li>
                        <li><a href="#p2">Cara Kami Menggunakan Data</a></li>
                        <li><a href="#p3">Penyimpanan & Keamanan Data</a></li>
                        <li><a href="#p4">Berbagi Data dengan Pihak Ketiga</a></li>
                        <li><a href="#p5">Hak Anda atas Data</a></li>
                        <li><a href="#p6">Cookie & Teknologi Pelacakan</a></li>
                        <li><a href="#p7">Perubahan Kebijakan</a></li>
                        <li><a href="#p8">Hubungi Kami</a></li>
                    </ol>
                </div>

                <div class="legal-body">

                    <div class="legal-intro mb-5">
                        <p>{{ config('app.name') }} ("kami", "platform") berkomitmen untuk menjaga privasi pengguna. Kebijakan ini berlaku untuk semua pengguna platform {{ config('app.name') }}, termasuk pemilik workspace (tenant), anggota tim, dan pengunjung website.</p>
                        <p class="mb-0">Dengan mendaftar dan menggunakan layanan kami, Anda menyetujui praktik yang dijelaskan dalam kebijakan ini.</p>
                    </div>

                    <div id="p1" class="legal-section">
                        <h2><span class="legal-section-num">1</span> Data yang Kami Kumpulkan</h2>

                        <h3>Data yang Anda berikan langsung</h3>
                        <ul>
                            <li>Nama lengkap dan alamat email saat mendaftar</li>
                            <li>Nama bisnis dan subdomain workspace</li>
                            <li>Informasi pembayaran (diproses oleh payment gateway — kami tidak menyimpan nomor kartu)</li>
                            <li>Pengaturan dan konfigurasi yang Anda isi di dalam workspace</li>
                        </ul>

                        <h3>Data yang dikumpulkan secara otomatis</h3>
                        <ul>
                            <li>Alamat IP dan informasi browser saat mengakses platform</li>
                            <li>Log aktivitas di dalam workspace (login, perubahan pengaturan, aksi penting)</li>
                            <li>Data penggunaan fitur untuk keperluan peningkatan layanan</li>
                        </ul>

                        <h3>Data percakapan pelanggan Anda</h3>
                        <p>Pesan dan percakapan yang masuk melalui channel yang Anda hubungkan (WhatsApp, sosial media, live chat) disimpan di workspace Anda. Data ini adalah milik Anda sepenuhnya — kami hanya menyimpannya atas permintaan Anda sebagai bagian dari layanan.</p>
                    </div>

                    <div id="p2" class="legal-section">
                        <h2><span class="legal-section-num">2</span> Cara Kami Menggunakan Data</h2>
                        <p>Kami menggunakan data yang dikumpulkan untuk:</p>
                        <ul>
                            <li>Menyediakan dan menjalankan layanan platform sesuai paket yang Anda pilih</li>
                            <li>Memproses pembayaran dan mengelola langganan Anda</li>
                            <li>Mengirimkan notifikasi penting terkait akun, pembayaran, atau perubahan layanan</li>
                            <li>Memberikan dukungan teknis saat Anda membutuhkan bantuan</li>
                            <li>Meningkatkan kualitas platform berdasarkan pola penggunaan secara agregat dan anonim</li>
                            <li>Mematuhi kewajiban hukum yang berlaku</li>
                        </ul>
                        <div class="legal-callout legal-callout-green">
                            <i class="ti ti-shield-check"></i>
                            <div>Kami <strong>tidak</strong> menggunakan data percakapan pelanggan Anda untuk keperluan iklan, pemasaran, atau pelatihan model AI kami.</div>
                        </div>
                    </div>

                    <div id="p3" class="legal-section">
                        <h2><span class="legal-section-num">3</span> Penyimpanan &amp; Keamanan Data</h2>
                        <p>Seluruh data disimpan di server yang berlokasi di Indonesia. Kami menggunakan infrastruktur cloud kelas enterprise dengan enkripsi koneksi TLS, backup harian otomatis, dan proteksi berlapis terhadap ancaman eksternal.</p>
                        <p>Data antar workspace sepenuhnya terisolasi satu sama lain di level sistem — tidak ada workspace lain yang dapat mengakses data Anda.</p>
                        <p>Untuk detail lebih lengkap tentang infrastruktur keamanan kami, lihat <a href="{{ route('security') }}">halaman Keamanan Data</a>.</p>
                    </div>

                    <div id="p4" class="legal-section">
                        <h2><span class="legal-section-num">4</span> Berbagi Data dengan Pihak Ketiga</h2>
                        <p>Kami tidak menjual atau menyewakan data Anda kepada siapapun. Kami hanya berbagi data dalam kondisi berikut:</p>
                        <ul>
                            <li><strong>Penyedia layanan infrastruktur</strong> — server, database, dan jaringan yang kami gunakan untuk menjalankan platform. Mereka hanya memproses data sesuai instruksi kami.</li>
                            <li><strong>Payment gateway</strong> — untuk memproses pembayaran langganan. Kami hanya mengirimkan informasi yang diperlukan untuk transaksi.</li>
                            <li><strong>Provider AI</strong> (jika Anda mengaktifkan fitur Chatbot AI) — pesan dikirim ke provider AI pilihan Anda menggunakan API key yang Anda pasang sendiri. Kami tidak menyimpan atau mengakses percakapan yang dikirim ke provider tersebut.</li>
                            <li><strong>Kewajiban hukum</strong> — jika diwajibkan oleh peraturan atau perintah pengadilan yang berlaku di Indonesia.</li>
                        </ul>
                    </div>

                    <div id="p5" class="legal-section">
                        <h2><span class="legal-section-num">5</span> Hak Anda atas Data</h2>
                        <p>Sebagai pengguna, Anda berhak untuk:</p>
                        <ul>
                            <li><strong>Mengakses</strong> data yang kami simpan tentang Anda</li>
                            <li><strong>Memperbarui</strong> informasi akun kapan saja melalui pengaturan workspace</li>
                            <li><strong>Mengekspor</strong> data percakapan dan kontak dari workspace Anda</li>
                            <li><strong>Menghapus</strong> akun dan data Anda — hubungi kami dan kami akan memproses permintaan penghapusan dalam 30 hari kerja</li>
                            <li><strong>Keberatan</strong> atas pemrosesan data tertentu yang tidak Anda setujui</li>
                        </ul>
                        <p>Untuk menggunakan hak-hak di atas, hubungi kami melalui email <a href="mailto:support@meetra.id">support@meetra.id</a>.</p>
                    </div>

                    <div id="p6" class="legal-section">
                        <h2><span class="legal-section-num">6</span> Cookie &amp; Teknologi Pelacakan</h2>
                        <p>Kami menggunakan cookie yang diperlukan untuk menjalankan platform, termasuk:</p>
                        <ul>
                            <li><strong>Cookie sesi</strong> — untuk menjaga status login Anda</li>
                            <li><strong>Cookie keamanan</strong> — untuk mencegah serangan CSRF</li>
                            <li><strong>Cookie preferensi</strong> — untuk mengingat pengaturan tampilan Anda</li>
                        </ul>
                        <p>Kami tidak menggunakan cookie iklan atau pelacakan pihak ketiga untuk keperluan marketing.</p>
                    </div>

                    <div id="p7" class="legal-section">
                        <h2><span class="legal-section-num">7</span> Perubahan Kebijakan</h2>
                        <p>Kami dapat memperbarui kebijakan privasi ini dari waktu ke waktu. Jika ada perubahan yang signifikan, kami akan memberitahu Anda melalui email yang terdaftar atau notifikasi di dalam platform minimal 14 hari sebelum perubahan berlaku.</p>
                        <p>Penggunaan layanan yang berlanjut setelah perubahan berlaku dianggap sebagai persetujuan terhadap kebijakan yang diperbarui.</p>
                    </div>

                    <div id="p8" class="legal-section">
                        <h2><span class="legal-section-num">8</span> Hubungi Kami</h2>
                        <p>Jika ada pertanyaan tentang kebijakan privasi ini atau cara kami menangani data Anda, jangan ragu menghubungi kami:</p>
                        <div class="legal-contact-grid">
                            <a href="mailto:support@meetra.id" class="legal-contact-item">
                                <i class="ti ti-mail"></i>
                                <div>
                                    <div class="fw-semibold">Email</div>
                                    <div class="text-muted">support@meetra.id</div>
                                </div>
                            </a>
                            <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="legal-contact-item">
                                <i class="ti ti-brand-whatsapp"></i>
                                <div>
                                    <div class="fw-semibold">WhatsApp</div>
                                    <div class="text-muted">+62 812-222-9815</div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>{{-- .legal-body --}}
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
                <div class="mb-3"><x-app-logo variant="default" :height="30" /></div>
                <p class="landing-footer-tagline mb-4">Platform omnichannel untuk tim sales, support, dan marketing — semua percakapan pelanggan dalam satu workspace.</p>
                <div class="landing-footer-contact">
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="landing-footer-contact-item">
                        <i class="ti ti-brand-whatsapp"></i><span>+62 812-222-9815</span>
                        <span class="landing-footer-contact-badge">Chat WhatsApp</span>
                    </a>
                    <a href="mailto:support@meetra.id" class="landing-footer-contact-item">
                        <i class="ti ti-mail"></i><span>support@meetra.id</span>
                    </a>
                    <div class="landing-footer-contact-item">
                        <i class="ti ti-headset"></i><span>Dukungan tersedia 24 jam</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Produk</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('landing') }}#solutions">Fitur</a>
                    <a href="{{ route('landing') }}#pricing">Harga</a>
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
                        <div><div class="fw-semibold">Server di Indonesia</div><div>Data center cloud Tier-1 berlokasi di Indonesia.</div></div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div><div class="fw-semibold">Koneksi Terenkripsi</div><div>TLS aktif di semua koneksi. Isolasi data per workspace.</div></div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div><div class="fw-semibold">Database PostgreSQL</div><div>Managed enterprise · Backup harian otomatis.</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <div class="landing-footer-copy">&copy; {{ date('Y') }} {{ config('app.name') }}. Hak cipta dilindungi.</div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="landing-footer-trust-pill"><i class="ti ti-shield-check"></i> Data Aman</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-brand-whatsapp"></i> WhatsApp API Resmi</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-credit-card"></i> Bayar Lokal</span>
            </div>
        </div>
    </div>
</footer>

</div>
</body>
</html>
