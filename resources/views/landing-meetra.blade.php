@extends('layouts.landing')

@section('head_title', config('app.name') . ' - Platform bisnis untuk operasional, penjualan, customer, dan workflow tim')
@section('head_description', 'Meetra adalah platform bisnis yang menyatukan customer, transaksi, dan workflow tim dalam satu workspace yang lebih tertata.')

@section('content')
<style>
    .meetra-intro-band { position:relative; overflow:hidden; background:linear-gradient(180deg, rgba(255,255,255,0.96) 0%, rgba(240,247,255,0.92) 100%); border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line); }
    .meetra-intro-band::before { content:""; position:absolute; inset:auto auto -80px -80px; width:240px; height:240px; border-radius:999px; background:radial-gradient(circle, rgba(37,99,235,0.10), transparent 70%); }
    .meetra-intro-band::after { content:""; position:absolute; inset:20px -60px auto auto; width:220px; height:220px; border-radius:999px; background:radial-gradient(circle, rgba(20,184,166,0.10), transparent 70%); }
    .meetra-intro-card, .meetra-story-card, .meetra-product-card { position:relative; border:1px solid rgba(15,23,42,.08); border-radius:24px; background:#fff; padding:1.5rem; height:100%; box-shadow:0 18px 42px rgba(15,23,42,.05); }
    .meetra-intro-icon, .meetra-product-icon { width:52px; height:52px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#eff6ff,#dbeafe); color:#1d4ed8; margin-bottom:1rem; }
    .meetra-intro-icon i, .meetra-product-icon i { font-size:1.35rem; }
    .meetra-why-icon { width:52px; height:52px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:#eff6ff; color:#1d4ed8; margin-bottom:1rem; }
    .meetra-why-icon i { font-size:1.4rem; }
</style>

<section class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-layout-dashboard"></i> Meetra Platform
                </div>
                <h1 class="landing-headline mb-4">
                    <span>Jalankan seluruh ekosistem bisnis Anda</span> dalam satu platform.
                </h1>
                <p class="landing-subtext mb-5">
                    Meetra membantu bisnis menyatukan customer, transaksi, dan workflow tim dalam workspace yang lebih tertata dan mudah dijalankan.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('products') }}" class="btn btn-lg btn-outline-dark">Lihat Product Lines</a>
                    <a href="{{ route('contact') }}" class="btn btn-lg btn-dark">Konsultasikan Kebutuhan</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill">Customer</span>
                    <span class="landing-pill">Transaksi</span>
                    <span class="landing-pill">Operasional</span>
                    <span class="landing-pill">Workflow Tim</span>
                    <span class="landing-pill">Reporting</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="meetra-story-card">
                            <div class="meetra-intro-icon"><i class="ti ti-users-group"></i></div>
                            <div class="small text-uppercase fw-bold text-muted mb-2">Customer</div>
                            <h3 class="h5 mb-2">Interaksi lebih terkelola</h3>
                            <p class="small text-muted mb-0">Riwayat komunikasi, lead, dan kebutuhan customer lebih mudah ditata dalam satu alur kerja.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="meetra-story-card">
                            <div class="meetra-intro-icon"><i class="ti ti-receipt-2"></i></div>
                            <div class="small text-uppercase fw-bold text-muted mb-2">Transaksi</div>
                            <h3 class="h5 mb-2">Proses bisnis lebih jelas</h3>
                            <p class="small text-muted mb-0">Penjualan, pembayaran, pembelian, dan laporan dapat berjalan dalam sistem yang konsisten.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="meetra-story-card">
                            <div class="meetra-intro-icon"><i class="ti ti-arrows-shuffle"></i></div>
                            <div class="small text-uppercase fw-bold text-muted mb-2">Workflow</div>
                            <h3 class="h5 mb-2">Koordinasi tim lebih rapi</h3>
                            <p class="small text-muted mb-0">Progres pekerjaan dan handoff antartim dapat dipantau tanpa proses manual yang berulang.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="meetra-story-card">
                            <div class="meetra-intro-icon"><i class="ti ti-chart-bar"></i></div>
                            <div class="small text-uppercase fw-bold text-muted mb-2">Insight</div>
                            <h3 class="h5 mb-2">Keputusan lebih cepat</h3>
                            <p class="small text-muted mb-0">Informasi penting lebih dekat ke pemilik bisnis dan manajer tanpa rekap yang memakan waktu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6 meetra-intro-band">
    <div class="container">
        <div class="text-center mb-5 position-relative" style="z-index:1;">
            <div class="landing-eyebrow mb-2">Tentang Meetra</div>
            <h2 class="landing-section-title mb-3">Platform bisnis yang membantu tim bekerja lebih terhubung.</h2>
            <p class="landing-subtext mx-auto" style="max-width:780px;">Meetra dirancang untuk bisnis yang ingin merapikan customer, transaksi, dan koordinasi tim tanpa harus bergantung pada banyak alat yang berdiri sendiri.</p>
        </div>
        <div class="row g-4 position-relative" style="z-index:1;">
            <div class="col-lg-4">
                <div class="meetra-intro-card">
                    <div class="meetra-intro-icon">
                        <i class="ti ti-target-arrow"></i>
                    </div>
                    <h3 class="h5 mb-2">Mulai dari kebutuhan utama</h3>
                    <p class="small text-muted mb-0">Implementasi bisa dimulai dari area yang paling relevan bagi operasional bisnis saat ini.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-intro-card">
                    <div class="meetra-intro-icon">
                        <i class="ti ti-link"></i>
                    </div>
                    <h3 class="h5 mb-2">Informasi lebih terhubung</h3>
                    <p class="small text-muted mb-0">Customer, transaksi, dan aktivitas tim berada dalam alur yang lebih selaras dan mudah ditelusuri.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-intro-card">
                    <div class="meetra-intro-icon">
                        <i class="ti ti-rotate-clockwise-2"></i>
                    </div>
                    <h3 class="h5 mb-2">Siap berkembang bersama bisnis</h3>
                    <p class="small text-muted mb-0">Platform yang sama dapat terus dipakai saat kebutuhan operasional dan struktur tim berkembang.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                <div class="landing-eyebrow mb-2">Nilai Utama</div>
                <h2 class="landing-section-title mb-3">Meetra dibangun untuk membuat operasional lebih jelas dan lebih mudah dijalankan.</h2>
                <p class="landing-subtext mb-4">Ketika data customer, transaksi, dan koordinasi tim tersebar di banyak tempat, proses bisnis menjadi lambat dan sulit dipantau. Meetra membantu merapikan alur tersebut dalam satu sistem kerja yang lebih terstruktur.</p>
                <div class="landing-checklist">
                    <div><i class="ti ti-check text-success"></i> Mengurangi ketergantungan pada proses manual yang berulang</div>
                    <div><i class="ti ti-check text-success"></i> Membantu tim bekerja dengan alur yang lebih konsisten</div>
                    <div><i class="ti ti-check text-success"></i> Memudahkan pemantauan aktivitas dan performa operasional</div>
                    <div><i class="ti ti-check text-success"></i> Menyediakan fondasi yang lebih siap untuk pertumbuhan bisnis</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 p-lg-5 h-100">
                    <div class="small text-uppercase fw-bold text-muted mb-3">Cocok untuk</div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="meetra-story-card">
                                <h3 class="h6 mb-2">Retail dan Distribusi</h3>
                                <p class="small text-muted mb-0">Membutuhkan proses transaksi, stok, dan laporan yang lebih tertata.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="meetra-story-card">
                                <h3 class="h6 mb-2">Bisnis Jasa</h3>
                                <p class="small text-muted mb-0">Membutuhkan pengelolaan customer, penagihan, dan koordinasi tim yang lebih rapi.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="meetra-story-card">
                                <h3 class="h6 mb-2">Tim Sales dan Support</h3>
                                <p class="small text-muted mb-0">Membutuhkan histori customer dan tindak lanjut yang lebih terstruktur.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="meetra-story-card">
                                <h3 class="h6 mb-2">Bisnis Bertumbuh</h3>
                                <p class="small text-muted mb-0">Membutuhkan sistem yang lebih siap mendukung skala operasional berikutnya.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Product Lines</div>
            <h2 class="landing-section-title">Meetra menyediakan beberapa jalur solusi sesuai kebutuhan bisnis.</h2>
            <p class="landing-subtext mx-auto" style="max-width:760px;">Setiap product line dirancang untuk menjawab kebutuhan operasional yang berbeda, namun tetap berada dalam ekosistem kerja yang selaras.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-report-money"></i></div>
                    <h3 class="h4 mb-2">Accounting</h3>
                    <p class="small text-muted mb-3">Untuk transaksi, pembayaran, pembelian, stok, dan pelaporan operasional dalam satu alur yang lebih tertata.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Penjualan, pembayaran, dan pembelian</div>
                        <div><i class="ti ti-check text-success"></i> Produk, stok, dan kas operasional</div>
                        <div><i class="ti ti-check text-success"></i> Laporan untuk pemantauan bisnis harian</div>
                    </div>
                    <a href="{{ route('landing.accounting') }}" class="btn btn-outline-dark btn-sm">Lihat Detail</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-message-circle-2"></i></div>
                    <h3 class="h4 mb-2">Omnichannel</h3>
                    <p class="small text-muted mb-3">Untuk pengelolaan percakapan customer dan tindak lanjut lintas channel secara lebih terpusat.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Inbox customer yang lebih terorganisir</div>
                        <div><i class="ti ti-check text-success"></i> Follow up lead dan pertanyaan masuk</div>
                        <div><i class="ti ti-check text-success"></i> Koordinasi tim sales dan support</div>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-checklist"></i></div>
                    <h3 class="h4 mb-2">Productivity</h3>
                    <p class="small text-muted mb-3">Untuk task management, alur kerja tim, dan pemantauan progres pekerjaan yang lebih konsisten.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Tugas, checklist, dan prioritas kerja</div>
                        <div><i class="ti ti-check text-success"></i> Monitoring progres antar fungsi tim</div>
                        <div><i class="ti ti-check text-success"></i> Pengingat dan ritme kerja operasional</div>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-users"></i></div>
                    <h3 class="h4 mb-2">HR & Payroll</h3>
                    <p class="small text-muted mb-3">Untuk administrasi SDM, kehadiran, dan proses payroll yang lebih rapi dan mudah dipantau.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Data karyawan dan administrasi dasar</div>
                        <div><i class="ti ti-check text-success"></i> Kehadiran, cuti, dan kebutuhan HR rutin</div>
                        <div><i class="ti ti-check text-success"></i> Perhitungan payroll yang lebih tertata</div>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-address-book"></i></div>
                    <h3 class="h4 mb-2">CRM</h3>
                    <p class="small text-muted mb-3">Untuk pengelolaan prospek, histori customer, dan pipeline hubungan bisnis yang lebih terukur.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Database prospek dan customer</div>
                        <div><i class="ti ti-check text-success"></i> Histori interaksi dan tindak lanjut</div>
                        <div><i class="ti ti-check text-success"></i> Pipeline untuk peluang penjualan</div>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-card">
                    <div class="meetra-product-icon"><i class="ti ti-speakerphone"></i></div>
                    <h3 class="h4 mb-2">Marketing Automation</h3>
                    <p class="small text-muted mb-3">Untuk campaign, segmentasi audiens, dan automation aktivitas pemasaran yang berjalan lebih konsisten.</p>
                    <div class="landing-checklist small text-muted mb-4">
                        <div><i class="ti ti-check text-success"></i> Segmentasi audiens dan target campaign</div>
                        <div><i class="ti ti-check text-success"></i> Automation untuk nurturing dan follow up</div>
                        <div><i class="ti ti-check text-success"></i> Aktivitas pemasaran yang lebih terukur</div>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Kenapa Meetra</div>
            <h2 class="landing-section-title">Platform yang dirancang agar lebih relevan dengan operasional bisnis sehari-hari.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="meetra-story-card">
                    <div class="meetra-why-icon"><i class="ti ti-layout-grid"></i></div>
                    <h3 class="h5 mb-2">Satu ekosistem kerja</h3>
                    <p class="small text-muted mb-0">Customer, transaksi, dan aktivitas tim dapat berjalan dalam arah yang lebih selaras.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-story-card">
                    <div class="meetra-why-icon"><i class="ti ti-user-heart"></i></div>
                    <h3 class="h5 mb-2">Lebih mudah diadopsi</h3>
                    <p class="small text-muted mb-0">Dirancang agar dapat dipahami tim operasional tanpa proses adaptasi yang terlalu berat.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="meetra-story-card">
                    <div class="meetra-why-icon"><i class="ti ti-building-store"></i></div>
                    <h3 class="h5 mb-2">Konteks lokal yang kuat</h3>
                    <p class="small text-muted mb-0">Bahasa, pembayaran, dan pendekatan support disesuaikan dengan kebutuhan bisnis di Indonesia.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Infrastruktur</div>
                <h2 class="landing-section-title mb-3">Dukungan infrastruktur yang disiapkan untuk operasional bisnis.</h2>
                <p class="landing-subtext mb-4">Meetra berjalan di atas infrastruktur cloud yang mendukung keamanan koneksi, isolasi workspace, dan backup otomatis untuk menjaga kesinambungan operasional.</p>
                <a href="{{ route('security') }}" class="btn btn-outline-dark">
                    <i class="ti ti-shield-check me-1"></i>Pelajari Keamanan Data
                </a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-server-2 mb-3 d-block" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                            <div class="fw-semibold mb-1">Server di Indonesia</div>
                            <div class="small text-muted">Data center berlokasi di Indonesia untuk mendukung kebutuhan bisnis lokal.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-lock mb-3 d-block" style="font-size:1.5rem;color:var(--landing-blue);"></i>
                            <div class="fw-semibold mb-1">Koneksi terenkripsi</div>
                            <div class="small text-muted">Pertukaran data dilindungi dengan koneksi yang terenkripsi dan workspace yang terisolasi.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-database-heart mb-3 d-block" style="font-size:1.5rem;color:var(--landing-teal);"></i>
                            <div class="fw-semibold mb-1">Backup otomatis</div>
                            <div class="small text-muted">Backup berkala membantu menjaga kesiapan data untuk kebutuhan operasional.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="landing-panel rounded-4 p-4">
                            <i class="ti ti-headset mb-3 d-block" style="font-size:1.5rem;color:var(--landing-teal);"></i>
                            <div class="fw-semibold mb-1">Support yang responsif</div>
                            <div class="small text-muted">Tim kami dapat membantu mengarahkan kebutuhan Anda ke skenario penggunaan yang tepat.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">Konsultasi</div>
            <h2 class="landing-section-title mb-3">Diskusikan kebutuhan bisnis Anda bersama tim kami.</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:760px;">Jika Anda sedang menilai product line atau skenario implementasi yang paling sesuai, kami dapat membantu memetakan kebutuhan bisnis Anda terlebih dahulu.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-dark btn-lg">Konsultasikan Sekarang</a>
                <a href="{{ route('products') }}" class="btn btn-outline-dark btn-lg">Lihat Product Lines</a>
            </div>
        </div>
    </div>
</section>
@endsection
