<x-guest-layout>
{{-- Onboarding: wider wrapper for wizard layout --}}
<div class="auth-card-wrapper ob-wrapper">

    {{-- Brand --}}
    <div class="auth-brand">
        <x-app-logo variant="default" :height="44" class="auth-brand-logo" />
        <div class="auth-brand-name">{{ config('app.name') }}</div>
    </div>

    <div class="card auth-card">
        <div class="card-body">
            @php
                $money                = app(\App\Support\MoneyFormatter::class);
                $selectedProductLineKey = strtolower((string) ($selectedProductLine ?? ''));
                $selectionRequired    = !in_array($selectedProductLineKey, ['accounting', 'commerce', 'omnichannel', 'crm'], true);
                $productLineKey       = $selectionRequired ? '' : $selectedProductLineKey;
                $isAccounting         = $productLineKey === 'accounting';
                $isCommerce           = $productLineKey === 'commerce';
                $isOmnichannel        = $productLineKey === 'omnichannel';
                $isCrm                = $productLineKey === 'crm';
                $trialEnabled         = (bool) ($trialAvailable ?? false);
                $trialLength          = (int)  ($trialDays ?? 14);
                $promoDiscountPercent = !empty($promoPreview) ? (int) ($promoPreview->discount_percent ?? 0) : 0;
                $selectedPlanId       = old('subscription_plan_id', $preferredPlanId ?: ($plans->first()->id ?? 0));
                $defaultSignupMode    = old('signup_mode', $trialEnabled ? 'trial' : 'paid');
                $defaultPaymentMethod = old('payment_method', $midtransReady ? 'midtrans' : ($manualPaymentReady ? 'bank_transfer' : ''));

                // Determine initial step when returning from server-side validation
                $initialStep = 1;
                if ($errors->any()) {
                    foreach (['promo_code', 'terms_accepted', 'payment_method'] as $_f) {
                        if ($errors->has($_f)) { $initialStep = 3; break; }
                    }
                    if ($initialStep < 3) {
                        foreach (['company_name', 'slug', 'name', 'email', 'password'] as $_f) {
                            if ($errors->has($_f)) { $initialStep = 2; break; }
                        }
                    }
                }

                // Interval headings per suite
                $intervalHeadings = $isAccounting
                    ? [
                        'monthly'    => ['title' => 'Paket Bulanan',    'description' => 'Cocok untuk mulai cepat dan melihat paket mana yang paling pas.'],
                        'semiannual' => ['title' => 'Paket 6 Bulanan',  'description' => 'Untuk tim yang ingin biaya lebih stabil beberapa bulan ke depan.'],
                        'yearly'     => ['title' => 'Paket Tahunan',    'description' => 'Untuk bisnis yang yakin menjalankan accounting sepanjang tahun.'],
                      ]
                    : ($isCommerce
                    ? [
                        'monthly'    => ['title' => 'Paket Bulanan',    'description' => 'Mulai cepat membuka storefront tanpa setup yang ribet.'],
                        'semiannual' => ['title' => 'Paket 6 Bulanan',  'description' => 'Untuk bisnis yang ingin biaya stabil sambil menata operasional order.'],
                        'yearly'     => ['title' => 'Paket Tahunan',    'description' => 'Untuk bisnis yang serius menjadikan commerce sebagai kanal utama.'],
                      ]
                    : ($isCrm
                    ? [
                        'monthly'    => ['title' => 'Paket Bulanan',    'description' => 'Untuk tim sales yang ingin langsung go-live.'],
                      ]
                    : [
                        'monthly'    => ['title' => 'Paket Bulanan',    'description' => 'Paling fleksibel untuk mulai jalan dan menyesuaikan ritme tim.'],
                        'semiannual' => ['title' => 'Paket 6 Bulanan',  'description' => 'Lebih tenang beberapa bulan ke depan tanpa repot perpanjang tiap bulan.'],
                        'yearly'     => ['title' => 'Paket Tahunan',    'description' => 'Paling stabil untuk bisnis yang siap menjalankan omnichannel sepanjang tahun.'],
                      ]));
            @endphp

            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            @if(!empty($affiliate))
                <div class="alert alert-info py-2 px-3 small mb-3">
                    Anda mendaftar melalui partner affiliate <strong>{{ $affiliate->name }}</strong>.
                </div>
            @endif

            @if($promoDiscountPercent > 0)
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    Promo <strong>{{ $promoCode }}</strong> aktif — potongan <strong>{{ $promoDiscountPercent }}%</strong> sudah termasuk dalam harga di bawah.
                </div>
            @endif

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- SUITE PICKER — shown when no suite is selected yet       --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            @if($selectionRequired)
                <div class="ob-panel">
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="ob-step-dot is-active">1</div>
                            <div class="ob-step-dot">2</div>
                            <div class="ob-step-dot">3</div>
                        </div>
                        <h2 class="h5 fw-bold mb-1">Pilih business suite</h2>
                        <p class="text-muted small mb-0">
                            Pilih dulu, baru sistem tampilkan plan yang relevan di langkah berikutnya.
                        </p>
                    </div>

                    <div class="vstack gap-2">
                        @foreach(($productLineOptions ?? []) as $option)
                            <a
                                href="{{ $option['available'] ? $option['url'] : '#' }}"
                                class="ob-suite-card p-3 {{ !$option['available'] ? 'is-disabled' : '' }}"
                                @if(!$option['available']) aria-disabled="true" @endif
                            >
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold mb-1">{{ $option['label'] }}</div>
                                        <div class="text-muted small">{{ $option['description'] }}</div>
                                    </div>
                                    <span class="badge flex-shrink-0 {{ $option['available'] ? 'bg-primary-lt text-primary' : 'bg-secondary-lt text-secondary' }}">
                                        {{ $option['available'] ? 'Pilih' : 'Belum tersedia' }}
                                    </span>
                                </div>
                                @if(!empty($option['highlights']))
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @foreach(array_slice($option['highlights'], 0, 4) as $hl)
                                            <span class="ob-feat-chip">{{ $hl }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- 3-STEP WIZARD — shown after suite is selected            --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            @else
                <form method="POST" action="{{ route('onboarding.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="product_line" value="{{ $productLineKey }}">
                    @if(!empty($trialEntry))
                        <input type="hidden" name="trial_entry" value="{{ $trialEntry }}">
                    @endif
                    @unless($trialEnabled)
                        <input type="hidden" name="signup_mode" value="paid">
                    @endunless

                    {{-- Progress indicator --}}
                    <div class="mb-4">
                        <div class="ob-progress-track">
                            <div class="ob-progress-fill" id="ob-fill" style="width: {{ ($initialStep / 3 * 100) }}%"></div>
                        </div>
                        <div class="ob-progress-steps">
                            <div class="ob-progress-step">
                                <div class="ob-step-dot {{ $initialStep === 1 ? 'is-active' : 'is-done' }}" id="ob-dot-1">
                                    {{ $initialStep > 1 ? '✓' : '1' }}
                                </div>
                                <div class="ob-step-label {{ $initialStep === 1 ? 'is-active' : 'is-done' }}" id="ob-lbl-1">Pilih plan</div>
                            </div>
                            <div class="ob-progress-step">
                                <div class="ob-step-dot {{ $initialStep === 2 ? 'is-active' : ($initialStep > 2 ? 'is-done' : '') }}" id="ob-dot-2">
                                    {{ $initialStep > 2 ? '✓' : '2' }}
                                </div>
                                <div class="ob-step-label {{ $initialStep === 2 ? 'is-active' : ($initialStep > 2 ? 'is-done' : '') }}" id="ob-lbl-2">Data bisnis</div>
                            </div>
                            <div class="ob-progress-step">
                                <div class="ob-step-dot {{ $initialStep === 3 ? 'is-active' : '' }}" id="ob-dot-3">3</div>
                                <div class="ob-step-label {{ $initialStep === 3 ? 'is-active' : '' }}" id="ob-lbl-3">Aktivasi</div>
                            </div>
                        </div>
                    </div>

                    {{-- Alpine reactive scope shared across all steps --}}
                    @php
                        // Interval tab helpers
                        $plansByIntervalAll = $plans->groupBy(fn($p) => $p->billing_interval);
                        $selectedPlanModel  = $plans->firstWhere('id', $selectedPlanId);
                        $defaultInterval    = $selectedPlanModel?->billing_interval ?? 'monthly';

                        // Compute savings % for semiannual / yearly vs cheapest monthly
                        $cheapestMonthlyPrice = (float) optional(
                            $plansByIntervalAll->get('monthly', collect())
                                ->sortBy(fn($p) => (float)($p->sales_meta['price'] ?? 0))
                                ->first()
                        )->sales_meta['price'] ?? 0;

                        $intervalSavingsPct = [];
                        $savingsMonths = ['semiannual' => 6, 'yearly' => 12];
                        foreach ($savingsMonths as $intKey => $months) {
                            $cheapest = $plansByIntervalAll->get($intKey, collect())
                                ->sortBy(fn($p) => (float)($p->sales_meta['price'] ?? 0))
                                ->first();
                            if ($cheapest && $cheapestMonthlyPrice > 0) {
                                $intPrice = (float)($cheapest->sales_meta['price'] ?? 0);
                                if ($intPrice > 0) {
                                    $saved = ($cheapestMonthlyPrice * $months) - $intPrice;
                                    $pct   = (int) round(($saved / ($cheapestMonthlyPrice * $months)) * 100);
                                    if ($pct > 0) $intervalSavingsPct[$intKey] = $pct;
                                }
                            }
                        }

                        // Available intervals (only those with plans + headings defined)
                        $intervalOrderAll   = ['monthly', 'semiannual', 'yearly'];
                        $availableIntervals = array_values(array_filter(
                            $intervalOrderAll,
                            fn($k) => isset($intervalHeadings[$k]) && $plansByIntervalAll->get($k, collect())->isNotEmpty()
                        ));
                        $hasMultipleIntervals = count($availableIntervals) > 1;

                        // If default interval has no plans, fall back to first available
                        if (!in_array($defaultInterval, $availableIntervals, true)) {
                            $defaultInterval = $availableIntervals[0] ?? 'monthly';
                        }
                    @endphp

                    <div
                        x-data="{
                            selected: {{ $selectedPlanId }},
                            paymentMethod: '{{ $defaultPaymentMethod }}',
                            signupMode: '{{ $defaultSignupMode }}',
                            activeInterval: '{{ $defaultInterval }}',
                            hasPaymentOptions: {{ ($midtransReady || $manualPaymentReady) ? 'true' : 'false' }},
                            planSummaries: @js($plans->mapWithKeys(function ($plan) use ($money, $promoDiscountPercent) {
                                $sales         = (array) ($plan->sales_meta ?? []);
                                $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                                $basePrice     = (float) ($sales['price'] ?? 0);
                                $finalPrice    = $promoDiscountPercent > 0
                                    ? round($basePrice * (1 - ($promoDiscountPercent / 100)), 2)
                                    : $basePrice;
                                return [
                                    (string) $plan->id => [
                                        'name'     => (string) ($sales['display_name'] ?? $plan->display_name),
                                        'price'    => (string) $money->format($finalPrice, $priceCurrency),
                                        'interval' => (string) $plan->billing_interval_label,
                                    ],
                                ];
                            })),
                            get planSummary() {
                                return this.planSummaries[String(this.selected)] ?? null;
                            },
                            init() {
                                // Auto-switch to monthly tab when trial mode is selected
                                this.$watch('signupMode', (val) => {
                                    if (val === 'trial') this.activeInterval = 'monthly';
                                });
                            },
                        }"
                    >

                        {{-- ═══════════════════════════════════════════ --}}
                        {{-- STEP 1 — Plan Selection                    --}}
                        {{-- ═══════════════════════════════════════════ --}}
                        <div class="ob-panel" id="ob-step-1" {{ $initialStep !== 1 ? 'hidden' : '' }}>

                            {{-- Suite strip --}}
                            <div class="ob-suite-strip mb-4">
                                <span><strong>Suite:</strong> {{ $productLineLabel }}</span>
                                <a href="{{ route('onboarding.create') }}" class="text-muted small text-decoration-none">
                                    Ganti suite
                                </a>
                            </div>

                            {{-- Trial / Paid toggle --}}
                            @if($trialEnabled)
                                <div class="mb-4">
                                    <div class="small fw-semibold text-uppercase text-muted mb-2" style="letter-spacing:.06em;">Cara mulai</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="ob-mode-card p-3" :class="signupMode === 'trial' ? 'is-selected' : ''">
                                                <div class="d-flex gap-2 align-items-start">
                                                    <input class="form-check-input mt-0 flex-shrink-0" type="radio"
                                                           name="signup_mode" value="trial" x-model="signupMode">
                                                    <div>
                                                        <div class="fw-semibold small">Trial {{ $trialLength }} Hari</div>
                                                        <div class="text-muted" style="font-size:.72rem;">Aktif tanpa checkout</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <label class="ob-mode-card p-3" :class="signupMode === 'paid' ? 'is-selected' : ''">
                                                <div class="d-flex gap-2 align-items-start">
                                                    <input class="form-check-input mt-0 flex-shrink-0" type="radio"
                                                           name="signup_mode" value="paid" x-model="signupMode">
                                                    <div>
                                                        <div class="fw-semibold small">Berlangganan</div>
                                                        <div class="text-muted" style="font-size:.72rem;">Invoice + checkout</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Plan cards --}}
                            @if($plans->isEmpty())
                                <div class="alert alert-warning">
                                    Paket {{ $productLineLabel }} belum tersedia untuk pendaftaran publik saat ini.
                                </div>
                            @else
                                <div class="small fw-semibold text-uppercase text-muted mb-2" style="letter-spacing:.06em;">Pilih paket</div>

                                {{-- Interval tabs — only rendered when more than one billing period exists --}}
                                @if($hasMultipleIntervals)
                                    <div class="ob-interval-tabs mb-3">
                                        @foreach($availableIntervals as $intKey)
                                            @php $intMeta = $intervalHeadings[$intKey] ?? null; @endphp
                                            @if($intMeta)
                                                <button type="button" class="ob-interval-tab"
                                                        :class="activeInterval === '{{ $intKey }}' ? 'is-active' : ''"
                                                        @click="activeInterval = '{{ $intKey }}'">
                                                    {{ $intMeta['title'] }}
                                                    @if(isset($intervalSavingsPct[$intKey]))
                                                        <span class="ob-interval-tab-badge">Hemat {{ $intervalSavingsPct[$intKey] }}%</span>
                                                    @endif
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Plan panels per interval --}}
                                @foreach($availableIntervals as $intKey)
                                    @php
                                        $intervalPlans = $plansByIntervalAll->get($intKey, collect())->sortBy('sort_order')->values();
                                        $intervalMeta  = $intervalHeadings[$intKey] ?? null;
                                    @endphp

                                    {{-- Wrap in x-show only when multiple intervals exist --}}
                                    @if($hasMultipleIntervals)
                                        <div x-show="activeInterval === '{{ $intKey }}'"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             style="{{ $intKey !== $defaultInterval ? 'display:none' : '' }}">
                                    @endif

                                        {{-- Description of this billing period --}}
                                        @if($intervalMeta)
                                            <div class="ob-interval-desc-box">{{ $intervalMeta['description'] }}</div>
                                        @endif

                                        @foreach($intervalPlans as $plan)
                                            @php
                                                $sales         = $plan->sales_meta ?? [];
                                                $features      = (array) ($plan->features ?? []);
                                                $limits        = (array) ($plan->limits ?? []);
                                                $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                                                $basePrice     = (float) ($sales['price'] ?? 0);
                                                $promoPrice    = $promoDiscountPercent > 0
                                                    ? round($basePrice * (1 - ($promoDiscountPercent / 100)), 2)
                                                    : $basePrice;
                                                $recommended   = (bool) ($sales['recommended'] ?? false);
                                                $fit           = (string) ($sales['audience'] ?? (
                                                    $isAccounting  ? 'Paket accounting untuk operasional bisnis' : (
                                                    $isCommerce    ? 'Paket commerce untuk operasional order' : (
                                                    $isCrm         ? 'Paket CRM untuk operasional sales' :
                                                                     'Paket omnichannel'))));
                                            @endphp

                                            <label
                                                class="ob-plan-card p-3"
                                                :class="{
                                                    'is-selected': selected == {{ $plan->id }},
                                                    'is-muted': signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'
                                                }"
                                            >
                                                <div class="d-flex justify-content-between align-items-start gap-3">
                                                    <div class="d-flex gap-2 align-items-start min-w-0">
                                                        <input
                                                            class="form-check-input mt-0 flex-shrink-0"
                                                            type="radio"
                                                            name="subscription_plan_id"
                                                            value="{{ $plan->id }}"
                                                            x-model="selected"
                                                            :disabled="signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'"
                                                            @checked($selectedPlanId == $plan->id)
                                                            required
                                                        >
                                                        <div class="min-w-0">
                                                            <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                                                <span class="fw-semibold small">{{ $sales['display_name'] ?? $plan->display_name }}</span>
                                                                @if($recommended)
                                                                    <span class="badge bg-primary-lt text-primary">Populer</span>
                                                                @endif
                                                                <span class="badge bg-warning-lt text-warning"
                                                                      x-show="signupMode === 'trial' && '{{ $plan->billing_interval }}' !== 'monthly'"
                                                                      x-cloak>Trial N/A</span>
                                                            </div>
                                                            <div class="text-muted" style="font-size:.75rem;">{{ $fit }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end flex-shrink-0">
                                                        @if($promoDiscountPercent > 0)
                                                            <div class="text-muted text-decoration-line-through" style="font-size:.72rem;">
                                                                {{ $money->format($basePrice, $priceCurrency) }}
                                                            </div>
                                                            <div class="fw-bold">{{ $money->format($promoPrice, $priceCurrency) }}</div>
                                                            <div class="small text-danger fw-semibold">-{{ $promoDiscountPercent }}%</div>
                                                        @else
                                                            <div class="fw-bold">{{ $money->format($basePrice, $priceCurrency) }}</div>
                                                        @endif
                                                        <div class="text-muted" style="font-size:.7rem;">per {{ strtolower($plan->billing_interval_label) }}</div>
                                                    </div>
                                                </div>

                                                {{-- Feature chips --}}
                                                <div class="d-flex flex-wrap gap-1 mt-2">
                                                    @if($isAccounting)
                                                        <span class="ob-feat-chip">Sales</span>
                                                        <span class="ob-feat-chip">Payments</span>
                                                        <span class="ob-feat-chip">Finance</span>
                                                        <span class="ob-feat-chip">Products</span>
                                                        <span class="ob-feat-chip">Contacts</span>
                                                        @if(!empty($features[\App\Support\PlanFeature::PURCHASES]))
                                                            <span class="ob-feat-chip">Purchases</span>
                                                        @endif
                                                        @if(!empty($features[\App\Support\PlanFeature::INVENTORY]))
                                                            <span class="ob-feat-chip">Inventory</span>
                                                        @endif
                                                        <span class="ob-feat-chip">{{ !empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]) ? 'Full Reports' : 'Basic Reports' }}</span>
                                                    @elseif($isCommerce)
                                                        <span class="ob-feat-chip">Storefront</span>
                                                        <span class="ob-feat-chip">Orders</span>
                                                        <span class="ob-feat-chip">Payments</span>
                                                        <span class="ob-feat-chip">Shipping</span>
                                                        <span class="ob-feat-chip">Fulfillment</span>
                                                        <span class="ob-feat-chip">Contacts</span>
                                                    @elseif($isCrm)
                                                        <span class="ob-feat-chip">Customer 360</span>
                                                        <span class="ob-feat-chip">Pipeline</span>
                                                        <span class="ob-feat-chip">Follow-Up</span>
                                                        <span class="ob-feat-chip">Contacts</span>
                                                        @if(!empty($features[\App\Support\PlanFeature::CRM_EXPORTS]))
                                                            <span class="ob-feat-chip">Export</span>
                                                        @endif
                                                        @if(!empty($features[\App\Support\PlanFeature::CRM_MANAGER_VISIBILITY]))
                                                            <span class="ob-feat-chip">Manager View</span>
                                                        @endif
                                                        @if(!empty($features[\App\Support\PlanFeature::CRM_AUTOMATION]))
                                                            <span class="ob-feat-chip">Automation</span>
                                                        @endif
                                                    @else
                                                        <span class="ob-feat-chip">IG / FB DM</span>
                                                        <span class="ob-feat-chip">Live Chat</span>
                                                        <span class="ob-feat-chip">CRM Lite</span>
                                                        @if(!empty($features[\App\Support\PlanFeature::CHATBOT_AI]))
                                                            <span class="ob-feat-chip">AI</span>
                                                        @endif
                                                        @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_API]))
                                                            <span class="ob-feat-chip">WhatsApp API</span>
                                                        @endif
                                                        @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]))
                                                            <span class="ob-feat-chip">WhatsApp Web</span>
                                                        @endif
                                                    @endif
                                                </div>

                                                {{-- Metrics --}}
                                                <div class="row g-2 mt-2">
                                                    <div class="col-6">
                                                        <div class="ob-metric">
                                                            <div class="text-muted mb-1" style="font-size:.68rem;">
                                                                {{ ($isAccounting || $isCommerce) ? 'Users / branch' : ($isCrm ? 'Users / pipelines' : 'AI') }}
                                                            </div>
                                                            <div class="fw-semibold small">
                                                                @if($isAccounting || $isCommerce)
                                                                    {{ (int)($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user
                                                                    / {{ (int)($limits[\App\Support\PlanLimit::BRANCHES] ?? 0) }} branch
                                                                @elseif($isCrm)
                                                                    {{ (int)($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user
                                                                    / {{ (int)($limits[\App\Support\PlanLimit::CRM_PIPELINES] ?? 0) }} pipeline
                                                                @else
                                                                    {{ ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0 ? 'Kuota termasuk' : 'Belum termasuk' }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="ob-metric">
                                                            <div class="text-muted mb-1" style="font-size:.68rem;">
                                                                {{ ($isAccounting || $isCommerce) ? 'Kapasitas data' : ($isCrm ? 'Deals / stages' : 'WhatsApp') }}
                                                            </div>
                                                            <div class="fw-semibold small">
                                                                @if($isAccounting || $isCommerce)
                                                                    {{ number_format((int)($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }} produk
                                                                    / {{ number_format((int)($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }} kontak
                                                                @elseif($isCrm)
                                                                    {{ number_format((int)($limits[\App\Support\PlanLimit::CRM_ACTIVE_DEALS] ?? 0), 0, ',', '.') }} deals
                                                                    / {{ (int)($limits[\App\Support\PlanLimit::CRM_CUSTOM_STAGES] ?? 0) }} stages
                                                                @else
                                                                    {{ (!empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB])) ? 'Hubungkan WA sendiri' : 'Belum termasuk' }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                @if(!empty($sales['highlights']))
                                                    <div class="border-top pt-2 mt-2">
                                                        <div class="fw-semibold mb-1" style="font-size:.72rem;">Yang Anda dapatkan</div>
                                                        @foreach($sales['highlights'] as $hl)
                                                            <div class="text-muted" style="font-size:.72rem;">‒ {{ $hl }}</div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </label>
                                        @endforeach

                                    @if($hasMultipleIntervals)
                                        </div>{{-- end x-show interval panel --}}
                                    @endif
                                @endforeach
                            @endif

                            <div class="ob-step-nav end">
                                <button type="button" class="btn btn-primary px-4" id="btn-next-1"
                                        {{ $plans->isEmpty() ? 'disabled' : '' }}>
                                    Lanjut: Data Bisnis <i class="ti ti-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════════ --}}
                        {{-- STEP 2 — Workspace & Admin Details         --}}
                        {{-- ═══════════════════════════════════════════ --}}
                        <div class="ob-panel" id="ob-step-2" {{ $initialStep < 2 ? 'hidden' : '' }}>

                            {{-- Plan summary chip --}}
                            <div class="ob-chip mb-4" x-show="planSummary" x-cloak>
                                <i class="ti ti-check" style="color:var(--tblr-green);"></i>
                                <span x-text="planSummary ? `${planSummary.name} — ${planSummary.price} / ${planSummary.interval}` : ''"></span>
                            </div>

                            {{-- Workspace section --}}
                            <div class="ob-section">
                                <div class="ob-section-body">
                                    <div class="ob-section-title">Data Workspace</div>
                                    <p class="text-muted small mb-3">Nama bisnis dan subdomain yang akan digunakan tim Anda.</p>

                                    <div class="mb-3">
                                        <label class="form-label" for="company_name">
                                            Nama Perusahaan / Bisnis <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="company_name" name="company_name"
                                               class="form-control @error('company_name') is-invalid @enderror"
                                               value="{{ old('company_name') }}"
                                               required autofocus autocomplete="organization">
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="form-label" for="slug">
                                            Subdomain <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" id="slug" name="slug"
                                                   class="form-control @error('slug') is-invalid @enderror"
                                                   value="{{ old('slug') }}"
                                                   placeholder="namabisnis"
                                                   pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
                                                   title="Huruf kecil, angka, dan tanda hubung"
                                                   required autocomplete="off"
                                                   style="border-right:0">
                                            <span class="input-group-text text-muted">.{{ config('multitenancy.saas_domain') }}</span>
                                        </div>
                                        @error('slug')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="form-hint">Hanya huruf kecil, angka, tanda hubung. Tidak bisa diubah setelah daftar.</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Admin account section --}}
                            <div class="ob-section">
                                <div class="ob-section-body">
                                    <div class="ob-section-title">Akun Admin Utama</div>
                                    <p class="text-muted small mb-3">Akun ini yang pertama masuk ke workspace setelah aktivasi.</p>

                                    <div class="mb-3">
                                        <label class="form-label" for="name">
                                            Nama Anda <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="name" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}"
                                               required autocomplete="name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="email">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" id="email" name="email"
                                               class="form-control @error('email') is-invalid @enderror"
                                               value="{{ old('email') }}"
                                               required autocomplete="email">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="password">
                                            Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" id="password" name="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               required autocomplete="new-password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="form-label" for="password_confirmation">
                                            Konfirmasi Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                               class="form-control"
                                               required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            <div class="ob-step-nav">
                                <button type="button" class="btn btn-outline-secondary" id="btn-prev-2">
                                    <i class="ti ti-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary px-4" id="btn-next-2">
                                    Lanjut: Aktivasi <i class="ti ti-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════════ --}}
                        {{-- STEP 3 — Payment & Activation              --}}
                        {{-- ═══════════════════════════════════════════ --}}
                        <div class="ob-panel" id="ob-step-3" {{ $initialStep < 3 ? 'hidden' : '' }}>

                            {{-- Plan summary chip --}}
                            <div class="ob-chip mb-4" x-show="planSummary" x-cloak>
                                <i class="ti ti-check" style="color:var(--tblr-green);"></i>
                                <span x-text="planSummary ? `${planSummary.name} — ${planSummary.price} / ${planSummary.interval}` : ''"></span>
                            </div>

                            {{-- Trial notice --}}
                            @if($trialEnabled)
                                <div class="alert alert-info py-2 px-3 small mb-3" x-show="signupMode === 'trial'" x-cloak>
                                    <i class="ti ti-info-circle me-1"></i>
                                    Free trial <strong>{{ $trialLength }} hari</strong> — workspace langsung aktif tanpa checkout. Berlaku untuk plan bulanan.
                                </div>
                            @endif

                            {{-- Payment method --}}
                            <div x-show="signupMode === 'paid'" x-cloak>
                                @if($midtransReady || $manualPaymentReady)
                                    <div class="ob-section">
                                        <div class="ob-section-body">
                                            <div class="ob-section-title">Metode Pembayaran</div>
                                            <p class="text-muted small mb-3">Pilih cara aktivasi yang paling nyaman.</p>

                                            @if($midtransReady)
                                                <label class="ob-pay-card p-3"
                                                       :class="paymentMethod === 'midtrans' ? 'is-selected' : ''">
                                                    <div class="d-flex gap-3 align-items-start">
                                                        <input class="form-check-input mt-1 flex-shrink-0"
                                                               type="radio" name="payment_method" value="midtrans"
                                                               x-model="paymentMethod"
                                                               :disabled="signupMode !== 'paid'"
                                                               @checked($defaultPaymentMethod === 'midtrans')
                                                               required>
                                                        <div>
                                                            <div class="fw-semibold small">Midtrans</div>
                                                            <div class="text-muted" style="font-size:.75rem;">
                                                                Pembayaran online — aktivasi otomatis setelah transaksi sukses.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endif

                                            @if($manualPaymentReady)
                                                <label class="ob-pay-card p-3"
                                                       :class="paymentMethod === 'bank_transfer' ? 'is-selected' : ''">
                                                    <div class="d-flex gap-3 align-items-start">
                                                        <input class="form-check-input mt-1 flex-shrink-0"
                                                               type="radio" name="payment_method" value="bank_transfer"
                                                               x-model="paymentMethod"
                                                               :disabled="signupMode !== 'paid'"
                                                               @checked($defaultPaymentMethod === 'bank_transfer')
                                                               required>
                                                        <div>
                                                            <div class="fw-semibold small">Transfer Bank Manual</div>
                                                            <div class="text-muted" style="font-size:.75rem;">
                                                                Nominal unik — detail rekening ditampilkan di invoice setelah daftar.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-3">
                                        Belum ada metode pembayaran tersedia saat ini. Hubungi tim kami untuk aktivasi manual.
                                    </div>
                                @endif
                            </div>

                            {{-- Promo code --}}
                            <div class="ob-section">
                                <div class="ob-section-body">
                                    <div class="ob-section-title">
                                        Kode Promo
                                        <span class="text-muted fw-normal ms-1" style="text-transform:none;font-size:.78rem;">(opsional)</span>
                                    </div>
                                    <input type="text" id="promo_code" name="promo_code"
                                           class="form-control text-uppercase mt-2 @error('promo_code') is-invalid @enderror"
                                           value="{{ old('promo_code', $promoCode ?? '') }}"
                                           placeholder="KODE PROMO"
                                           autocomplete="off"
                                           style="letter-spacing:.08em;">
                                    @error('promo_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint">Contoh: <strong>MEETRA2ND</strong> untuk promo anniversary 50% off.</div>
                                </div>
                            </div>

                            {{-- Terms --}}
                            <div class="ob-section">
                                <div class="ob-section-body">
                                    <label class="form-check">
                                        <input class="form-check-input @error('terms_accepted') is-invalid @enderror"
                                               type="checkbox" name="terms_accepted" value="1"
                                               @checked(old('terms_accepted'))>
                                        <span class="form-check-label small">
                                            Saya menyetujui
                                            <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a>
                                            dan
                                            <a href="{{ route('terms') }}" target="_blank" rel="noopener">Syarat &amp; Ketentuan</a>.
                                        </span>
                                    </label>
                                    @error('terms_accepted')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="ob-step-nav">
                                <button type="button" class="btn btn-outline-secondary" id="btn-prev-3">
                                    <i class="ti ti-arrow-left me-1"></i> Kembali
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary px-4"
                                    :disabled="{{ $plans->isEmpty() || (!$trialEnabled && !$midtransReady && !$manualPaymentReady) ? 'true' : 'false' }} || (signupMode === 'paid' && !hasPaymentOptions)"
                                >
                                    <span x-show="signupMode !== 'trial'">Lanjut ke Pembayaran <i class="ti ti-arrow-right ms-1"></i></span>
                                    <span x-show="signupMode === 'trial'" x-cloak>Mulai Free Trial <i class="ti ti-arrow-right ms-1"></i></span>
                                </button>
                            </div>
                        </div>

                    </div>{{-- end Alpine scope --}}
                </form>
            @endif
        </div>
    </div>

    <p class="text-center text-secondary small mt-3 mb-0">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const TOTAL = 3;
    let step    = {{ $initialStep }};

    const fill   = document.getElementById('ob-fill');
    const dots   = [null, document.getElementById('ob-dot-1'), document.getElementById('ob-dot-2'), document.getElementById('ob-dot-3')];
    const labels = [null, document.getElementById('ob-lbl-1'), document.getElementById('ob-lbl-2'), document.getElementById('ob-lbl-3')];
    const panels = [null, document.getElementById('ob-step-1'), document.getElementById('ob-step-2'), document.getElementById('ob-step-3')];

    /* ── Sync progress indicator ── */
    function syncProgress(n) {
        if (fill) fill.style.width = ((n / TOTAL) * 100) + '%';

        for (let i = 1; i <= TOTAL; i++) {
            const dot = dots[i];
            const lbl = labels[i];
            if (!dot || !lbl) continue;

            dot.className = 'ob-step-dot';
            lbl.className = 'ob-step-label';

            if (i < n) {
                dot.classList.add('is-done');
                dot.textContent = '✓';
                lbl.classList.add('is-done');
            } else if (i === n) {
                dot.classList.add('is-active');
                if (dot.textContent === '✓') dot.textContent = String(i);
                lbl.classList.add('is-active');
            } else {
                if (dot.textContent === '✓') dot.textContent = String(i);
            }
        }
    }

    /* ── Show a step panel with slide animation ── */
    function showStep(n, direction) {
        const current = panels[step];
        const next    = panels[n];
        if (!current || !next) return;

        // Hide current instantly (no need to animate out — enter animation is enough)
        current.hidden = true;
        current.classList.remove('ob-panel', 'from-back');

        // Trigger enter animation on next panel
        next.hidden = false;
        next.classList.remove('ob-panel', 'from-back');
        if (direction === 'back') next.classList.add('from-back');

        void next.offsetWidth; // force reflow so animation restarts
        next.classList.add('ob-panel');

        step = n;
        syncProgress(n);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Step 1 validation: plan must be selected ── */
    function validateStep1() {
        const selected = document.querySelector('input[name="subscription_plan_id"]:checked');
        if (!selected) {
            // Briefly highlight the plan section to indicate selection is needed
            const firstPlan = document.querySelector('.ob-plan-card');
            if (firstPlan) {
                firstPlan.style.transition = 'box-shadow .2s';
                firstPlan.style.boxShadow = '0 0 0 2px #f59e0b';
                setTimeout(() => { firstPlan.style.boxShadow = ''; }, 1200);
            }
            return false;
        }
        return true;
    }

    /* ── Step 2 validation: required fields ── */
    function validateStep2() {
        const fields = ['company_name', 'slug', 'name', 'email', 'password', 'password_confirmation'];
        for (const id of fields) {
            const el = document.getElementById(id);
            if (!el) continue;
            if (!el.value.trim()) {
                el.focus();
                el.classList.add('is-invalid');
                el.addEventListener('input', () => el.classList.remove('is-invalid'), { once: true });
                return false;
            }
        }
        const pw  = document.getElementById('password');
        const pwc = document.getElementById('password_confirmation');
        if (pw && pwc && pw.value !== pwc.value) {
            pwc.classList.add('is-invalid');
            pwc.setCustomValidity('Password dan konfirmasi tidak cocok.');
            pwc.reportValidity();
            pwc.addEventListener('input', () => {
                pwc.classList.remove('is-invalid');
                pwc.setCustomValidity('');
            }, { once: true });
            return false;
        }
        if (pwc) pwc.setCustomValidity('');
        return true;
    }

    /* ── Wire buttons ── */
    const btnNext1 = document.getElementById('btn-next-1');
    const btnNext2 = document.getElementById('btn-next-2');
    const btnPrev2 = document.getElementById('btn-prev-2');
    const btnPrev3 = document.getElementById('btn-prev-3');

    if (btnNext1) btnNext1.addEventListener('click', () => { if (validateStep1()) showStep(2, 'forward'); });
    if (btnNext2) btnNext2.addEventListener('click', () => { if (validateStep2()) showStep(3, 'forward'); });
    if (btnPrev2) btnPrev2.addEventListener('click', () => showStep(1, 'back'));
    if (btnPrev3) btnPrev3.addEventListener('click', () => showStep(2, 'back'));

    // Initialise progress bar on load
    syncProgress(step);
})();
</script>
@endpush
</x-guest-layout>
