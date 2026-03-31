<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Informasi Partner</title>
    <meta name="description" content="Informasi ringkas aturan referral, atribusi, dan payout partner {{ config('app.name') }}.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="landing-page">
    @php
        $money = app(\App\Support\MoneyFormatter::class);
    @endphp
    <div class="landing-shell">
        <header class="landing-topbar sticky-top">
            <div class="container py-3 d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <x-app-logo variant="default" :height="40" />
                        <div class="text-muted small">Informasi Partner</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('landing') }}" class="btn btn-outline-dark">Kembali ke Landing</a>
                    <a href="mailto:{{ config('mail.from.address', 'hello@' . config('multitenancy.saas_domain', 'app.com')) }}" class="btn btn-dark">Diskusi Kemitraan</a>
                </div>
            </div>
        </header>

        <main>
            <section class="landing-hero py-5">
                <div class="container py-lg-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="landing-badge mb-4">
                                <i class="ti ti-badge-dollar-sign"></i>
                                Informasi partner yang transparan dan bisa diverifikasi
                            </div>
                            <h1 class="landing-headline mb-4">
                                Halaman referensi untuk partner referral <span>{{ config('app.name') }}</span>.
                            </h1>
                            <p class="landing-subtext mb-4">
                                Halaman ini menjelaskan aturan komisi, atribusi referral, payout, dan kondisi referral yang dihitung. Ini bukan halaman pendaftaran umum; kami gunakan saat bekerja sama dengan partner yang sudah kami setujui.
                            </p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('landing') }}" class="btn btn-lg btn-outline-dark">Lihat Produk</a>
                                <a href="mailto:{{ config('mail.from.address', 'hello@' . config('multitenancy.saas_domain', 'app.com')) }}" class="btn btn-lg btn-dark">Hubungi Tim Kami</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="landing-panel p-4 p-lg-5">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Ringkasan Aturan</div>
                                <div class="landing-highlight-list small text-muted">
                                    <div class="mb-2">Status: <strong>Invite only / by approval</strong></div>
                                    <div class="mb-2">
                                        Komisi default:
                                        <strong>
                                            @if($policy['commission_type'] === 'flat')
                                                {{ $money->format($policy['commission_rate'], 'IDR') }}
                                            @else
                                                {{ rtrim(rtrim(number_format($policy['commission_rate'], 2, '.', ''), '0'), '.') }}%
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="mb-2">Cookie attribution: <strong>{{ $policy['cookie_days'] }} hari</strong></div>
                                    <div class="mb-2">Komisi renewal: <strong>{{ $policy['first_purchase_only'] ? 'Tidak dihitung' : 'Mengikuti program aktif' }}</strong></div>
                                    <div class="mb-2">Jadwal payout: <strong>{{ ucfirst($policy['payout_schedule']) }}</strong></div>
                                    <div>Target payout: <strong>Tanggal {{ $policy['payout_day'] }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Komisi</div>
                                <h3 class="h4">Berapa komisi yang dihitung?</h3>
                                <p class="text-muted mb-0">
                                    @if($policy['commission_type'] === 'flat')
                                        Setiap penjualan yang eligible memberi komisi tetap sebesar {{ $money->format($policy['commission_rate'], 'IDR') }}.
                                    @else
                                        Setiap penjualan yang eligible memberi komisi {{ rtrim(rtrim(number_format($policy['commission_rate'], 2, '.', ''), '0'), '.') }}% dari nilai order yang tercatat.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Attribution</div>
                                <h3 class="h4">Berapa lama referral terbaca?</h3>
                                <p class="text-muted mb-0">Referrer akan tetap terbaca sampai {{ $policy['cookie_days'] }} hari sejak calon customer masuk dari link affiliate yang valid.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Payout</div>
                                <h3 class="h4">Bagaimana payout diproses?</h3>
                                <p class="text-muted mb-0">Komisi masuk ke antrian payout internal kami, diverifikasi, lalu dibayar sesuai jadwal operasional platform.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="landing-panel p-4 h-100">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Yang dihitung</div>
                                <h2 class="landing-section-title mb-3">Referral yang eligible</h2>
                                <div class="landing-highlight-list text-muted">
                                    <div class="mb-2">Customer datang dari link affiliate yang valid.</div>
                                    <div class="mb-2">Order benar-benar berubah menjadi `paid`.</div>
                                    <div class="mb-2">Sale tercatat ke partner yang masih aktif.</div>
                                    <div class="mb-2">
                                        @if($policy['first_purchase_only'])
                                            Hanya pembelian pertama yang eligible untuk komisi.
                                        @else
                                            Program ini dapat menghitung lebih dari pembelian pertama, sesuai rule aktif platform.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="landing-panel p-4 h-100">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Yang tidak dihitung</div>
                                <h2 class="landing-section-title mb-3">Referral yang tidak eligible</h2>
                                <div class="landing-highlight-list text-muted">
                                    <div class="mb-2">Order yang batal, expired, atau tidak pernah dibayar.</div>
                                    @if($policy['first_purchase_only'])
                                        <div class="mb-2">Perpanjangan langganan atau renewal tenant yang sama.</div>
                                        <div class="mb-2">Upgrade berikutnya setelah pembelian pertama, kecuali program berubah di masa depan.</div>
                                    @endif
                                    <div class="mb-2">Referral di luar masa attribution {{ $policy['cookie_days'] }} hari.</div>
                                    <div>Kasus yang dinilai tidak valid menurut review operasional platform.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="landing-panel p-4 p-lg-5 rounded-4">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Status Komisi</div>
                                <h2 class="landing-section-title mb-3">Status dibuat jelas dan bisa diaudit.</h2>
                                <div class="landing-highlight-list text-muted">
                                    <div class="mb-2"><strong>Pending</strong> - sale sudah tercatat dan menunggu review operasional.</div>
                                    <div class="mb-2"><strong>Approved</strong> - komisi sudah disetujui untuk dibayar.</div>
                                    <div class="mb-2"><strong>Paid</strong> - payout sudah dikirim.</div>
                                    <div><strong>Not Eligible / Rejected</strong> - komisi tidak dibayarkan karena tidak memenuhi rule program.</div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Operasional</div>
                                <h2 class="landing-section-title mb-3">Jadwal payout dan komunikasi</h2>
                                <div class="landing-highlight-list text-muted">
                                    <div class="mb-2">Metode payout utama: <strong>{{ implode(', ', array_map(fn ($item) => ucwords(str_replace('_', ' ', $item)), $policy['payout_methods'])) }}</strong></div>
                                    <div class="mb-2">Jadwal payout: <strong>{{ ucfirst($policy['payout_schedule']) }}</strong></div>
                                    <div class="mb-2">Target tanggal payout: <strong>{{ $policy['payout_day'] }}</strong></div>
                                    <div>Untuk diskusi partner atau pertanyaan operasional, gunakan email: <strong>{{ config('mail.from.address', 'hello@' . config('multitenancy.saas_domain', 'app.com')) }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="landing-panel p-4 rounded-4">
                        <div class="text-uppercase text-muted small fw-bold mb-2">Catatan</div>
                        <p class="mb-0 text-muted">
                            Program partner kami tidak dibuka sebagai pendaftaran umum. Jika kami mengundang atau menyetujui kerja sama, halaman ini menjadi referensi aturan yang berlaku pada saat itu.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
