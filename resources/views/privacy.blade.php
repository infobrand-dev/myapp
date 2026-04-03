<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi - {{ config('app.name') }}</title>
    <meta name="description" content="Kebijakan privasi {{ config('app.name') }} - bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi data Anda.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="landing-page">
<div class="landing-shell">

<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="{{ route('landing') }}#solutions" class="landing-nav-link">Fitur</a>
                <a href="{{ route('landing') }}#pricing" class="landing-nav-link">Harga</a>
                <a href="{{ route('landing') }}#faq" class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('onboarding.create') }}" class="btn btn-dark btn-sm">Daftar Gratis</a>
            </div>
        </div>
    </div>
</header>

<main>
<section class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #eef1ff 100%); border-bottom: 1px solid var(--landing-line);">
    <div class="container py-3">
        <div class="legal-page-header">
            <div class="landing-badge mb-3"><i class="ti ti-file-description"></i> Dokumen Legal</div>
            <h1 class="landing-headline mb-3">Kebijakan Privasi</h1>
            <p class="landing-subtext mb-3">Terakhir diperbarui: <strong>{{ date('d F Y') }}</strong></p>
            <p class="landing-subtext mb-0">Dokumen ini menjelaskan data apa yang kami kumpulkan, bagaimana kami menggunakannya, dan langkah yang kami ambil untuk melindunginya.</p>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="legal-toc mb-5">
                    <div class="legal-toc-title">Daftar Isi</div>
                    <ol class="legal-toc-list">
                        <li><a href="#p1">Data yang Kami Kumpulkan</a></li>
                        <li><a href="#p2">Cara Kami Menggunakan Data</a></li>
                        <li><a href="#p3">Penyimpanan dan Keamanan Data</a></li>
                        <li><a href="#p4">Berbagi Data dengan Pihak Ketiga</a></li>
                        <li><a href="#p5">Hak Anda atas Data</a></li>
                        <li><a href="#p6">Cookie dan Teknologi Serupa</a></li>
                        <li><a href="#p7">Perubahan Kebijakan</a></li>
                        <li><a href="#p8">Hubungi Kami</a></li>
                    </ol>
                </div>

                <div class="legal-body">
                    <div class="legal-intro mb-5">
                        <p>{{ config('app.name') }} ("kami", "platform") berkomitmen untuk menjaga privasi pengguna. Kebijakan ini berlaku untuk penggunaan website, aplikasi web, fitur berlangganan, integrasi pihak ketiga, dan layanan lain yang kami sediakan.</p>
                        <p class="mb-0">Dengan menggunakan layanan kami, Anda menyetujui praktik yang dijelaskan dalam kebijakan ini.</p>
                    </div>

                    <div id="p1" class="legal-section">
                        <h2><span class="legal-section-num">1</span> Data yang Kami Kumpulkan</h2>

                        <h3>Data yang Anda berikan langsung</h3>
                        <ul>
                            <li>Nama, alamat email, nomor telepon, atau informasi profil lain saat Anda mendaftar atau menggunakan layanan</li>
                            <li>Informasi bisnis, nama workspace, subdomain, dan pengaturan akun</li>
                            <li>Informasi penagihan dan langganan yang diperlukan untuk memproses pembayaran</li>
                            <li>Data, file, konfigurasi, dan konten yang Anda masukkan atau unggah ke platform</li>
                        </ul>

                        <h3>Data yang dikumpulkan secara otomatis</h3>
                        <ul>
                            <li>Alamat IP, browser, perangkat, sistem operasi, dan waktu akses</li>
                            <li>Log aktivitas akun, login, perubahan pengaturan, dan penggunaan fitur</li>
                            <li>Informasi teknis yang diperlukan untuk keamanan, troubleshooting, dan peningkatan layanan</li>
                        </ul>

                        <h3>Data dari integrasi pihak ketiga</h3>
                        <p>Jika Anda menghubungkan layanan pihak ketiga seperti platform sosial, komunikasi, autentikasi, pembayaran, atau layanan lain, kami dapat menerima data akun, metadata integrasi, token akses, dan data operasional yang diperlukan untuk menjalankan integrasi tersebut.</p>
                    </div>

                    <div id="p2" class="legal-section">
                        <h2><span class="legal-section-num">2</span> Cara Kami Menggunakan Data</h2>
                        <p>Kami menggunakan data yang dikumpulkan untuk:</p>
                        <ul>
                            <li>Menyediakan, mengoperasikan, dan memelihara layanan sesuai paket yang Anda pilih</li>
                            <li>Mengelola akun, akses pengguna, langganan, dan penagihan</li>
                            <li>Menyediakan dukungan teknis, notifikasi sistem, dan komunikasi layanan</li>
                            <li>Menjalankan integrasi yang Anda aktifkan dengan layanan pihak ketiga</li>
                            <li>Meningkatkan keandalan, keamanan, performa, dan kualitas layanan</li>
                            <li>Memenuhi kewajiban hukum, audit, dan kepatuhan yang berlaku</li>
                        </ul>
                        <div class="legal-callout legal-callout-green">
                            <i class="ti ti-shield-check"></i>
                            <div>Kami tidak menjual data Anda dan tidak menggunakan data workspace Anda untuk iklan atau pelatihan model kami tanpa dasar hukum dan persetujuan yang jelas.</div>
                        </div>
                    </div>

                    <div id="p3" class="legal-section">
                        <h2><span class="legal-section-num">3</span> Penyimpanan dan Keamanan Data</h2>
                        <p>Data disimpan menggunakan infrastruktur cloud dan layanan teknis yang kami pilih secara komersial, dengan pengamanan seperti enkripsi koneksi, kontrol akses, backup, logging, dan langkah teknis yang wajar sesuai kebutuhan layanan.</p>
                        <p>Data antar workspace diisolasi pada level aplikasi dan sistem untuk mencegah akses tidak sah antar pengguna atau antar tenant.</p>
                        <p>Untuk detail tambahan, lihat juga <a href="{{ route('security') }}">halaman Keamanan Data</a>.</p>
                    </div>

                    <div id="p4" class="legal-section">
                        <h2><span class="legal-section-num">4</span> Berbagi Data dengan Pihak Ketiga</h2>
                        <p>Kami tidak menjual atau menyewakan data Anda. Kami hanya membagikan data dalam kondisi berikut:</p>
                        <ul>
                            <li><strong>Penyedia infrastruktur</strong> - server, database, email, storage, jaringan, atau layanan teknis lain yang membantu kami menjalankan platform</li>
                            <li><strong>Penyedia pembayaran</strong> - untuk memproses transaksi, invoice, dan langganan</li>
                            <li><strong>Penyedia integrasi pihak ketiga</strong> - jika Anda menghubungkan akun atau layanan luar ke platform kami, data yang diperlukan dapat diteruskan untuk menjalankan fungsi integrasi tersebut</li>
                            <li><strong>Kewajiban hukum</strong> - jika diwajibkan oleh hukum, regulator, atau permintaan resmi yang sah</li>
                        </ul>
                    </div>

                    <div id="p5" class="legal-section">
                        <h2><span class="legal-section-num">5</span> Hak Anda atas Data</h2>
                        <p>Anda dapat meminta untuk:</p>
                        <ul>
                            <li>Mengakses data yang terkait dengan akun Anda</li>
                            <li>Memperbarui atau memperbaiki informasi akun</li>
                            <li>Mengekspor data yang tersedia dari workspace Anda</li>
                            <li>Menghapus akun atau data tertentu, sesuai kewajiban hukum dan operasional yang berlaku</li>
                            <li>Menyampaikan keberatan atau pertanyaan terkait cara kami memproses data</li>
                        </ul>
                        <p>Permintaan terkait privasi dapat dikirim ke <a href="mailto:support@meetra.id">support@meetra.id</a>.</p>
                    </div>

                    <div id="p6" class="legal-section">
                        <h2><span class="legal-section-num">6</span> Cookie dan Teknologi Serupa</h2>
                        <p>Kami menggunakan cookie dan teknologi serupa untuk menjalankan fungsi dasar platform, seperti:</p>
                        <ul>
                            <li>Menjaga sesi login dan autentikasi</li>
                            <li>Meningkatkan keamanan dan mencegah penyalahgunaan</li>
                            <li>Menyimpan preferensi dasar penggunaan</li>
                        </ul>
                        <p>Kami tidak menjadikan cookie iklan pihak ketiga sebagai bagian default dari layanan inti platform.</p>
                    </div>

                    <div id="p7" class="legal-section">
                        <h2><span class="legal-section-num">7</span> Perubahan Kebijakan</h2>
                        <p>Kami dapat memperbarui kebijakan ini dari waktu ke waktu. Jika ada perubahan material, kami dapat memberi tahu Anda melalui email, notifikasi di dalam platform, atau pembaruan pada halaman ini.</p>
                    </div>

                    <div id="p8" class="legal-section">
                        <h2><span class="legal-section-num">8</span> Hubungi Kami</h2>
                        <p>Jika Anda memiliki pertanyaan tentang kebijakan ini atau cara kami menangani data, hubungi kami melalui:</p>
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
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<footer class="landing-footer">
    <div class="container">
        <div class="landing-footer-inner row g-5">
            <div class="col-lg-4">
                <div class="mb-3"><x-app-logo variant="default" :height="30" /></div>
                <p class="landing-footer-tagline mb-4">Platform bisnis untuk mengelola data, komunikasi, otomasi, dan operasional workspace dalam satu sistem.</p>
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
                        <div><div class="fw-semibold">Infrastruktur Cloud</div><div>Layanan berjalan di infrastruktur cloud yang dikelola secara komersial.</div></div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div><div class="fw-semibold">Koneksi Terenkripsi</div><div>TLS aktif di koneksi layanan dan isolasi data per workspace.</div></div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div><div class="fw-semibold">Data &amp; Backup</div><div>Backup, logging, dan kontrol akses digunakan untuk menjaga layanan tetap andal.</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <div class="landing-footer-copy">&copy; {{ date('Y') }} {{ config('app.name') }}. Hak cipta dilindungi.</div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="landing-footer-trust-pill"><i class="ti ti-shield-check"></i> Data Aman</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-plug-connected"></i> Integrasi Pihak Ketiga</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-credit-card"></i> Billing Tersedia</span>
            </div>
        </div>
    </div>
</footer>

</div>
</body>
</html>
