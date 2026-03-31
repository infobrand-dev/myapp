<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | Omnichannel Inbox, Live Chat, Chatbot AI, WhatsApp API & WhatsApp Web</title>
    <meta name="description" content="Satukan percakapan live chat website, WhatsApp, sosial media, dan chatbot AI dalam satu workspace untuk tim sales, support, dan marketing.">
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
            <div class="container py-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <x-app-logo variant="default" :height="40" />
                            <div class="text-muted small">Omnichannel workspace untuk sales, support, dan marketing</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="#results" class="btn btn-link text-decoration-none text-dark">Fitur</a>
                        <a href="#pricing" class="btn btn-link text-decoration-none text-dark">Paket & Harga</a>
                        <a href="#faq" class="btn btn-link text-decoration-none text-dark">FAQ</a>
                        <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark">Login Workspace</a>
                        <a href="{{ route('onboarding.create') }}" class="btn btn-dark">Daftar Sekarang</a>
                    </div>
                </div>
            </div>
        </header>

        <main>
            @if(!empty($affiliate))
                <section class="py-3">
                    <div class="container">
                        <div class="alert alert-info border-0 shadow-sm mb-0">
                            Anda masuk dari link affiliate <strong>{{ $affiliate->name }}</strong>. Referral akan tercatat otomatis saat Anda melanjutkan pendaftaran.
                        </div>
                    </div>
                </section>
            @endif
            <section class="landing-hero py-5 py-lg-6">
                <div class="container py-lg-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="landing-badge mb-4">
                                <i class="ti ti-bolt"></i>
                                Fokus launch untuk omnichannel: live chat, sosial media, AI chatbot, WhatsApp API, dan WhatsApp Web
                            </div>
                            <h1 class="landing-headline mb-4">
                                Satukan semua percakapan pelanggan dalam <span>satu workspace</span>.
                            </h1>
                            <p class="landing-subtext mb-4">
                                {{ config('app.name') }} membantu tim Anda mengubah chat yang tercecer jadi alur kerja yang lebih rapi untuk closing, follow-up, dan support. Satu tim, satu inbox, banyak channel.
                            </p>
                            <div class="d-flex flex-wrap gap-3 mb-4">
                                <a href="{{ route('onboarding.create') }}" class="btn btn-lg btn-dark">Aktifkan Workspace</a>
                                <a href="#pricing" class="btn btn-lg btn-outline-dark">Bandingkan Paket</a>
                            </div>
                            <div class="small text-muted mb-4">Beli paket, buat workspace, bayar, dan tenant aktif otomatis setelah pembayaran settle.</div>
                            <div class="d-flex flex-wrap gap-2 text-muted small">
                                <span class="landing-pill text-dark"><i class="ti ti-message-circle"></i> Social media inbox</span>
                                <span class="landing-pill text-dark"><i class="ti ti-message-chatbot"></i> Live chat widget</span>
                                <span class="landing-pill text-dark"><i class="ti ti-brand-openai"></i> Chatbot AI</span>
                                <span class="landing-pill text-dark"><i class="ti ti-brand-whatsapp"></i> WhatsApp API & Web</span>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="landing-panel landing-hero-card p-4 p-lg-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <div class="text-uppercase text-muted small fw-bold">Omnichannel Control</div>
                                        <div class="fw-bold fs-3">Siap launch tanpa flow manual</div>
                                    </div>
                                    <span class="badge bg-success-lt text-success">Launch-ready</span>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="landing-metric p-3">
                                            <div class="text-muted small">Channel utama</div>
                                            <div class="fw-bold fs-2">4</div>
                                            <div class="small text-muted">Social, AI, WA API, WA Web</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="landing-metric p-3">
                                            <div class="text-muted small">Flow jualan</div>
                                            <div class="fw-bold fs-2">1</div>
                                            <div class="small text-muted">Pilih paket, bayar, aktif</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="landing-highlight-list small text-muted">
                                    <div class="mb-2">Paket publik langsung terhubung ke entitlement modul</div>
                                    <div class="mb-2">Workspace aktif otomatis setelah pembayaran settle</div>
                                    <div class="mb-2">Email invoice, payment received, dan welcome email sudah masuk flow</div>
                                    <div>Tenant tidak aktif prematur sebelum customer benar-benar bayar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="results" class="py-5">
                <div class="container">
                    <div class="row g-4 align-items-end mb-4">
                        <div class="col-lg-7">
                            <div class="text-uppercase text-muted small fw-bold mb-2">Hasil yang dijual</div>
                            <h2 class="landing-section-title mb-3">Bukan cuma dashboard baru. Ini tentang response time, follow-up, dan closing.</h2>
                            <p class="landing-subtext mb-0">
                                Saat orang buka homepage Anda, mereka perlu langsung paham hasil akhirnya: tim tidak lagi kehilangan lead, admin lebih cepat membalas, dan channel yang dibayar langsung bisa dipakai.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Lead handling</div>
                                <h3 class="h4">Lead yang masuk tidak nyasar ke akun pribadi admin</h3>
                                <p class="text-muted mb-0">Percakapan masuk ke inbox tim, bisa di-claim, dipantau, dan dilanjutkan tanpa kehilangan konteks.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Response speed</div>
                                <h3 class="h4">Customer dijawab lebih cepat dengan kombinasi tim + AI</h3>
                                <p class="text-muted mb-0">Chatbot AI bantu jawab awal, sementara tim fokus ke percakapan yang benar-benar perlu ditangani manusia.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-result-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Operational scale</div>
                                <h3 class="h4">Channel bertambah tanpa bikin operasional makin berantakan</h3>
                                <p class="text-muted mb-0">Live chat website, social media, WhatsApp API, dan WhatsApp Web berjalan dari satu workspace dengan plan yang jelas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="solutions" class="py-5">
                <div class="container">
                    <div class="row g-4 align-items-end mb-4">
                        <div class="col-lg-7">
                            <div class="text-uppercase text-muted small fw-bold mb-2">Solusi utama</div>
                            <h2 class="landing-section-title mb-3">Jangan cuma sukses transaksi. Pastikan tim benar-benar bisa jualan.</h2>
                            <p class="landing-subtext mb-0">
                                Homepage ini fokus ke masalah nyata saat launch: chat tercecer, admin lambat merespons, WhatsApp belum terhubung, dan AI belum dipakai untuk bantu closing dan support.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="landing-feature-card p-4">
                                <div class="landing-feature-icon mb-3"><i class="ti ti-messages"></i></div>
                                <h3 class="h4">Conversation Inbox</h3>
                                <p class="text-muted mb-0">Satukan percakapan tim dalam satu inbox bersama untuk assignment, follow up, dan histori customer.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="landing-feature-card p-4">
                                <div class="landing-feature-icon mb-3"><i class="ti ti-brand-instagram"></i></div>
                                <h3 class="h4">Social Media Inbox</h3>
                                <p class="text-muted mb-0">Kelola DM dan percakapan sosial media dari satu dashboard, bukan pindah-pindah aplikasi admin.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="landing-feature-card p-4">
                                <div class="landing-feature-icon mb-3"><i class="ti ti-brand-openai"></i></div>
                                <h3 class="h4">Chatbot AI</h3>
                                <p class="text-muted mb-0">Bantu jawab cepat, saring lead, dan otomatisasi percakapan awal sebelum masuk ke tim manusia.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="landing-feature-card p-4">
                                <div class="landing-feature-icon mb-3"><i class="ti ti-brand-whatsapp"></i></div>
                                <h3 class="h4">WhatsApp API & Web</h3>
                                <p class="text-muted mb-0">Aktifkan channel WhatsApp sesuai kesiapan bisnis, dari API resmi sampai operasional WhatsApp Web.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="landing-usecase-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Sales</div>
                                <h3 class="h4">Lead masuk tidak lagi tercecer</h3>
                                <p class="text-muted mb-0">Semua lead dari sosial media, chatbot, live chat, dan WhatsApp bisa masuk ke CRM pipeline untuk follow up yang lebih rapi.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-usecase-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Customer Support</div>
                                <h3 class="h4">Respon lebih cepat dan terukur</h3>
                                <p class="text-muted mb-0">Tim support dapat inbox bersama, claim conversation, dan histori percakapan yang konsisten.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="landing-usecase-card p-4">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Marketing Ops</div>
                                <h3 class="h4">Channel aktif sesuai paket</h3>
                                <p class="text-muted mb-0">Plan menentukan modul yang aktif, jadi apa yang dibeli customer langsung sama dengan apa yang bisa dipakai.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pricing" class="py-5">
                <div class="container">
                    <div class="row g-4 align-items-end mb-4">
                        <div class="col-lg-7">
                            <div class="text-uppercase text-muted small fw-bold mb-2">Paket</div>
                            <h2 class="landing-section-title mb-3">Pilih paket omnichannel yang sesuai tahap bisnis Anda.</h2>
                            <p class="landing-subtext mb-0">
                                Semua paket fokus ke Omnichannel untuk tim sales dan customer service. Mulai dari social inbox dan live chat dasar sampai paket lengkap dengan AI, WhatsApp API, dan WhatsApp Web.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4">
                        @foreach ($plans as $plan)
                            @php
                                $sales = $plan->sales_meta ?? [];
                                $features = (array) ($plan->features ?? []);
                                $limits = (array) ($plan->limits ?? []);
                                $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                                $recommended = (bool) ($sales['recommended'] ?? false);
                                $fit = (string) ($sales['audience'] ?? 'Paket omnichannel');
                                $channels = [
                                    'Social Inbox',
                                    'Live Chat',
                                    'CRM Lite',
                                ];

                                if (!empty($features[\App\Support\PlanFeature::CHATBOT_AI])) {
                                    $channels[] = 'AI';
                                }

                                if (!empty($features[\App\Support\PlanFeature::WHATSAPP_API])) {
                                    $channels[] = 'WhatsApp API';
                                }

                                if (!empty($features[\App\Support\PlanFeature::WHATSAPP_WEB])) {
                                    $channels[] = 'WhatsApp Web';
                                }

                                $primaryLimits = [
                                    'Users' => $limits[\App\Support\PlanLimit::USERS] ?? null,
                                    'Contacts' => $limits[\App\Support\PlanLimit::CONTACTS] ?? null,
                                    'Social Accounts' => $limits[\App\Support\PlanLimit::SOCIAL_ACCOUNTS] ?? null,
                                    'Live Chat Widgets' => $limits[\App\Support\PlanLimit::LIVE_CHAT_WIDGETS] ?? null,
                                ];

                                if (!empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB])) {
                                    $primaryLimits['WhatsApp Connections'] = $limits[\App\Support\PlanLimit::WHATSAPP_INSTANCES] ?? null;
                                }

                                if (($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0) {
                                    $primaryLimits['AI Credits'] = $limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0;
                                }
                            @endphp
                            <div class="col-lg-4">
                                <div class="landing-plan-card p-4 {{ $recommended ? 'featured' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="h3 mb-1">{{ $sales['display_name'] ?? $plan->display_name }}</div>
                                            <div class="text-muted small">{{ $sales['tagline'] ?? 'Paket omnichannel' }}</div>
                                        </div>
                                        @if ($recommended)
                                            <span class="badge bg-primary-lt text-primary">Paling populer</span>
                                        @endif
                                    </div>
                                    <div class="landing-price mb-1">{{ $money->format((float) ($sales['price'] ?? 0), $priceCurrency) }}</div>
                                    <div class="text-muted small mb-3">/{{ $plan->billing_interval_label }}</div>
                                    <div class="landing-package-fit mb-3"><i class="ti ti-user-circle"></i>{{ $fit }}</div>
                                    <p class="text-muted">{{ $sales['description'] ?? 'Paket untuk tim omnichannel.' }}</p>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @foreach ($channels as $channel)
                                            <span class="badge bg-light text-dark border">{{ $channel }}</span>
                                        @endforeach
                                    </div>
                                    <div class="rounded-3 border p-3 mb-3 small">
                                        <div class="d-flex justify-content-between gap-3 mb-2">
                                            <span class="text-muted">AI</span>
                                            <span class="fw-semibold">{{ ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0 ? 'Termasuk kuota + top up tersedia' : 'Belum termasuk' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between gap-3">
                                            <span class="text-muted">WhatsApp</span>
                                            <span class="fw-semibold">{{ !empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]) ? 'Hubungkan akun WhatsApp Anda sendiri' : 'Belum termasuk' }}</span>
                                        </div>
                                    </div>
                                    <div class="landing-highlight-list small text-muted mb-4">
                                        @foreach ($primaryLimits as $label => $value)
                                            @if ($value !== null)
                                                <div class="mb-2 d-flex justify-content-between gap-3">
                                                    <span>{{ $label }}</span>
                                                    <span class="text-dark fw-semibold">{{ number_format((int) $value, 0, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="landing-highlight-list small text-muted mb-4">
                                        @foreach (($sales['highlights'] ?? []) as $highlight)
                                            <div class="mb-2">{{ $highlight }}</div>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('onboarding.create') }}?plan={{ $plan->code }}" class="btn {{ $recommended ? 'btn-dark' : 'btn-outline-dark' }} w-100">
                                        Pilih {{ $sales['display_name'] ?? $plan->display_name }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-3">
                        Add-on yang tersedia untuk launch hanya <strong>AI Credits top up</strong>. Di luar itu, penambahan kapasitas dilakukan dengan upgrade plan atau penyesuaian internal oleh tim kami.
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-5">
                            <div class="text-uppercase text-muted small fw-bold mb-2">Cara kerja</div>
                            <h2 class="landing-section-title mb-3">Beli paket, aktifkan workspace, langsung pakai modul.</h2>
                            <p class="landing-subtext mb-0">
                                Fokus kita sekarang bukan cuma payment sukses, tapi memastikan tenant aktif dan modul yang dibeli langsung siap dipakai setelah pembayaran settle.
                            </p>
                        </div>
                        <div class="col-lg-7">
                            <div class="landing-panel p-4 p-lg-5 rounded-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="landing-step">
                                            <div class="landing-step-number">1</div>
                                            <h3 class="h5">Pilih paket</h3>
                                            <p class="text-muted mb-0">Pilih Starter, Growth, atau Scale sesuai channel dan kapasitas tim Anda.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="landing-step">
                                            <div class="landing-step-number">2</div>
                                            <h3 class="h5">Buat workspace</h3>
                                            <p class="text-muted mb-0">Tentukan nama bisnis, subdomain, dan admin utama untuk tenant Anda.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="landing-step">
                                            <div class="landing-step-number">3</div>
                                            <h3 class="h5">Bayar invoice</h3>
                                            <p class="text-muted mb-0">Sistem otomatis membuat invoice dan mengarahkan Anda ke Midtrans checkout.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="landing-step">
                                            <div class="landing-step-number">4</div>
                                            <h3 class="h5">Tenant aktif otomatis</h3>
                                            <p class="text-muted mb-0">Saat payment settle, subscription aktif, welcome email terkirim, dan modul sesuai paket siap dipakai.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="text-center mb-4">
                        <div class="text-uppercase text-muted small fw-bold mb-2">Kenapa pilih kami</div>
                        <h2 class="landing-section-title mb-0">Dibangun untuk tim bisnis Indonesia.</h2>
                    </div>
                    <div class="row g-3 justify-content-center">
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="font-size:2rem;color:var(--landing-blue);">
                                    <i class="ti ti-brand-whatsapp" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">WhatsApp API</div>
                                <div class="text-muted" style="font-size:0.78rem;">Resmi & terverifikasi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="color:var(--landing-blue);">
                                    <i class="ti ti-lock" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">Data Aman</div>
                                <div class="text-muted" style="font-size:0.78rem;">Tiap tenant terisolasi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="color:var(--landing-blue);">
                                    <i class="ti ti-bolt" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">Aktif Otomatis</div>
                                <div class="text-muted" style="font-size:0.78rem;">Setelah bayar langsung pakai</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="color:var(--landing-blue);">
                                    <i class="ti ti-headset" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">Support Lokal</div>
                                <div class="text-muted" style="font-size:0.78rem;">Tim berbahasa Indonesia</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="color:var(--landing-blue);">
                                    <i class="ti ti-credit-card" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">Bayar Lokal</div>
                                <div class="text-muted" style="font-size:0.78rem;">Transfer, VA, QRIS</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3">
                                <div class="fw-bold mb-1" style="color:var(--landing-blue);">
                                    <i class="ti ti-building-store" style="font-size:2.2rem;"></i>
                                </div>
                                <div class="small fw-semibold">Multi-Branch</div>
                                <div class="text-muted" style="font-size:0.78rem;">Satu akun, banyak lokasi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="py-5">
                <div class="container">
                    <div class="row g-4 align-items-end mb-4">
                        <div class="col-lg-7">
                            <div class="text-uppercase text-muted small fw-bold mb-2">FAQ</div>
                            <h2 class="landing-section-title mb-3">Pertanyaan yang paling sering muncul sebelum mulai.</h2>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="landing-faq-card p-4">
                                <h3 class="h5">Apa bedanya WhatsApp API dan WhatsApp Web?</h3>
                                <p class="text-muted mb-0">WhatsApp API cocok untuk skala yang lebih resmi dan terintegrasi. WhatsApp Web cocok untuk operasional yang masih butuh pola kerja seperti admin manual. Untuk kedua opsi ini, tenant menghubungkan akun WhatsApp bisnisnya sendiri sesuai paket yang dipilih.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="landing-faq-card p-4">
                                <h3 class="h5">Apakah tiap tenant dapat subdomain sendiri?</h3>
                                <p class="text-muted mb-0">Ya. Setelah daftar, tenant Anda memakai workspace terpisah seperti `bisnisanda.{{ config('multitenancy.saas_domain') }}`.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="landing-faq-card p-4">
                                <h3 class="h5">Apakah modul langsung aktif setelah bayar?</h3>
                                <p class="text-muted mb-0">Ya. Setelah payment settle, tenant aktif dan entitlement modul mengikuti plan yang dibeli.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="landing-faq-card p-4">
                                <h3 class="h5">Kalau sudah punya workspace, masuk dari mana?</h3>
                                <p class="text-muted mb-0">Masuk lewat URL workspace Anda sendiri, misalnya `subdomain.{{ config('multitenancy.saas_domain') }}/login`.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workspace" class="py-5">
                <div class="container">
                    <div class="landing-panel p-4 p-lg-5 rounded-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-8">
                                <div class="text-uppercase text-muted small fw-bold mb-2">Sudah punya workspace?</div>
                                <h2 class="landing-section-title mb-3">Masuk lewat subdomain workspace Anda.</h2>
                                <p class="landing-subtext mb-0">
                                    Login tenant tidak memakai apex domain. Jika workspace Anda sudah aktif, masuk langsung lewat URL seperti <strong>`subdomain.{{ config('multitenancy.saas_domain') }}/login`</strong>.
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <a href="{{ route('workspace.finder') }}" class="btn btn-dark btn-lg">Buka Login Workspace</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-5">
                <div class="container">
                    <div class="landing-cta-band p-4 p-lg-5">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-8">
                                <div class="text-uppercase small fw-bold mb-2 opacity-75">Siap mulai</div>
                                <h2 class="landing-section-title text-white mb-3">Mulai jualan dengan workspace omnichannel yang langsung bisa dipakai.</h2>
                                <p class="mb-0 opacity-75">Pilih paket, buat tenant, lanjutkan ke pembayaran, dan aktifkan modul sesuai kebutuhan tim Anda.</p>
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

        <footer class="py-4 mt-2" style="border-top: 1px solid var(--landing-line);">
            <div class="container">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <x-app-logo variant="default" :height="28" />
                        <span class="text-muted small">&copy; {{ date('Y') }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-4">
                        <a href="{{ route('affiliate.program') }}" class="text-muted small text-decoration-none" style="transition: color 0.15s;" onmouseover="this.style.color='var(--landing-blue)'" onmouseout="this.style.color=''">Info Partner</a>
                        <a href="#" class="text-muted small text-decoration-none" style="transition: color 0.15s;" onmouseover="this.style.color='var(--landing-blue)'" onmouseout="this.style.color=''">Kebijakan Privasi</a>
                        <a href="#" class="text-muted small text-decoration-none" style="transition: color 0.15s;" onmouseover="this.style.color='var(--landing-blue)'" onmouseout="this.style.color=''">Syarat & Ketentuan</a>
                        <a href="mailto:{{ config('mail.from.address', 'hello@' . config('multitenancy.saas_domain', 'app.com')) }}" class="text-muted small text-decoration-none" style="transition: color 0.15s;" onmouseover="this.style.color='var(--landing-blue)'" onmouseout="this.style.color=''">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
