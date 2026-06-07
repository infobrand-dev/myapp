<x-guest-layout>
    <x-auth-card>
        @php
            $money = app(\App\Support\MoneyFormatter::class);
            $selectedProductLineKey = strtolower((string) ($selectedProductLine ?? ''));
            $selectionRequired = !in_array($selectedProductLineKey, ['accounting', 'commerce', 'omnichannel', 'crm'], true);
            $productLineKey = $selectionRequired ? '' : $selectedProductLineKey;
            $isAccounting = $productLineKey === 'accounting';
            $isCommerce = $productLineKey === 'commerce';
            $isOmnichannel = $productLineKey === 'omnichannel';
            $isCrm = $productLineKey === 'crm';
            $trialEnabled = (bool) ($trialAvailable ?? false);
            $trialLength = (int) ($trialDays ?? 14);
            $promoDiscountPercent = !empty($promoPreview) ? (int) ($promoPreview->discount_percent ?? 0) : 0;
            $selectedPlanId = old('subscription_plan_id', $preferredPlanId ?: ($plans->first()->id ?? 0));
            $defaultSignupMode = old('signup_mode', $trialEnabled ? 'trial' : 'paid');
            $defaultPaymentMethod = old('payment_method', $midtransReady ? 'midtrans' : ($manualPaymentReady ? 'bank_transfer' : ''));
            if ($selectionRequired) {
                $lineTitle = 'Pilih business suite lalu lanjutkan ke plan';
                $lineIntro = 'Mulai dari kebutuhan bisnis Anda dulu. Setelah suite dipilih, sistem langsung menampilkan plan yang relevan dan form pendaftaran workspace tanpa membuat Anda menebak alurnya.';
            } elseif ($productLineKey === 'accounting') {
                $lineTitle = 'Mulai workspace Accounting Anda';
                $lineIntro = 'Pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan workflow transaksi yang Anda butuhkan.';
            } elseif ($productLineKey === 'commerce') {
                $lineTitle = 'Mulai workspace Commerce Anda';
                $lineIntro = 'Pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan operasional order dan storefront Anda.';
            } elseif ($productLineKey === 'crm') {
                $lineTitle = 'Mulai workspace CRM Anda';
                $lineIntro = 'Pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan pipeline, follow-up queue, dan Customer 360 tim sales Anda.';
            } else {
                $lineTitle = 'Mulai workspace Omnichannel Anda';
                $lineIntro = 'Pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan modul yang Anda beli.';
            }
            $intervalHeadings = $isAccounting
                ? [
                    'monthly' => [
                        'title' => 'Paket Bulanan',
                        'description' => 'Cocok untuk mulai cepat dan melihat paket mana yang paling pas untuk ritme operasional bisnis Anda.',
                    ],
                    'semiannual' => [
                        'title' => 'Paket 6 Bulanan',
                        'description' => 'Pilihan untuk tim yang ingin biaya lebih stabil beberapa bulan ke depan.',
                    ],
                    'yearly' => [
                        'title' => 'Paket Tahunan',
                        'description' => 'Pilihan untuk bisnis yang sudah yakin ingin menjalankan workspace accounting sepanjang tahun.',
                    ],
                ]
                : ($isCommerce
                ? [
                    'monthly' => [
                        'title' => 'Paket Bulanan',
                        'description' => 'Cocok untuk mulai cepat membuka storefront dan operasional order tanpa setup yang ribet.',
                    ],
                    'semiannual' => [
                        'title' => 'Paket 6 Bulanan',
                        'description' => 'Pilihan untuk bisnis yang ingin biaya lebih stabil sambil menata ritme order beberapa bulan ke depan.',
                    ],
                    'yearly' => [
                        'title' => 'Paket Tahunan',
                        'description' => 'Cocok untuk bisnis yang sudah serius menjadikan commerce sebagai kanal penjualan utama.',
                    ],
                ]
                : ($isCrm
                ? [
                    'monthly' => [
                        'title' => 'Paket Bulanan',
                        'description' => 'Cocok untuk tim sales yang ingin langsung go-live tanpa menunggu integrasi suite lain.',
                    ],
                ]
                : [
                    'monthly' => [
                        'title' => 'Paket Bulanan',
                        'description' => 'Pilihan paling fleksibel untuk mulai jalan dan menyesuaikan ritme tim Anda.',
                    ],
                    'semiannual' => [
                        'title' => 'Paket 6 Bulanan',
                        'description' => 'Cocok untuk bisnis yang ingin lebih tenang beberapa bulan ke depan tanpa repot perpanjang setiap bulan.',
                    ],
                    'yearly' => [
                        'title' => 'Paket Tahunan',
                        'description' => 'Pilihan paling stabil untuk bisnis yang sudah siap menjalankan omnichannel sepanjang tahun.',
                    ],
                ]));
        @endphp
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <style>
            .onboarding-stepper {
                display: grid;
                gap: 0.75rem;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-bottom: 1.5rem;
            }

            .onboarding-step {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 1rem;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                padding: 0.85rem 1rem;
            }

            .onboarding-step.is-active {
                border-color: #111827;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            }

            .onboarding-step-number {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                background: #111827;
                color: #fff;
                font-size: 0.85rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .onboarding-step.is-pending .onboarding-step-number {
                background: #e5e7eb;
                color: #374151;
            }

            .suite-card {
                border: 1px solid rgba(15, 23, 42, 0.1);
                border-radius: 1.1rem;
                color: inherit;
                display: block;
                height: 100%;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .suite-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
            }

            .suite-card.is-selected {
                border-color: #111827;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            }

            .suite-card.is-disabled {
                background: #f8fafc;
                color: #6b7280;
                pointer-events: none;
            }

            .suite-highlight {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 999px;
                padding: 0.35rem 0.65rem;
                background: rgba(255, 255, 255, 0.85);
            }

            .plan-card {
                border: 1px solid rgba(15, 23, 42, 0.1);
                border-radius: 1.1rem;
                background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
                transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            }

            .plan-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
            }

            .plan-card.is-selected {
                border-color: #0d6efd;
                box-shadow: 0 18px 42px rgba(13, 110, 253, 0.12);
            }

            .plan-card.is-muted {
                opacity: 0.55;
            }

            .plan-chip {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 999px;
                padding: 0.35rem 0.65rem;
                background: #f8fafc;
                color: #0f172a;
            }

            .plan-metric {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 0.9rem;
                padding: 0.8rem;
                background: rgba(248, 250, 252, 0.9);
            }

            .form-section-card {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 1.1rem;
                background: #fff;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            }

            .form-section-title {
                font-size: 0.8rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #64748b;
                margin-bottom: 0.35rem;
            }

            .payment-option-card {
                border: 1px solid rgba(15, 23, 42, 0.1);
                border-radius: 1rem;
                background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
                transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
            }

            .payment-option-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            }

            .selection-summary-card {
                position: sticky;
                top: 1rem;
                z-index: 5;
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 1.1rem;
                background: rgba(255, 255, 255, 0.94);
                backdrop-filter: blur(10px);
                box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
            }

            .selection-summary-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 999px;
                padding: 0.4rem 0.7rem;
                background: #f8fafc;
                color: #0f172a;
                font-size: 0.8rem;
            }

            .onboarding-section-card {
                border: 0;
                border-radius: 1.25rem;
                background: linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%);
            }

            .onboarding-stepper > span {
                display: none;
            }

            @media (max-width: 767.98px) {
                .onboarding-stepper {
                    grid-template-columns: 1fr;
                }

                .selection-summary-card {
                    top: 0.75rem;
                }
            }
        </style>

        <div class="mb-4">
            <div class="onboarding-stepper">
                <div class="onboarding-step is-active">
                    <div class="onboarding-step-number">1</div>
                    <div class="fw-semibold small text-dark">Pilih suite</div>
                    <div class="small text-muted">Mulai dari business suite yang memang ingin Anda jalankan.</div>
                </div>
                <span class="text-muted">•</span>
                <div class="onboarding-step {{ $selectionRequired ? 'is-pending' : 'is-active' }}">
                    <div class="onboarding-step-number">2</div>
                    <div class="fw-semibold small text-dark">Pilih plan</div>
                    <div class="small text-muted">Hanya plan yang relevan dengan suite terpilih yang akan ditampilkan.</div>
                </div>
                <span class="text-muted">•</span>
                <div class="onboarding-step {{ $selectionRequired ? 'is-pending' : 'is-active' }}">
                    <div class="onboarding-step-number">3</div>
                    <div class="fw-semibold small text-dark">Isi workspace</div>
                    <div class="small text-muted">Lengkapi data bisnis dan admin utama sebelum aktivasi.</div>
                </div>
            </div>
            <h2 class="h4 mb-1">{{ $lineTitle }}</h2>
            <p class="text-muted small mb-0">{{ $lineIntro }}</p>
        </div>

        @if(!empty($affiliate))
            <div class="alert alert-info py-2 px-3 small">
                Anda mendaftar melalui partner affiliate <strong>{{ $affiliate->name }}</strong>.
            </div>
        @endif

        @if($promoDiscountPercent > 0)
            <div class="alert alert-danger py-2 px-3 small">
                Promo <strong>{{ $promoCode }}</strong> aktif. Harga di bawah ini sudah menampilkan potongan <strong>{{ $promoDiscountPercent }}%</strong>.
            </div>
        @endif

        <div class="row g-3 mb-4">
            @foreach(($productLineOptions ?? []) as $option)
                @php
                    $isSelectedSuite = !$selectionRequired && $productLineKey === $option['key'];
                @endphp
                <div class="col-12 col-lg-4">
                    <a
                        href="{{ $option['available'] ? $option['url'] : '#' }}"
                        class="suite-card text-decoration-none {{ $isSelectedSuite ? 'is-selected' : '' }} {{ $option['available'] ? '' : 'is-disabled' }}"
                        @if(!$option['available']) aria-disabled="true" @endif
                    >
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div class="min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <div class="fw-semibold">{{ $option['label'] }}</div>
                                        @if($isSelectedSuite)
                                            <span class="badge bg-dark text-white">Dipilih</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $option['description'] }}</div>
                                </div>
                                <span class="badge {{ $option['available'] ? ($isSelectedSuite ? 'bg-dark text-white' : 'bg-primary-lt text-primary') : 'bg-secondary-lt text-secondary' }}">
                                    {{ $option['available'] ? ($isSelectedSuite ? 'Lihat plan di bawah' : 'Pilih suite') : 'Belum dibuka' }}
                                </span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 small mb-0">
                                @foreach(($option['highlights'] ?? []) as $highlight)
                                    <span class="suite-highlight">{{ $highlight }}</span>
                                @endforeach
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if($selectionRequired)
            <div class="card onboarding-section-card mb-0">
                <div class="card-body p-3 p-md-4">
                    <div class="fw-semibold mb-2">Pilih suite lebih dulu supaya alurnya tidak membingungkan</div>
                    <div class="small text-muted mb-0">
                        Setelah Anda memilih Accounting, Commerce, CRM, atau Omnichannel, halaman ini langsung menampilkan plan yang cocok untuk suite tersebut beserta form pendaftarannya. Jadi tidak ada lagi fallback diam-diam ke suite tertentu.
                    </div>
                </div>
            </div>
        @else
            <div class="card onboarding-section-card mb-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold mb-1">Suite terpilih</div>
                            <div class="fw-semibold">{{ $productLineLabel }}</div>
                            <div class="text-muted small">Plan dan form di bawah sekarang sudah difilter khusus untuk suite ini.</div>
                        </div>
                        <a href="{{ route('onboarding.create') }}" class="btn btn-outline-secondary btn-sm">Ganti suite</a>
                    </div>
                </div>
            </div>

        <div class="card onboarding-section-card mb-4">
            <div class="card-body py-3 px-3">
                <div class="fw-semibold mb-2">Flow pendaftaran</div>
                <div class="small text-muted mb-3">
                    Pendaftaran publik di halaman ini hanya satu kali: Anda memilih plan sekaligus membuat workspace dan akun admin.
                </div>
                <div class="row g-2 small">
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold">1. Pilih plan</div>
                            <div class="text-muted">Plan dipilih saat daftar, bukan setelah akun jadi.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold">2. Buat workspace</div>
                            <div class="text-muted">Isi nama bisnis, subdomain, dan akun admin utama.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold">3. Aktifkan workspace</div>
                            <div class="text-muted">{{ $trialEnabled ? 'Mulai trial untuk langsung pakai, atau lanjut bayar untuk aktivasi berlangganan.' : 'Midtrans aktif otomatis, transfer bank aktif setelah verifikasi.' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}">
            @csrf
            <input type="hidden" name="product_line" value="{{ $productLineKey }}">
            @if(!empty($trialEntry))
                <input type="hidden" name="trial_entry" value="{{ $trialEntry }}">
            @endif

            <div
                class="mb-4"
                x-data="{
                    selected: {{ $selectedPlanId }},
                    paymentMethod: '{{ $defaultPaymentMethod }}',
                    signupMode: '{{ $defaultSignupMode }}',
                    hasPaymentOptions: {{ ($midtransReady || $manualPaymentReady) ? 'true' : 'false' }},
                    planSummaries: @js($plans->mapWithKeys(function ($plan) use ($money, $promoDiscountPercent) {
                        $sales = (array) ($plan->sales_meta ?? []);
                        $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                        $basePrice = (float) ($sales['price'] ?? 0);
                        $finalPrice = $promoDiscountPercent > 0
                            ? round($basePrice * (1 - ($promoDiscountPercent / 100)), 2)
                            : $basePrice;

                        return [
                            (string) $plan->id => [
                                'name' => (string) ($sales['display_name'] ?? $plan->display_name),
                                'price' => (string) $money->format($finalPrice, $priceCurrency),
                                'interval' => (string) $plan->billing_interval_label,
                            ],
                        ];
                    })),
                    selectedPlanSummary() {
                        return this.planSummaries[String(this.selected)] ?? null;
                    },
                    activationLabel() {
                        if (this.signupMode === 'trial') {
                            return 'Free Trial';
                        }

                        if (this.paymentMethod === 'midtrans') {
                            return 'Checkout Midtrans';
                        }

                        if (this.paymentMethod === 'bank_transfer') {
                            return 'Transfer Manual';
                        }

                        return 'Pilih metode aktivasi';
                    }
                }"
            >
                @php
                    $plansByInterval = $plans->groupBy(fn ($plan) => $plan->billing_interval);
                    $intervalOrder = ['monthly', 'semiannual', 'yearly'];
                @endphp

                <div class="card selection-summary-card mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                            <div>
                                <div class="form-section-title mb-1">Ringkasan Pilihan</div>
                                <div class="text-muted small">Saat Anda scroll ke bawah, ringkasan ini membantu memastikan suite, plan, dan mode aktivasi tetap konsisten.</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="selection-summary-chip">
                                    <strong>Suite:</strong> {{ $productLineLabel }}
                                </span>
                                <span class="selection-summary-chip" x-show="selectedPlanSummary()">
                                    <strong>Plan:</strong>
                                    <span x-text="selectedPlanSummary() ? selectedPlanSummary().name : 'Belum dipilih'"></span>
                                </span>
                                <span class="selection-summary-chip" x-show="selectedPlanSummary()">
                                    <strong>Harga:</strong>
                                    <span x-text="selectedPlanSummary() ? `${selectedPlanSummary().price} / ${selectedPlanSummary().interval}` : '-'"></span>
                                </span>
                                <span class="selection-summary-chip">
                                    <strong>Aktivasi:</strong>
                                    <span x-text="activationLabel()"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($trialEnabled)
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pilih cara mulai</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="card border p-3 h-100 cursor-pointer" :class="signupMode === 'trial' ? 'border-primary shadow-sm' : ''" style="transition: border-color 0.15s, box-shadow 0.15s;">
                                    <div class="d-flex align-items-start gap-3">
                                        <input class="form-check-input mt-1" type="radio" name="signup_mode" value="trial" x-model="signupMode">
                                        <div>
                                            <div class="fw-semibold">Mulai Free Trial {{ $trialLength }} Hari</div>
                                            <div class="text-muted small">Workspace langsung aktif tanpa checkout. Saat ini hanya untuk plan Accounting bulanan.</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="card border p-3 h-100 cursor-pointer" :class="signupMode === 'paid' ? 'border-primary shadow-sm' : ''" style="transition: border-color 0.15s, box-shadow 0.15s;">
                                    <div class="d-flex align-items-start gap-3">
                                        <input class="form-check-input mt-1" type="radio" name="signup_mode" value="paid" x-model="signupMode">
                                        <div>
                                            <div class="fw-semibold">Langsung Berlangganan</div>
                                            <div class="text-muted small">Buat invoice lalu lanjut ke Midtrans atau transfer manual sesuai metode yang tersedia.</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="signup_mode" value="paid">
                @endif

                <label class="form-label fw-semibold">Pilih paket {{ $productLineLabel }}</label>

                @foreach ($intervalOrder as $intervalKey)
                    @php
                        $intervalPlans = $plansByInterval->get($intervalKey, collect())->sortBy('sort_order')->values();
                        $intervalMeta = $intervalHeadings[$intervalKey] ?? null;
                    @endphp

                    @if($intervalMeta && $intervalPlans->isNotEmpty())
                        <div class="mb-3">
                            <div class="fw-semibold">{{ $intervalMeta['title'] }}</div>
                            <div class="text-muted small">{{ $intervalMeta['description'] }}</div>
                        </div>

                        <div class="row g-3 mb-4">
                            @foreach ($intervalPlans as $plan)
                                @php
                                    $sales = $plan->sales_meta ?? [];
                                    $features = (array) ($plan->features ?? []);
                                    $limits = (array) ($plan->limits ?? []);
                                    $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                                    $basePrice = (float) ($sales['price'] ?? 0);
                                    $promoPrice = $promoDiscountPercent > 0
                                        ? round($basePrice * (1 - ($promoDiscountPercent / 100)), 2)
                                        : $basePrice;
                                    $recommended = (bool) ($sales['recommended'] ?? false);
                                    $fit = (string) ($sales['audience'] ?? ($isAccounting ? 'Paket accounting untuk operasional bisnis' : ($isCommerce ? 'Paket commerce untuk operasional order' : ($isCrm ? 'Paket CRM untuk operasional sales' : 'Paket omnichannel'))));
                                @endphp

                                <div class="col-12 col-xl-6">
                                    <label
                                        class="plan-card card h-100 cursor-pointer"
                                        :class="{
                                            'is-selected border-primary shadow-sm': selected == {{ $plan->id }},
                                            'is-muted opacity-50': signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'
                                        }"
                                        style="transition: border-color 0.15s, box-shadow 0.15s;"
                                    >
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <input
                                                            class="form-check-input mt-0"
                                                            type="radio"
                                                            name="subscription_plan_id"
                                                            value="{{ $plan->id }}"
                                                            x-model="selected"
                                                            :disabled="signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'"
                                                            @checked($selectedPlanId == $plan->id)
                                                            required
                                                        >
                                                        <span class="fw-semibold">{{ $sales['display_name'] ?? $plan->display_name }}</span>
                                                        <span class="plan-chip">{{ $plan->billing_interval_label }}</span>
                                                        @if($recommended)
                                                            <span class="badge bg-primary-lt text-primary">Paling populer</span>
                                                        @endif
                                                        <span class="badge bg-warning-lt text-warning" x-show="signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'">Trial tidak tersedia</span>
                                                    </div>
                                                    <div class="text-muted small mt-2">{{ $sales['tagline'] ?? 'Paket' }}</div>
                                                </div>
                                                <div class="text-end">
                                                    @if($promoDiscountPercent > 0)
                                                        <div class="text-muted small text-decoration-line-through">{{ $money->format($basePrice, $priceCurrency) }}</div>
                                                        <div class="fw-bold fs-5">{{ $money->format($promoPrice, $priceCurrency) }}</div>
                                                        <div class="small text-danger fw-semibold">{{ $promoDiscountPercent }}% OFF</div>
                                                    @else
                                                        <div class="fw-bold fs-5">{{ $money->format($basePrice, $priceCurrency) }}</div>
                                                    @endif
                                                    <div class="text-muted small">Paket {{ strtolower($plan->billing_interval_label) }}</div>
                                                </div>
                                            </div>

                                            @if(!empty($sales['description']))
                                                <div class="small text-muted mb-2">{{ $sales['description'] }}</div>
                                            @endif

                                            <div class="small mb-2 text-dark">{{ $fit }}</div>

                                            <div class="d-flex flex-wrap gap-2 small mb-3">
                                                @if($isAccounting)
                                                    <span class="plan-chip">Sales</span>
                                                    <span class="plan-chip">Payments</span>
                                                    <span class="plan-chip">Finance</span>
                                                    <span class="plan-chip">Products</span>
                                                    <span class="plan-chip">Contacts</span>
                                                    @if(!empty($features[\App\Support\PlanFeature::PURCHASES]))
                                                        <span class="plan-chip">Purchases</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::INVENTORY]))
                                                        <span class="plan-chip">Inventory</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]))
                                                        <span class="plan-chip">Full Reports</span>
                                                    @else
                                                        <span class="plan-chip">Basic Reports</span>
                                                    @endif
                                                @elseif($isCommerce)
                                                    <span class="plan-chip">Storefront</span>
                                                    <span class="plan-chip">Orders</span>
                                                    <span class="plan-chip">Payments</span>
                                                    <span class="plan-chip">Shipping</span>
                                                    <span class="plan-chip">Fulfillment</span>
                                                    <span class="plan-chip">Contacts</span>
                                                @elseif($isCrm)
                                                    <span class="plan-chip">Customer 360</span>
                                                    <span class="plan-chip">Pipeline</span>
                                                    <span class="plan-chip">Follow-Up</span>
                                                    <span class="plan-chip">Contacts</span>
                                                    @if(!empty($features[\App\Support\PlanFeature::CRM_EXPORTS]))
                                                        <span class="plan-chip">Export</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::CRM_MANAGER_VISIBILITY]))
                                                        <span class="plan-chip">Manager Visibility</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::CRM_AUTOMATION]))
                                                        <span class="plan-chip">Automation Ready</span>
                                                    @endif
                                                @else
                                                    <span class="plan-chip">Instagram / Facebook DM</span>
                                                    <span class="plan-chip">Live Chat</span>
                                                    <span class="plan-chip">CRM Lite</span>
                                                    @if(!empty($features[\App\Support\PlanFeature::CHATBOT_AI]))
                                                        <span class="plan-chip">AI</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_API]))
                                                        <span class="plan-chip">WhatsApp API</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]))
                                                        <span class="plan-chip">WhatsApp Web</span>
                                                    @endif
                                                @endif
                                            </div>

                                            <div class="row g-2 small mb-3">
                                                <div class="col-sm-6">
                                                    <div class="plan-metric h-100">
                                                        <div class="text-muted mb-1">{{ $isAccounting ? 'Users / branch' : ($isCommerce ? 'Users / branch' : ($isCrm ? 'Users / pipelines' : 'AI')) }}</div>
                                                        <div class="fw-semibold">
                                                            @if($isAccounting)
                                                                {{ (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user / {{ (int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0) }} branch
                                                            @elseif($isCommerce)
                                                                {{ (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user / {{ (int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0) }} branch
                                                            @elseif($isCrm)
                                                                {{ (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user / {{ (int) ($limits[\App\Support\PlanLimit::CRM_PIPELINES] ?? 0) }} pipeline
                                                            @else
                                                                {{ ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0 ? 'Termasuk kuota + top up tersedia' : 'Belum termasuk' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="plan-metric h-100">
                                                        <div class="text-muted mb-1">{{ $isAccounting ? 'Kapasitas data' : ($isCommerce ? 'Kapasitas data' : ($isCrm ? 'Deals / stages' : 'WhatsApp')) }}</div>
                                                        <div class="fw-semibold">
                                                            @if($isAccounting)
                                                                {{ number_format((int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }} produk / {{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }} kontak
                                                            @elseif($isCommerce)
                                                                {{ number_format((int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }} produk / {{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }} kontak
                                                            @elseif($isCrm)
                                                                {{ number_format((int) ($limits[\App\Support\PlanLimit::CRM_ACTIVE_DEALS] ?? 0), 0, ',', '.') }} deals / {{ number_format((int) ($limits[\App\Support\PlanLimit::CRM_CUSTOM_STAGES] ?? 0), 0, ',', '.') }} stages
                                                            @else
                                                                {{ !empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]) ? 'Hubungkan akun WhatsApp Anda sendiri' : 'Belum termasuk' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            @if(!empty($sales['highlights']))
                                                <div class="small border-top pt-3 mt-3">
                                                    <div class="fw-semibold mb-2">Yang Anda dapatkan</div>
                                                    @foreach ($sales['highlights'] as $highlight)
                                                        <div class="mb-1">- {{ $highlight }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                @if($plans->isEmpty())
                    <div class="alert alert-warning mb-0">
                        Paket {{ $productLineLabel }} belum tersedia untuk pendaftaran publik saat ini.
                    </div>
                @else
                    <div class="card onboarding-section-card mb-4">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-dark text-white">Setelah klik lanjut</span>
                                <div class="fw-semibold">Yang akan sistem tampilkan</div>
                            </div>
                            <div class="small text-muted" x-show="signupMode === 'paid'">
                                Sistem akan membuat workspace dengan status pending payment, membuat invoice platform, lalu mengarahkan Anda ke Midtrans atau menampilkan instruksi transfer manual sesuai metode yang dipilih.
                            </div>
                            <div class="small text-muted" x-show="signupMode === 'trial'">
                                Sistem akan langsung membuat workspace trial yang aktif, mengirim email sambutan, lalu mengarahkan Anda ke login workspace.
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card form-section-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="form-section-title">Data Workspace</div>
                        <div class="text-muted small mb-3">Isi identitas bisnis dan subdomain workspace yang akan dipakai tim Anda.</div>

                        <div class="mb-3">
                            <x-label for="company_name" :value="__('Nama Perusahaan / Bisnis')" />
                            <x-input id="company_name" type="text" name="company_name" :value="old('company_name')" required autofocus />
                        </div>

                        <div class="mb-0">
                            <x-label for="slug" :value="__('Subdomain')" />
                            <div class="input-group">
                                <x-input
                                    id="slug"
                                    type="text"
                                    name="slug"
                                    :value="old('slug')"
                                    placeholder="namabisnis"
                                    pattern="[a-z0-9][a-z0-9\\-]*[a-z0-9]"
                                    title="Huruf kecil, angka, dan tanda hubung"
                                    required
                                    style="border-right:0"
                                />
                                <span class="input-group-text text-muted">.{{ config('multitenancy.saas_domain') }}</span>
                            </div>
                            <div class="form-hint">Hanya huruf kecil, angka, dan tanda hubung. Tidak bisa diubah setelah daftar.</div>
                        </div>
                    </div>
                </div>

                <div class="card form-section-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="form-section-title">Akun Admin</div>
                        <div class="text-muted small mb-3">Akun ini akan menjadi admin utama yang pertama kali masuk ke workspace.</div>

                        <div class="mb-3">
                            <x-label for="name" :value="__('Nama Anda')" />
                            <x-input id="name" type="text" name="name" :value="old('name')" required />
                        </div>

                        <div class="mb-3">
                            <x-label for="email" :value="__('Email')" />
                            <x-input id="email" type="email" name="email" :value="old('email')" required />
                        </div>

                        <div class="mb-3">
                            <x-label for="password" :value="__('Password')" />
                            <x-input id="password" type="password" name="password" required autocomplete="new-password" />
                        </div>

                        <div class="mb-0">
                            <x-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                            <x-input id="password_confirmation" type="password" name="password_confirmation" required />
                        </div>
                    </div>
                </div>

                <div class="card form-section-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="form-section-title">Promo & Persetujuan</div>
                        <div class="text-muted small mb-3">Tambahkan kode promo bila ada, lalu konfirmasi persetujuan sebelum lanjut.</div>

                        <div class="mb-4">
                            <label class="form-label">Kode Promo <span class="text-muted small fw-normal">(opsional)</span></label>
                            <input
                                type="text"
                                name="promo_code"
                                id="promo_code"
                                class="form-control text-uppercase @error('promo_code') is-invalid @enderror"
                                value="{{ old('promo_code', $promoCode ?? '') }}"
                                placeholder="Masukkan kode promo jika ada"
                                autocomplete="off"
                                style="letter-spacing:0.08em;"
                            >
                            @error('promo_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">Contoh: <strong>MEETRA2ND</strong> untuk promo anniversary 50% off.</div>
                        </div>

                        <div class="mb-0">
                            <label class="form-check">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted'))>
                                <span class="form-check-label">
                                    Saya menyetujui <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a> dan <a href="{{ route('terms') }}" target="_blank" rel="noopener">Syarat &amp; Ketentuan</a>.
                                </span>
                            </label>
                            @error('terms_accepted')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card form-section-card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="form-section-title">Metode Pembayaran</div>
                        <div class="text-muted small mb-3">Pilih cara aktivasi yang paling nyaman untuk tim Anda.</div>

                <div class="mb-0" x-show="signupMode === 'paid'">
                    <label class="form-label fw-semibold">Pilih pembayaran</label>
                    <div class="vstack gap-3">
                        @if($midtransReady)
                            <label
                                class="payment-option-card card p-3"
                                :class="paymentMethod === 'midtrans' ? 'border-primary shadow-sm' : ''"
                                style="transition: border-color 0.15s, box-shadow 0.15s;"
                            >
                                <div class="d-flex align-items-start gap-3">
                                    <input
                                        class="form-check-input mt-1"
                                        type="radio"
                                        name="payment_method"
                                        value="midtrans"
                                        x-model="paymentMethod"
                                        :disabled="signupMode !== 'paid'"
                                        @checked($defaultPaymentMethod === 'midtrans')
                                        required
                                    >
                                    <div>
                                        <div class="fw-semibold">Midtrans</div>
                                        <div class="text-muted small">Pembayaran online. Aktivasi otomatis setelah pembayaran sukses.</div>
                                        <div class="small mt-1">Cocok jika Anda ingin langsung checkout sekarang.</div>
                                    </div>
                                </div>
                            </label>
                        @endif

                        @if($manualPaymentReady)
                            <label
                                class="payment-option-card card p-3"
                                :class="paymentMethod === 'bank_transfer' ? 'border-primary shadow-sm' : ''"
                                style="transition: border-color 0.15s, box-shadow 0.15s;"
                            >
                                <div class="d-flex align-items-start gap-3">
                                    <input
                                        class="form-check-input mt-1"
                                        type="radio"
                                        name="payment_method"
                                        value="bank_transfer"
                                        x-model="paymentMethod"
                                        :disabled="signupMode !== 'paid'"
                                        @checked($defaultPaymentMethod === 'bank_transfer')
                                        required
                                    >
                                    <div class="w-100">
                                        <div class="fw-semibold">Transfer Bank Manual</div>
                                        <div class="text-muted small">Pembayaran manual dengan nominal unik. Detail rekening akan ditampilkan pada invoice setelah pendaftaran dibuat.</div>
                                    </div>
                                </div>
                            </label>
                        @endif

                        @if(!$midtransReady && !$manualPaymentReady)
                            <div class="alert alert-warning mb-0">
                                Belum ada metode pembayaran yang tersedia saat ini. Hubungi tim kami untuk proses aktivasi manual.
                            </div>
                        @endif
                    </div>
                </div>
                    </div>
                </div>

                @if($trialEnabled)
                    <div class="alert alert-info py-2 px-3 small mb-4" x-show="signupMode === 'trial'">
                        Free trial {{ $trialLength }} hari hanya berlaku untuk Accounting. Pilih plan bulanan yang ingin dicoba, lalu workspace akan aktif tanpa checkout.
                    </div>
                @endif

                <x-button class="w-100" :disabled="$plans->isEmpty() || (!$trialEnabled && !$midtransReady && !$manualPaymentReady)" x-bind:disabled="signupMode === 'paid' && !hasPaymentOptions">
                    <span x-show="signupMode === 'paid'">{{ __('Lanjut ke Pembayaran') }}</span>
                    <span x-show="signupMode === 'trial'">Mulai Free Trial</span>
                </x-button>
            </div>
        </form>
        @endif
    </x-auth-card>
</x-guest-layout>
