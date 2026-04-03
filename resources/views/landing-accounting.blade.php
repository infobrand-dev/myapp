@extends('layouts.landing')

@section('head_title', config('app.name') . ' Accounting - Paket Sales, Payments, Purchases, Finance, POS, dan Reports')
@section('head_description', 'Product line Accounting untuk operasional transaksi existing: sales, payments, purchases, finance ringan, point of sale, dan reports dengan tier Starter, Growth, dan Scale.')

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#bundle" class="landing-nav-link">Bundle</a>
                <a href="#tiers" class="landing-nav-link">Paket</a>
                <a href="#notes" class="landing-nav-link">Catatan</a>
                <a href="#faq" class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('landing') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Omnichannel</a>
                <a href="#tiers" class="btn btn-dark btn-sm">Lihat Paket</a>
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<section class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-report-money"></i> Product Line: Accounting
                </div>
                <h1 class="landing-headline mb-4">
                    Bundle transaksi internal untuk <span>sales sampai reporting</span>.
                </h1>
                <p class="landing-subtext mb-5">
                    Product line ini menggantikan family `commerce` lama di layer plan dan billing. Fase pertama tidak membuka modul akuntansi formal baru, tetapi merapikan paket existing menjadi tiga tier: Starter, Growth, dan Scale.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="#bundle" class="btn btn-lg btn-dark">Lihat Bundle</a>
                    <a href="#tiers" class="btn btn-lg btn-outline-dark">Lihat Tier</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill"><i class="ti ti-shopping-cart"></i> Sales</span>
                    <span class="landing-pill"><i class="ti ti-credit-card"></i> Payments</span>
                    <span class="landing-pill"><i class="ti ti-package"></i> Purchases</span>
                    <span class="landing-pill"><i class="ti ti-cash"></i> Finance</span>
                    <span class="landing-pill"><i class="ti ti-device-desktop"></i> POS</span>
                    <span class="landing-pill"><i class="ti ti-chart-bar"></i> Reports</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel landing-hero-card p-4 p-lg-5">
                    <div class="mb-4">
                        <div class="text-uppercase text-muted small fw-bold mb-1">Fase pertama</div>
                        <div class="fw-bold fs-4 lh-sm">Rename product line, siapkan harga, dan jaga kompatibilitas data lama.</div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-blue);">3</div>
                                <div class="small text-muted">Tier utama</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-teal);">6</div>
                                <div class="small text-muted">Module inti existing</div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> `commerce` di-bundle ulang menjadi `accounting` pada plan dan billing</div>
                        <div><i class="ti ti-check text-success"></i> Semua tier membawa core bundle yang sama</div>
                        <div><i class="ti ti-check text-success"></i> Pembeda tier fokus ke limit dan kapasitas</div>
                        <div><i class="ti ti-check text-success"></i> Onboarding public tetap omnichannel-only</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="bundle" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Bundle Inti</div>
            <h2 class="landing-section-title">Modul existing yang dijual di product line Accounting.</h2>
            <p class="landing-subtext mx-auto">Fase pertama tidak menunggu modul baru. Bundle ini memakai capability yang sudah ada di repo dan sudah masuk alur transaksi harian.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-shopping-cart"></i></div>
                    <h3 class="h5 mb-2">Sales</h3>
                    <p class="text-muted small mb-0">Transaksi penjualan, retur, dan workflow operasional penjualan yang sudah ada di sistem.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-credit-card"></i></div>
                    <h3 class="h5 mb-2">Payments</h3>
                    <p class="text-muted small mb-0">Pencatatan payment dan alokasi pembayaran lintas transaksi existing.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-package"></i></div>
                    <h3 class="h5 mb-2">Purchases</h3>
                    <p class="text-muted small mb-0">Draft, finalize, receiving, dan pembelian supplier yang sudah berjalan di modul existing.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-cash"></i></div>
                    <h3 class="h5 mb-2">Finance</h3>
                    <p class="text-muted small mb-0">Cashflow operasional ringan untuk kas masuk dan kas keluar non-sales.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-device-desktop"></i></div>
                    <h3 class="h5 mb-2">Point of Sale</h3>
                    <p class="text-muted small mb-0">Checkout kasir, cash session, dan alur operasional outlet yang sudah ada.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-chart-bar"></i></div>
                    <h3 class="h5 mb-2">Reports</h3>
                    <p class="text-muted small mb-0">Layer reporting read-only untuk membaca data transaksi yang sudah aktif di bundle ini.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="tiers" class="py-5" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Tier Struktur</div>
            <h2 class="landing-section-title">Starter, Growth, dan Scale dengan core bundle yang sama.</h2>
            <p class="landing-subtext mx-auto">Perbedaan paket ada pada kapasitas users, branches, storage, products, dan contacts. Fase pertama tidak memakai unlock module antar tier.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="landing-plan-card p-4 h-100">
                    <div class="h3 mb-2 fw-800">Accounting Starter</div>
                    <div class="text-muted small mb-4">Untuk tim awal yang baru merapikan operasional transaksi.</div>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> 1 company, 1 branch, 5 users</div>
                        <div><i class="ti ti-check text-success"></i> Storage awal 1 GB</div>
                        <div><i class="ti ti-check text-success"></i> Bundle inti sales, payments, purchases, finance, POS, reports</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="landing-plan-card p-4 h-100 featured">
                    <div class="landing-plan-popular">Paket rekomendasi</div>
                    <div class="h3 mb-2 fw-800">Accounting Growth</div>
                    <div class="text-muted small mb-4">Untuk tim yang sudah aktif menangani transaksi harian dan butuh kapasitas lebih longgar.</div>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> 1 company, 3 branches, 15 users</div>
                        <div><i class="ti ti-check text-success"></i> Storage 5 GB</div>
                        <div><i class="ti ti-check text-success"></i> Core bundle tetap sama, limit operasional naik</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="landing-plan-card p-4 h-100">
                    <div class="h3 mb-2 fw-800">Accounting Scale</div>
                    <div class="text-muted small mb-4">Untuk operasional multi-user dan multi-branch yang lebih padat.</div>
                    <div class="landing-checklist small text-muted">
                        <div><i class="ti ti-check text-success"></i> 3 companies, 10 branches, 50 users</div>
                        <div><i class="ti ti-check text-success"></i> Storage 20 GB</div>
                        <div><i class="ti ti-check text-success"></i> Kapasitas produk dan kontak jauh lebih besar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="notes" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Catatan Teknis</div>
                    <h2 class="landing-section-title mb-3">Core bundle dijual dulu, dependency dijaga tetap aman.</h2>
                    <div class="landing-checklist text-muted">
                        <div><i class="ti ti-check text-success"></i> `products`, `inventory`, `contacts`, dan `discounts` bisa tetap ikut sebagai dependency teknis bila runtime membutuhkannya</div>
                        <div><i class="ti ti-check text-success"></i> Dependency teknis tidak dijadikan pesan utama pricing</div>
                        <div><i class="ti ti-check text-success"></i> Rename family plan tidak boleh memutus tenant lama yang masih membaca `commerce` di data historis</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Catatan Produk</div>
                    <h2 class="landing-section-title mb-3">Belum menjual akuntansi formal penuh.</h2>
                    <div class="landing-checklist text-muted">
                        <div><i class="ti ti-check text-success"></i> Jangan klaim COA, ledger, atau closing formal bila modulnya belum ada</div>
                        <div><i class="ti ti-check text-success"></i> Jangan buka self-serve checkout accounting di fase ini</div>
                        <div><i class="ti ti-check text-success"></i> Landing ini hanya untuk positioning sampai katalog public dibuka</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan dasar untuk fase pertama Accounting.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah ini modul baru?</h3>
                    <p class="text-muted small mb-0">Bukan. `accounting` adalah product line dan bundle pricing untuk modul existing yang sudah ada di repo.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah ini menggantikan commerce lama?</h3>
                    <p class="text-muted small mb-0">Ya, di layer plan dan billing family `commerce` digeser menjadi `accounting` dengan tetap menjaga kompatibilitas data lama.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah tiap tier membuka modul berbeda?</h3>
                    <p class="text-muted small mb-0">Tidak pada fase pertama. Semua tier membawa core bundle yang sama, pembeda utamanya ada di limit dan kapasitas.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah sudah dijual publik?</h3>
                    <p class="text-muted small mb-0">Belum. Onboarding public tetap fokus ke Omnichannel, sedangkan Accounting disiapkan lebih dulu di control plane dan pricing internal.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
