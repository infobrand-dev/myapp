@extends('layouts.landing')

@section('head_title', config('app.name') . ' Accounting - Akuntansi, Closing, dan Integrasi Software Keuangan')
@section('head_description', 'Lini produk accounting untuk pembukuan formal, buku besar, rekonsiliasi, laporan keuangan, dan integrasi ke Accurate, Zahir, Jurnal, serta software akuntansi lain.')

@section('topbar')
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#modules" class="landing-nav-link">Modul</a>
                <a href="#roadmap" class="landing-nav-link">Roadmap</a>
                <a href="#integrations" class="landing-nav-link">Integrasi</a>
                <a href="#faq" class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('landing') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Omnichannel</a>
                <a href="#cta" class="btn btn-dark btn-sm">Buka Plan</a>
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
                    <i class="ti ti-building-bank"></i> Product Line Baru: Accounting
                </div>
                <h1 class="landing-headline mb-4">
                    Dari transaksi operasional ke <span>pembukuan yang rapi</span>.
                </h1>
                <p class="landing-subtext mb-5">
                    Section ini disiapkan untuk bisnis yang sudah jalan dan butuh akuntansi formal: COA, jurnal, buku besar, piutang, hutang, rekonsiliasi bank, laporan keuangan, sampai integrasi ke Accurate, Zahir, dan Jurnal.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="#modules" class="btn btn-lg btn-dark">Lihat Modul</a>
                    <a href="#roadmap" class="btn btn-lg btn-outline-dark">Lihat Roadmap</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill"><i class="ti ti-book-2"></i> General Ledger</span>
                    <span class="landing-pill"><i class="ti ti-scale"></i> Trial Balance</span>
                    <span class="landing-pill"><i class="ti ti-receipt-tax"></i> Tax Layer</span>
                    <span class="landing-pill"><i class="ti ti-arrows-exchange"></i> External Sync</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel landing-hero-card p-4 p-lg-5">
                    <div class="mb-4">
                        <div class="text-uppercase text-muted small fw-bold mb-1">Apa yang dibuka</div>
                        <div class="fw-bold fs-4 lh-sm">Accounting berdiri terpisah dari commerce, tetapi tetap terhubung.</div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-blue);">8</div>
                                <div class="small text-muted">Module scope awal</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-teal);">3</div>
                                <div class="small text-muted">Target adapter integrasi</div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> `commerce` tetap fokus ke transaksi operasional</div>
                        <div><i class="ti ti-check text-success"></i> `finance` tetap ringan, tidak dipaksa jadi ledger formal</div>
                        <div><i class="ti ti-check text-success"></i> `accounting` fokus ke closing, auditability, dan laporan keuangan</div>
                        <div><i class="ti ti-check text-success"></i> Integrasi external diposisikan sebagai adapter, bukan source of truth transaksi operasional</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <div class="landing-trust-strip">
            <div class="landing-trust-item"><i class="ti ti-package"></i><div class="small">Dipisah dari commerce</div></div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-building-bank"></i><div class="small">Siap multi-company</div></div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-file-invoice"></i><div class="small">Piutang & hutang formal</div></div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-report-money"></i><div class="small">P&amp;L, Neraca, Arus Kas</div></div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item"><i class="ti ti-link"></i><div class="small">Adapter ke Accurate, Zahir, Jurnal</div></div>
        </div>
    </div>
</section>

<section id="modules" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Module Scope</div>
            <h2 class="landing-section-title">Modul yang masuk ke section accounting.</h2>
            <p class="landing-subtext mx-auto">Strukturnya dipisah per domain akuntansi supaya activation, dependency, dan rollout tetap rapi.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-book-2"></i></div>
                    <h3 class="h5 mb-2">Accounting Core</h3>
                    <p class="text-muted small mb-0">COA, fiscal period, jurnal manual, ledger, trial balance, dan lock period sebagai fondasi pembukuan formal.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-receipt-2"></i></div>
                    <h3 class="h5 mb-2">Receivables</h3>
                    <p class="text-muted small mb-0">Invoice customer, aging piutang, allocation pembayaran, dan statement account.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-file-dollar"></i></div>
                    <h3 class="h5 mb-2">Payables</h3>
                    <p class="text-muted small mb-0">Vendor bill, aging hutang, allocation pembayaran, dan adjustment dasar vendor.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-building-bank"></i></div>
                    <h3 class="h5 mb-2">Cash &amp; Bank</h3>
                    <p class="text-muted small mb-0">Register kas/bank, transfer antar akun, mutasi, dan bank reconciliation.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-building-warehouse"></i></div>
                    <h3 class="h5 mb-2">Fixed Assets</h3>
                    <p class="text-muted small mb-0">Register aset, perolehan, depresiasi, disposal, dan write-off dasar.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-receipt-tax"></i></div>
                    <h3 class="h5 mb-2">Tax Layer</h3>
                    <p class="text-muted small mb-0">Tax code, mapping transaksi, summary export-ready, dan perluasan pajak bertahap tanpa hardcode berlebih.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-report-analytics"></i></div>
                    <h3 class="h5 mb-2">Accounting Reports</h3>
                    <p class="text-muted small mb-0">Laba rugi, neraca, arus kas, journal report, ledger report, dan aging report.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-arrows-exchange"></i></div>
                    <h3 class="h5 mb-2">Integrations</h3>
                    <p class="text-muted small mb-0">Mapping account, job sync, export/import queue, error log, dan adapter provider-specific.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background:#f8fafc; border-top:1px solid var(--landing-line); border-bottom:1px solid var(--landing-line);">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Boundary</div>
                    <h2 class="landing-section-title mb-3">Yang tetap ada di commerce.</h2>
                    <div class="landing-checklist text-muted">
                        <div><i class="ti ti-check text-success"></i> Products, inventory, discounts, sales, purchases, payments, point-of-sale</div>
                        <div><i class="ti ti-check text-success"></i> Transaksi operasional tetap dibuat di modul asalnya</div>
                        <div><i class="ti ti-check text-success"></i> Accounting menerima posting dan reconciliation, bukan menduplikasi source transaksi</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel p-4 h-100">
                    <div class="landing-eyebrow mb-2">Positioning</div>
                    <h2 class="landing-section-title mb-3">Yang dibuka di accounting.</h2>
                    <div class="landing-checklist text-muted">
                        <div><i class="ti ti-check text-success"></i> Closing period, jurnal, ledger, trial balance, laporan keuangan</div>
                        <div><i class="ti ti-check text-success"></i> Piutang, hutang, cash/bank, rekonsiliasi, fixed asset</div>
                        <div><i class="ti ti-check text-success"></i> Integrasi ke software akuntansi eksternal sebagai adapter terpisah</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="roadmap" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Rollout Plan</div>
            <h2 class="landing-section-title">Urutan implementasi yang paling aman.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Phase 1</div>
                    <h3 class="h5 mb-2">Accounting foundation</h3>
                    <p class="text-muted small mb-0">Bangun `accounting_core`, `accounting_cashbank`, dan `accounting_reports` lebih dulu supaya tenant sudah punya fondasi jurnal dan laporan.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Phase 2</div>
                    <h3 class="h5 mb-2">Receivables &amp; payables</h3>
                    <p class="text-muted small mb-0">Buka invoice/bill formal, aging, payment allocation, dan statement tanpa menunggu integrasi commerce penuh.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Phase 3</div>
                    <h3 class="h5 mb-2">Posting adapter dari commerce</h3>
                    <p class="text-muted small mb-0">Tambahkan adapter dari `sales`, `purchases`, `payments`, dan `point-of-sale` agar transaksi operasional dapat menghasilkan jurnal yang konsisten.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Phase 4</div>
                    <h3 class="h5 mb-2">External accounting integrations</h3>
                    <p class="text-muted small mb-0">Buka adapter ke Accurate, Zahir, dan Jurnal setelah mapping, queue, observability, dan error handling siap.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="integrations" class="py-5">
    <div class="container">
        <div class="landing-panel rounded-4 p-4 p-lg-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <div class="landing-eyebrow mb-2">Target Integrasi</div>
                    <h2 class="landing-section-title mb-3">Provider yang jadi target awal.</h2>
                    <p class="landing-subtext mb-0">Halaman ini menampilkan target adapter yang akan dibuka. Bukan klaim semua integrasi sudah live di runtime saat ini.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="landing-credit-card p-3 h-100">
                                <div class="landing-credit-icon"><i class="ti ti-building-bank"></i></div>
                                <div class="fw-semibold mb-1">Accurate</div>
                                <div class="text-muted small">Mapping account, customer/vendor, invoice/bill export, dan sync status.</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="landing-credit-card p-3 h-100">
                                <div class="landing-credit-icon"><i class="ti ti-building-store"></i></div>
                                <div class="fw-semibold mb-1">Zahir</div>
                                <div class="text-muted small">Adapter export/import transaksi, mapping COA, dan observability job.</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="landing-credit-card p-3 h-100">
                                <div class="landing-credit-icon"><i class="ti ti-file-invoice"></i></div>
                                <div class="fw-semibold mb-1">Jurnal</div>
                                <div class="text-muted small">Sync transaksi dan laporan dasar melalui layer integrasi yang terisolasi.</div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-credit-note mt-3">
                        <i class="ti ti-info-circle"></i>
                        <div>Setiap integrasi akan dipasang sebagai adapter provider-specific di atas `accounting_integrations`, supaya domain accounting inti tetap bersih dan tidak tergantung ke satu vendor.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan dasar untuk section accounting.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah ini menggantikan commerce?</h3>
                    <p class="text-muted small mb-0">Tidak. `commerce` tetap menjadi domain transaksi operasional. `accounting` dipakai untuk pembukuan formal, closing, dan reporting keuangan.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah finance yang sekarang langsung dipindah?</h3>
                    <p class="text-muted small mb-0">Belum. `finance` saat ini tetap diposisikan sebagai cash flow operasional ringan. Sebagian capability bisa nanti diambil alih bertahap oleh `accounting_cashbank` bila dibutuhkan.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Accurate, Zahir, dan Jurnal sudah live?</h3>
                    <p class="text-muted small mb-0">Belum diasumsikan live. Di plan ini ketiganya diposisikan sebagai target adapter awal dan akan dibuka setelah fondasi accounting inti stabil.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Kenapa tidak semua dijadikan satu modul besar?</h3>
                    <p class="text-muted small mb-0">Karena dependency, activation, migration, dan integrasi akan lebih mudah dikelola jika dipisah per domain: core, AR, AP, cash/bank, reports, dan integrations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="cta" class="py-5">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 rounded-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="landing-eyebrow mb-2">Deliverable</div>
                    <h2 class="landing-section-title mb-2">Plan accounting sudah dibuka sebagai product line baru.</h2>
                    <p class="landing-subtext mb-0">Dokumen detail modul dan rollout disimpan di <code>docs/product/accounting-plan.md</code>. Halaman ini menjadi landing awal untuk positioning, module scope, dan target integrasi.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('landing') }}" class="btn btn-outline-dark btn-lg">Kembali ke Produk Utama</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
