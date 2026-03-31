<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Omnichannel Inbox untuk Tim Sales & Support</title>
    <meta name="description" content="Satukan percakapan WhatsApp, sosial media, live chat, dan chatbot AI dalam satu workspace. Balas lebih cepat, lead tidak tercecer, tim lebih fokus.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="landing-page">
@php $money = app(\App\Support\MoneyFormatter::class); @endphp
<div class="landing-shell">

{{-- ══ TOPBAR ══════════════════════════════════════════════ --}}
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="/" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="#solutions" class="landing-nav-link">Fitur</a>
                <a href="#pricing"   class="landing-nav-link">Harga</a>
                <a href="#ai-credits" class="landing-nav-link">AI Credits</a>
                <a href="#faq"       class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('onboarding.create') }}" class="btn btn-dark btn-sm">Daftar Gratis</a>
            </div>
        </div>
    </div>
</header>

<main>

{{-- ══ AFFILIATE BANNER ════════════════════════════════════ --}}
@if(!empty($affiliate))
<section class="pt-4">
    <div class="container">
        <div class="alert alert-info border-0 rounded-3 mb-0 d-flex align-items-center gap-3">
            <i class="ti ti-link fs-4 flex-shrink-0"></i>
            <div>Anda masuk dari link referral <strong>{{ $affiliate->name }}</strong>. Referral akan tercatat otomatis saat melanjutkan pendaftaran.</div>
        </div>
    </div>
</section>
@endif

{{-- ══ HERO ════════════════════════════════════════════════ --}}
<section class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="landing-badge mb-4">
                    <i class="ti ti-bolt"></i> Untuk tim sales, support, dan marketing
                </div>
                <h1 class="landing-headline mb-4">
                    Semua percakapan pelanggan, <span>satu inbox</span>.
                </h1>
                <p class="landing-subtext mb-5">
                    WhatsApp, sosial media, live chat, dan chatbot AI — dikelola dari satu tempat. Tim lebih cepat merespons, lead tidak nyasar, dan closing jadi lebih terukur.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="{{ route('onboarding.create') }}" class="btn btn-lg btn-dark">Coba Sekarang</a>
                    <a href="#pricing" class="btn btn-lg btn-outline-dark">Lihat Paket</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="landing-pill"><i class="ti ti-brand-whatsapp"></i> WhatsApp API & Web</span>
                    <span class="landing-pill"><i class="ti ti-brand-instagram"></i> Social Inbox</span>
                    <span class="landing-pill"><i class="ti ti-message-chatbot"></i> Chatbot AI</span>
                    <span class="landing-pill"><i class="ti ti-live-view"></i> Live Chat</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-panel landing-hero-card p-4 p-lg-5">
                    <div class="mb-4">
                        <div class="text-uppercase text-muted small fw-bold mb-1">Apa yang didapat</div>
                        <div class="fw-bold fs-4 lh-sm">Tim Anda fokus jualan,<br>bukan pindah-pindah aplikasi.</div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-blue);">4+</div>
                                <div class="small text-muted">Channel dalam satu inbox</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="landing-metric p-3 text-center">
                                <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;color:var(--landing-teal);">24/7</div>
                                <div class="small text-muted">Chatbot AI siap menjawab</div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Percakapan masuk ke inbox tim, bisa di-assign ke CS</div>
                        <div><i class="ti ti-check text-success"></i> Chatbot jawab otomatis, tim fokus ke percakapan penting</div>
                        <div><i class="ti ti-check text-success"></i> Aktif setelah bayar — tidak perlu setup teknis manual</div>
                        <div><i class="ti ti-check text-success"></i> Bayar pakai transfer, VA, atau QRIS</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ SOCIAL PROOF / TRUST STRIP ═════════════════════════ --}}
<section class="py-4">
    <div class="container">
        <div class="landing-trust-strip">
            <div class="landing-trust-item">
                <i class="ti ti-brand-whatsapp"></i>
                <div class="small">WhatsApp API Resmi</div>
            </div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item">
                <i class="ti ti-lock"></i>
                <div class="small">Data tiap tenant terisolasi</div>
            </div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item">
                <i class="ti ti-credit-card"></i>
                <div class="small">Bayar lokal — VA, QRIS, transfer</div>
            </div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item">
                <i class="ti ti-headset"></i>
                <div class="small">Support berbahasa Indonesia</div>
            </div>
            <div class="landing-trust-sep"></div>
            <div class="landing-trust-item">
                <i class="ti ti-building-store"></i>
                <div class="small">Multi-branch & multi-company</div>
            </div>
        </div>
    </div>
</section>

{{-- ══ PROBLEMS / WHY ══════════════════════════════════════ --}}
<section class="py-5 py-lg-6" id="why">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Masalah yang kami selesaikan</div>
            <h2 class="landing-section-title">Tim Anda seharusnya tidak menghabiskan waktu<br class="d-none d-lg-block"> untuk hal-hal ini.</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="landing-problem-card p-4 h-100">
                    <div class="landing-problem-icon mb-3"><i class="ti ti-alert-triangle"></i></div>
                    <h3 class="h5 mb-2">Lead nyasar ke HP pribadi admin</h3>
                    <p class="text-muted mb-0 small">Pesan dari pelanggan masuk ke akun WhatsApp atau DM pribadi admin — tidak bisa dipantau, tidak bisa di-handover.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="landing-problem-card p-4 h-100">
                    <div class="landing-problem-icon mb-3"><i class="ti ti-clock"></i></div>
                    <h3 class="h5 mb-2">Respon lambat karena pindah-pindah app</h3>
                    <p class="text-muted mb-0 small">Admin harus cek Instagram, WhatsApp, email, dan website secara bergantian — waktu habis, respon lambat, lead tidak ditangani.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="landing-problem-card p-4 h-100">
                    <div class="landing-problem-icon mb-3"><i class="ti ti-robot-off"></i></div>
                    <h3 class="h5 mb-2">Di luar jam kerja tidak ada yang jawab</h3>
                    <p class="text-muted mb-0 small">Pelanggan tanya malam hari atau weekend — tidak ada yang balas, padahal banyak pertanyaan bisa dijawab otomatis oleh bot.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ SOLUTIONS / FEATURES ════════════════════════════════ --}}
<section id="solutions" class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-5 align-items-center mb-6">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Solusi</div>
                <h2 class="landing-section-title mb-3">Satu workspace untuk semua channel percakapan tim Anda.</h2>
                <p class="landing-subtext">Tidak perlu banyak tools. Semua channel terhubung, semua percakapan bisa dipantau tim, dan bot AI siap bantu di luar jam kerja.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-messages"></i></div>
                    <h3 class="h5 mb-2">Conversation Inbox</h3>
                    <p class="text-muted small mb-0">Satu inbox tim untuk semua percakapan masuk. Bisa di-assign ke CS, dipantau supervisor, dan punya riwayat lengkap per pelanggan.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-brand-instagram"></i></div>
                    <h3 class="h5 mb-2">Social Media Inbox</h3>
                    <p class="text-muted small mb-0">Kelola DM Instagram, Facebook, dan channel sosial lainnya dari satu dashboard — tidak perlu login ke masing-masing akun.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-brand-whatsapp"></i></div>
                    <h3 class="h5 mb-2">WhatsApp API & Web</h3>
                    <p class="text-muted small mb-0">Hubungkan nomor WhatsApp bisnis Anda sendiri. Pilih WhatsApp API untuk volume tinggi, atau WhatsApp Web untuk operasional harian.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-message-chatbot"></i></div>
                    <h3 class="h5 mb-2">Chatbot AI</h3>
                    <p class="text-muted small mb-0">Bot menjawab otomatis 24/7, menyaring pertanyaan umum, dan meneruskan ke tim hanya saat benar-benar dibutuhkan.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-live-view"></i></div>
                    <h3 class="h5 mb-2">Live Chat Widget</h3>
                    <p class="text-muted small mb-0">Tambahkan widget chat ke website Anda dalam hitungan menit. Pengunjung website bisa langsung terhubung ke tim tanpa meninggalkan halaman.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-chart-bar"></i></div>
                    <h3 class="h5 mb-2">CRM & Pipeline</h3>
                    <p class="text-muted small mb-0">Lead dari semua channel bisa masuk ke pipeline CRM untuk follow-up terstruktur — tidak ada yang terlewat.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-users"></i></div>
                    <h3 class="h5 mb-2">Manajemen Kontak</h3>
                    <p class="text-muted small mb-0">Semua kontak pelanggan tersimpan terpusat lengkap dengan riwayat percakapan, tag, dan informasi bisnis relevan.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="landing-feature-card p-4 h-100">
                    <div class="landing-feature-icon mb-3"><i class="ti ti-building-store"></i></div>
                    <h3 class="h5 mb-2">Multi-Branch</h3>
                    <p class="text-muted small mb-0">Satu workspace untuk beberapa cabang atau departemen. Akses bisa dibatasi per lokasi atau tim sesuai kebutuhan.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ USE CASES ════════════════════════════════════════════ --}}
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Cocok untuk</div>
            <h2 class="landing-section-title">Dirancang untuk tim yang menangani pelanggan setiap hari.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Sales</div>
                    <h3 class="h5 mb-2">Tidak ada lead yang terlewat lagi</h3>
                    <p class="text-muted small mb-0">Semua lead dari WhatsApp, sosmed, dan live chat masuk ke satu pipeline. Follow-up bisa dijadwal, dan histori percakapan tidak hilang saat ganti admin.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Customer Support</div>
                    <h3 class="h5 mb-2">Respon lebih cepat, pelanggan lebih puas</h3>
                    <p class="text-muted small mb-0">Tim CS punya inbox bersama dengan assignment dan prioritas. Bot AI tangani pertanyaan berulang, tim fokus ke kasus yang butuh penanganan manusia.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="landing-usecase-card p-4 h-100">
                    <div class="landing-usecase-label mb-3">Marketing & Ops</div>
                    <h3 class="h5 mb-2">Semua channel aktif, satu tempat kelola</h3>
                    <p class="text-muted small mb-0">Dari social inbox sampai WhatsApp API — semua dikelola dari satu workspace sesuai paket yang diambil. Tidak perlu integrasi manual.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ PRICING ══════════════════════════════════════════════ --}}
<section id="pricing" class="py-5 py-lg-6">
    <div class="container">
        @php
            $plansByInterval = $plans->groupBy(fn ($plan) => $plan->billing_interval);
            $intervalOrder = ['monthly', 'semiannual', 'yearly'];
            $intervalMeta = [
                'monthly'    => ['label' => 'Bulanan',   'desc' => 'Fleksibel, bisa evaluasi setiap bulan.',            'badge' => null],
                'semiannual' => ['label' => '6 Bulanan', 'desc' => 'Lebih hemat, cocok untuk bisnis yang sudah jalan.', 'badge' => 'Hemat ~10%'],
                'yearly'     => ['label' => 'Tahunan',   'desc' => 'Paling efisien untuk tim yang sudah siap scale.',   'badge' => 'Hemat ~17%'],
            ];
            $firstAvailableInterval = collect($intervalOrder)->first(fn($k) => $plansByInterval->has($k));
        @endphp

        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">Paket & Harga</div>
            <h2 class="landing-section-title mb-3">Pilih paket sesuai kebutuhan tim.</h2>
            <p class="landing-subtext mx-auto text-center mb-5">Semua paket sudah termasuk inbox tim, kontak, dan live chat. Pilih plan yang sesuai dengan channel dan kapasitas yang Anda butuhkan.</p>

            {{-- Billing interval tab switcher --}}
            <div class="d-flex justify-content-center">
                <div class="pricing-tab-nav">
                    @foreach ($intervalOrder as $iKey)
                        @php $iMeta = $intervalMeta[$iKey] ?? null; @endphp
                        @if($iMeta && $plansByInterval->has($iKey))
                        <button type="button" class="pricing-tab-btn {{ $loop->first ? 'active' : '' }}" data-tab="{{ $iKey }}">
                            {{ $iMeta['label'] }}
                            @if($iMeta['badge'])
                                <span class="pricing-tab-badge">{{ $iMeta['badge'] }}</span>
                            @endif
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @foreach ($intervalOrder as $iKey)
            @php
                $iPlans = $plansByInterval->get($iKey, collect())->sortBy('sort_order')->values();
                $iMeta  = $intervalMeta[$iKey] ?? null;
            @endphp
            @if ($iMeta && $iPlans->isNotEmpty())
            <div class="pricing-tab-pane {{ $loop->first ? 'active' : '' }}" data-pane="{{ $iKey }}">
                <div class="row g-4">
                    @foreach ($iPlans as $plan)
                    @php
                        $sales     = $plan->sales_meta ?? [];
                        $features  = (array) ($plan->features ?? []);
                        $limits    = (array) ($plan->limits ?? []);
                        $currency  = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                        $recommended = (bool) ($sales['recommended'] ?? false);
                        $fit       = (string) ($sales['audience'] ?? '');
                        $hasAi     = !empty($features[\App\Support\PlanFeature::CHATBOT_AI]);
                        $hasWaApi  = !empty($features[\App\Support\PlanFeature::WHATSAPP_API]);
                        $hasWaWeb  = !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]);
                        $aiCredits = (int) ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0);
                        $users     = $limits[\App\Support\PlanLimit::USERS] ?? null;
                        $contacts  = $limits[\App\Support\PlanLimit::CONTACTS] ?? null;
                        $socialAcc = $limits[\App\Support\PlanLimit::SOCIAL_ACCOUNTS] ?? null;
                        $waInst    = $limits[\App\Support\PlanLimit::WHATSAPP_INSTANCES] ?? null;
                        $widgets   = $limits[\App\Support\PlanLimit::LIVE_CHAT_WIDGETS] ?? null;
                    @endphp
                    <div class="col-lg-4">
                        <div class="landing-plan-card p-4 p-lg-5 {{ $recommended ? 'featured' : '' }}">
                            @if($recommended)
                                <div class="landing-plan-popular">Paling populer</div>
                            @endif
                            <div class="mb-1 d-flex align-items-center gap-2 flex-wrap">
                                <div class="h3 mb-0 fw-800">{{ $sales['display_name'] ?? $plan->display_name }}</div>
                            </div>
                            <div class="text-muted small mb-4">{{ $sales['tagline'] ?? '' }}</div>

                            <div class="landing-price mb-1">{{ $money->format((float) ($sales['price'] ?? 0), $currency) }}</div>
                            <div class="text-muted small mb-4">per bulan · tagihan {{ strtolower($plan->billing_interval_label) }}</div>

                            @if($fit)
                            <div class="landing-package-fit mb-4"><i class="ti ti-user-circle"></i>{{ $fit }}</div>
                            @endif

                            <a href="{{ route('onboarding.create') }}?plan={{ $plan->code }}" class="btn {{ $recommended ? 'btn-dark' : 'btn-outline-dark' }} w-100 mb-4">
                                Mulai dengan {{ $sales['display_name'] ?? $plan->display_name }}
                            </a>

                            {{-- Channel Badges --}}
                            <div class="mb-4">
                                <div class="text-uppercase text-muted mb-2" style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;">Channel yang aktif</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="landing-channel-badge"><i class="ti ti-messages"></i> Conversation</span>
                                    <span class="landing-channel-badge"><i class="ti ti-live-view"></i> Live Chat</span>
                                    <span class="landing-channel-badge"><i class="ti ti-brand-instagram"></i> Social</span>
                                    @if($hasAi)<span class="landing-channel-badge active"><i class="ti ti-message-chatbot"></i> Chatbot AI</span>@endif
                                    @if($hasWaApi)<span class="landing-channel-badge active"><i class="ti ti-brand-whatsapp"></i> WA API</span>@endif
                                    @if($hasWaWeb)<span class="landing-channel-badge active"><i class="ti ti-brand-whatsapp"></i> WA Web</span>@endif
                                </div>
                            </div>

                            {{-- Limits --}}
                            <div class="landing-limit-table mb-4">
                                @if($users !== null)
                                <div class="landing-limit-row">
                                    <span>Pengguna (CS, Admin)</span>
                                    <span class="fw-semibold">{{ number_format((int)$users, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($contacts !== null)
                                <div class="landing-limit-row">
                                    <span>Kontak</span>
                                    <span class="fw-semibold">{{ number_format((int)$contacts, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($socialAcc !== null)
                                <div class="landing-limit-row">
                                    <span>Akun sosial media</span>
                                    <span class="fw-semibold">{{ number_format((int)$socialAcc, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($widgets !== null)
                                <div class="landing-limit-row">
                                    <span>Widget live chat</span>
                                    <span class="fw-semibold">{{ number_format((int)$widgets, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if(($hasWaApi || $hasWaWeb) && $waInst !== null)
                                <div class="landing-limit-row">
                                    <span>Koneksi WhatsApp</span>
                                    <span class="fw-semibold">{{ number_format((int)$waInst, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($hasAi && $aiCredits > 0)
                                <div class="landing-limit-row">
                                    <span>AI Credits / bulan <a href="#ai-credits" class="landing-credit-hint">Apa ini?</a></span>
                                    <span class="fw-semibold">{{ number_format($aiCredits, 0, ',', '.') }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Highlights --}}
                            @if(!empty($sales['highlights']))
                            <div class="landing-checklist small text-muted">
                                @foreach ($sales['highlights'] as $hl)
                                <div><i class="ti ti-check text-success"></i> {{ $hl }}</div>
                                @endforeach
                            </div>
                            @endif

                            @if(!$hasAi)
                            <div class="landing-plan-note mt-3">
                                <i class="ti ti-info-circle"></i> Chatbot AI tersedia mulai paket Growth ke atas.
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach

        <div class="landing-pricing-footnote mt-4">
            <i class="ti ti-info-circle"></i>
            Semua harga belum termasuk PPN. Add-on yang tersedia: <strong>top up AI Credits</strong>. Penambahan kapasitas lain dilakukan dengan upgrade plan.
        </div>
    </div>
</section>

{{-- ══ AI CREDITS EXPLAINER ════════════════════════════════ --}}
<section id="ai-credits" class="py-5 py-lg-6">
    <div class="container">
        <div class="landing-panel rounded-4 p-4 p-lg-5">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <div class="landing-eyebrow mb-2">AI Credits</div>
                    <h2 class="landing-section-title mb-3">1 AI Credit itu setara dengan apa?</h2>
                    <p class="landing-subtext mb-4">
                        AI Credits adalah satuan penggunaan fitur kecerdasan buatan di workspace Anda. Setiap kali bot AI menjawab pesan atau mencari referensi dari dokumen, kredit berkurang sesuai pemakaian.
                    </p>
                    <p class="text-muted small">
                        Credits direset setiap bulan sesuai paket. Jika habis, Anda bisa top up kapan saja dari halaman subscription tanpa perlu upgrade paket.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="landing-credit-card p-3">
                                <div class="landing-credit-icon"><i class="ti ti-message-reply"></i></div>
                                <div class="fw-semibold mb-1">Balasan chatbot</div>
                                <div class="landing-credit-cost">≈ 1 kredit</div>
                                <div class="text-muted small mt-1">Per percakapan singkat bot menjawab pertanyaan pelanggan (~100–200 kata).</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-credit-card p-3">
                                <div class="landing-credit-icon"><i class="ti ti-file-search"></i></div>
                                <div class="fw-semibold mb-1">Cari dokumen (RAG)</div>
                                <div class="landing-credit-cost">≈ 1–2 kredit</div>
                                <div class="text-muted small mt-1">Saat bot mencari referensi dari knowledge base sebelum menjawab.</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-credit-card p-3">
                                <div class="landing-credit-icon"><i class="ti ti-messages"></i></div>
                                <div class="fw-semibold mb-1">Percakapan panjang</div>
                                <div class="landing-credit-cost">≈ 2–5 kredit</div>
                                <div class="text-muted small mt-1">Percakapan lebih panjang dengan konteks berlapis atau pertanyaan kompleks.</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="landing-credit-card p-3">
                                <div class="landing-credit-icon"><i class="ti ti-robot"></i></div>
                                <div class="fw-semibold mb-1">Paket Growth (~500 kredit)</div>
                                <div class="landing-credit-cost">≈ 300–500 balasan</div>
                                <div class="text-muted small mt-1">Cukup untuk ribuan pesan pelanggan per bulan dengan pertanyaan-pertanyaan umum sehari-hari.</div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-credit-note mt-3">
                        <i class="ti ti-bulb"></i>
                        <div>
                            <strong>Tips:</strong> Chatbot dengan mode "Berbasis Aturan" (rule-only) <em>tidak menggunakan</em> AI Credits sama sekali. Credits hanya terpakai saat bot benar-benar memanggil model AI untuk menjawab.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ HOW IT WORKS ════════════════════════════════════════ --}}
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="landing-eyebrow mb-2">Cara kerja</div>
                <h2 class="landing-section-title mb-3">Dari daftar sampai aktif dalam satu alur.</h2>
                <p class="landing-subtext">Tidak perlu hubungi tim kami untuk aktivasi. Semua berjalan otomatis setelah pembayaran dikonfirmasi.</p>
            </div>
            <div class="col-lg-7">
                <div class="landing-steps-grid">
                    <div class="landing-step">
                        <div class="landing-step-number">1</div>
                        <div>
                            <h3 class="h6 mb-1">Pilih paket</h3>
                            <p class="text-muted small mb-0">Pilih Starter, Growth, atau Scale sesuai channel dan kapasitas tim Anda.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">2</div>
                        <div>
                            <h3 class="h6 mb-1">Buat workspace</h3>
                            <p class="text-muted small mb-0">Isi nama bisnis, subdomain, dan informasi admin utama. Selesai dalam 2 menit.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">3</div>
                        <div>
                            <h3 class="h6 mb-1">Bayar invoice</h3>
                            <p class="text-muted small mb-0">Pilih metode pembayaran lokal — transfer bank, virtual account, atau QRIS.</p>
                        </div>
                    </div>
                    <div class="landing-step">
                        <div class="landing-step-number">4</div>
                        <div>
                            <h3 class="h6 mb-1">Langsung bisa dipakai</h3>
                            <p class="text-muted small mb-0">Setelah pembayaran dikonfirmasi, workspace aktif dan semua modul sesuai paket siap digunakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ FAQ ══════════════════════════════════════════════════ --}}
<section id="faq" class="py-5 py-lg-6">
    <div class="container">
        <div class="text-center mb-5">
            <div class="landing-eyebrow mb-2">FAQ</div>
            <h2 class="landing-section-title">Pertanyaan yang paling sering muncul.</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apa bedanya WhatsApp API dan WhatsApp Web?</h3>
                    <p class="text-muted small mb-0">WhatsApp API cocok untuk volume tinggi dan integrasi resmi dengan Business API Meta. WhatsApp Web cocok untuk operasional yang masih butuh pola kerja seperti admin manual biasa. Keduanya menggunakan nomor WhatsApp Anda sendiri — tidak disediakan oleh kami.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">AI Credits itu apa dan bagaimana hitungannya?</h3>
                    <p class="text-muted small mb-0">AI Credits adalah satuan pemakaian chatbot AI. Setiap balasan bot menghabiskan ≈1 kredit. Jika bot juga mencari dokumen referensi, bisa 1–2 kredit ekstra. Kredit direset tiap bulan, dan bisa di-top up kapan saja tanpa upgrade paket.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah modul langsung aktif setelah bayar?</h3>
                    <p class="text-muted small mb-0">Ya. Tidak perlu konfirmasi manual ke tim kami. Setelah pembayaran dikonfirmasi oleh sistem, workspace aktif dan semua modul sesuai paket yang dipilih langsung bisa digunakan.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Apakah tiap tenant dapat subdomain sendiri?</h3>
                    <p class="text-muted small mb-0">Ya. Setelah daftar, workspace Anda memakai subdomain terpisah seperti <code>bisnisanda.{{ config('multitenancy.saas_domain') }}</code>. Login tenant dilakukan lewat URL workspace Anda, bukan lewat halaman ini.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Bisa upgrade paket di tengah periode?</h3>
                    <p class="text-muted small mb-0">Bisa. Upgrade paket bisa dilakukan kapan saja dari halaman subscription di dalam workspace. Modul tambahan akan langsung aktif setelah pembayaran selisihnya dikonfirmasi.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="landing-faq-card p-4 h-100">
                    <h3 class="h5 mb-2">Sudah punya workspace, masuk dari mana?</h3>
                    <p class="text-muted small mb-0">Masuk langsung lewat URL workspace Anda, misalnya <code>namaworkspace.{{ config('multitenancy.saas_domain') }}/login</code>. Bisa juga gunakan tombol "Login Workspace" di atas untuk mencari workspace berdasarkan subdomain atau email.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ WORKSPACE FINDER ════════════════════════════════════ --}}
<section id="workspace" class="py-5">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 rounded-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="landing-eyebrow mb-2">Sudah punya workspace?</div>
                    <h2 class="landing-section-title mb-2">Masuk lewat subdomain workspace Anda.</h2>
                    <p class="landing-subtext mb-0">
                        Gunakan URL seperti <strong>namaworkspace.{{ config('multitenancy.saas_domain') }}/login</strong>, atau klik tombol di samping untuk mencari workspace berdasarkan email atau subdomain.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('workspace.finder') }}" class="btn btn-dark btn-lg">Buka Login Workspace</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ CTA BAND ═════════════════════════════════════════════ --}}
<section class="py-5">
    <div class="container">
        <div class="landing-cta-band p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <div class="text-uppercase small fw-bold mb-2 opacity-75">Siap mulai?</div>
                    <h2 class="landing-section-title text-white mb-3">Aktifkan workspace omnichannel Anda hari ini.</h2>
                    <p class="mb-0 opacity-75">Pilih paket, buat workspace, bayar, dan semua modul langsung aktif. Tidak perlu setup teknis.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('onboarding.create') }}" class="btn btn-light btn-lg me-2 mb-2">Mulai Sekarang</a>
                    <a href="{{ route('workspace.finder') }}" class="btn btn-outline-light btn-lg mb-2">Masuk Workspace</a>
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
            {{-- Brand + contact --}}
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

            {{-- Produk --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Produk</div>
                <nav class="landing-footer-nav">
                    <a href="#solutions">Fitur</a>
                    <a href="#pricing">Harga</a>
                    <a href="#ai-credits">AI Credits</a>
                    <a href="#faq">FAQ</a>
                    <a href="{{ route('workspace.finder') }}">Login Workspace</a>
                    <a href="{{ route('onboarding.create') }}">Daftar Gratis</a>
                </nav>
            </div>

            {{-- Perusahaan --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Perusahaan</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('affiliate.program') }}">Program Partner</a>
                    <a href="{{ route('security') }}">Keamanan Data</a>
                    <a href="#" class="landing-footer-link-placeholder">Kebijakan Privasi</a>
                    <a href="#" class="landing-footer-link-placeholder">Syarat &amp; Ketentuan</a>
                </nav>
            </div>

            {{-- Trust --}}
            <div class="col-lg-4">
                <div class="landing-footer-heading">Infrastruktur &amp; Keamanan</div>
                <div class="landing-footer-trust-cards">
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-server-2"></i>
                        <div>
                            <div class="fw-semibold">Server di Indonesia</div>
                            <div>Data tersimpan di data center berlokasi di Indonesia.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div>
                            <div class="fw-semibold">Enkripsi &amp; Isolasi</div>
                            <div>Setiap workspace terisolasi. Koneksi dienkripsi TLS.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div>
                            <div class="fw-semibold">Database Kelas Enterprise</div>
                            <div>Backup otomatis harian, high-availability, zero data loss.</div>
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

<script>
(function () {
    // Pricing tab switcher
    var tabBtns = document.querySelectorAll('.pricing-tab-btn');
    var tabPanes = document.querySelectorAll('.pricing-tab-pane');

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.dataset.tab;
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            tabPanes.forEach(function (p) {
                if (p.dataset.pane === target) {
                    p.classList.add('active');
                } else {
                    p.classList.remove('active');
                }
            });
            btn.classList.add('active');
        });
    });
})();
</script>
</body>
</html>
