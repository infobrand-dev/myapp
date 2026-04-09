<x-guest-layout>
    <x-auth-card>
        @php
            $money = app(\App\Support\MoneyFormatter::class);
            $productLineKey = strtolower((string) ($productLine ?? 'accounting'));
            $isAccounting = $productLineKey === 'accounting';
            $trialRequested = !empty($trialRequested);
            $lineTitle = $isAccounting ? 'Mulai workspace Accounting Anda' : 'Mulai workspace Omnichannel Anda';
            $lineIntro = $trialRequested
                ? 'Mulai free trial 14 hari untuk paket Accounting pilihan Anda. Tidak perlu bayar di awal.'
                : ($isAccounting
                ? 'Pilih paket Accounting, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan workflow transaksi yang Anda butuhkan.'
                : 'Pilih paket Omnichannel, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan modul yang Anda beli.');
            $intervalHeadings = $isAccounting
                ? [
                    'monthly' => [
                        'title' => 'Paket Bulanan',
                        'description' => 'Cocok untuk mulai lebih cepat dan melihat paket mana yang paling pas untuk ritme operasional bisnis Anda.',
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
                ];
        @endphp
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <h2 class="h4 mb-1">{{ $lineTitle }}</h2>
        <p class="text-muted small mb-4">{{ $lineIntro }}</p>
        @if(!empty($affiliate))
            <div class="alert alert-info py-2 px-3 small">
                Anda mendaftar melalui partner affiliate <strong>{{ $affiliate->name }}</strong>.
            </div>
        @endif
        @if($trialRequested)
            <div class="alert alert-success py-2 px-3 small">
                Free trial aktif selama <strong>14 hari</strong>. Setelah form dikirim, workspace langsung dibuat dan siap login.
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.store') }}">
            @csrf
            <input type="hidden" name="product_line" value="{{ $productLineKey }}">
            @if($trialRequested)
                <input type="hidden" name="trial" value="1">
            @endif

            <div class="mb-4" x-data="{ selected: {{ old('subscription_plan_id', $preferredPlanId ?: ($plans->first()->id ?? 0)) }} }">
                @php
                    $plansByInterval = $plans->groupBy(fn ($plan) => $plan->billing_interval);
                    $intervalOrder = ['monthly', 'semiannual', 'yearly'];
                @endphp
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
                                    $recommended = (bool) ($sales['recommended'] ?? false);
                                    $fit = (string) ($sales['audience'] ?? ($isAccounting ? 'Paket accounting untuk operasional bisnis' : 'Paket omnichannel'));
                                @endphp
                                <div class="col-12">
                                    <label
                                        class="card h-100 cursor-pointer"
                                        :class="selected == {{ $plan->id }} ? 'border-primary shadow-sm' : 'border'"
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
                                                            :value="{{ $plan->id }}"
                                                            @checked(old('subscription_plan_id', $preferredPlanId ?: ($loop->first && $loop->parent->first ? $plan->id : null)) == $plan->id)
                                                            required
                                                        >
                                                        <span class="fw-semibold">{{ $sales['display_name'] ?? $plan->display_name }}</span>
                                                        <span class="badge bg-light text-dark border">{{ $plan->billing_interval_label }}</span>
                                                        @if($recommended)
                                                            <span class="badge bg-primary-lt text-primary">Paling populer</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small mt-2">{{ $sales['tagline'] ?? 'Paket omnichannel' }}</div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold fs-5">{{ $money->format((float) ($sales['price'] ?? 0), $priceCurrency) }}</div>
                                                    <div class="text-muted small">Paket {{ strtolower($plan->billing_interval_label) }}</div>
                                                </div>
                                            </div>

                                            @if(!empty($sales['description']))
                                                <div class="small text-muted mb-2">{{ $sales['description'] }}</div>
                                            @endif

                                            <div class="small mb-2 text-dark">{{ $fit }}</div>

                                            <div class="d-flex flex-wrap gap-2 small mb-3">
                                                @if($isAccounting)
                                                    <span class="badge bg-light text-dark border">Sales</span>
                                                    <span class="badge bg-light text-dark border">Payments</span>
                                                    <span class="badge bg-light text-dark border">Finance</span>
                                                    <span class="badge bg-light text-dark border">Products</span>
                                                    <span class="badge bg-light text-dark border">Contacts</span>
                                                    @if(!empty($features[\App\Support\PlanFeature::PURCHASES]))
                                                        <span class="badge bg-light text-dark border">Purchases</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::INVENTORY]))
                                                        <span class="badge bg-light text-dark border">Inventory</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::ADVANCED_REPORTS]))
                                                        <span class="badge bg-light text-dark border">Full Reports</span>
                                                    @else
                                                        <span class="badge bg-light text-dark border">Basic Reports</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-dark border">Instagram / Facebook DM</span>
                                                    <span class="badge bg-light text-dark border">Live Chat</span>
                                                    <span class="badge bg-light text-dark border">CRM Lite</span>
                                                    @if(!empty($features[\App\Support\PlanFeature::CHATBOT_AI]))
                                                        <span class="badge bg-light text-dark border">AI</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_API]))
                                                        <span class="badge bg-light text-dark border">WhatsApp API</span>
                                                    @endif
                                                    @if(!empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]))
                                                        <span class="badge bg-light text-dark border">WhatsApp Web</span>
                                                    @endif
                                                @endif
                                            </div>

                                            <div class="row g-2 small mb-3">
                                                <div class="col-sm-6">
                                                    <div class="border rounded p-2 h-100">
                                                        <div class="text-muted mb-1">{{ $isAccounting ? 'Users & branch' : 'AI' }}</div>
                                                        <div class="fw-semibold">
                                                            @if($isAccounting)
                                                                {{ (int) ($limits[\App\Support\PlanLimit::USERS] ?? 0) }} user • {{ (int) ($limits[\App\Support\PlanLimit::BRANCHES] ?? 0) }} branch
                                                            @else
                                                                {{ ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0 ? 'Termasuk kuota + top up tersedia' : 'Belum termasuk' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="border rounded p-2 h-100">
                                                        <div class="text-muted mb-1">{{ $isAccounting ? 'Kapasitas data' : 'WhatsApp' }}</div>
                                                        <div class="fw-semibold">
                                                            @if($isAccounting)
                                                                {{ number_format((int) ($limits[\App\Support\PlanLimit::PRODUCTS] ?? 0), 0, ',', '.') }} produk • {{ number_format((int) ($limits[\App\Support\PlanLimit::CONTACTS] ?? 0), 0, ',', '.') }} kontak
                                                            @else
                                                                {{ !empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]) ? 'Hubungkan akun WhatsApp Anda sendiri' : 'Belum termasuk' }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            @if(!empty($sales['highlights']))
                                                <div class="small">
                                                    @foreach ($sales['highlights'] as $highlight)
                                                        <div class="mb-1">&bull; {{ $highlight }}</div>
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
                @endif
            </div>

            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="border-top flex-grow-1"></div>
                <span class="text-muted small px-2">Data workspace</span>
                <div class="border-top flex-grow-1"></div>
            </div>

            <div class="mb-3">
                <x-label for="company_name" :value="__('Nama Perusahaan / Bisnis')" />
                <x-input id="company_name" type="text" name="company_name" :value="old('company_name')" required autofocus />
            </div>

            <div class="mb-3">
                <x-label for="slug" :value="__('Subdomain')" />
                <div class="input-group">
                    <x-input id="slug" type="text"
                        name="slug"
                        :value="old('slug')"
                        placeholder="namabisnis"
                        pattern="[a-z0-9][a-z0-9\\-]*[a-z0-9]"
                        title="Huruf kecil, angka, dan tanda hubung"
                        required
                        style="border-right:0" />
                    <span class="input-group-text text-muted">.{{ config('multitenancy.saas_domain') }}</span>
                </div>
                <div class="form-hint">Hanya huruf kecil, angka, dan tanda hubung. Tidak bisa diubah setelah daftar.</div>
            </div>

            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="border-top flex-grow-1"></div>
                <span class="text-muted small px-2">Akun admin</span>
                <div class="border-top flex-grow-1"></div>
            </div>

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

            <div class="mb-4">
                <x-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                <x-input id="password_confirmation" type="password" name="password_confirmation" required />
            </div>

            @if(!$trialRequested)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="border-top flex-grow-1"></div>
                    <span class="text-muted small px-2">Metode pembayaran</span>
                    <div class="border-top flex-grow-1"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Pilih pembayaran</label>
                    <div class="vstack gap-3">
                        @if($midtransReady)
                            <label class="card border p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <input
                                        class="form-check-input mt-1"
                                        type="radio"
                                        name="payment_method"
                                        value="midtrans"
                                        @checked(old('payment_method', $manualPaymentReady ? 'midtrans' : 'bank_transfer') === 'midtrans')
                                        required
                                    >
                                    <div>
                                        <div class="fw-semibold">Midtrans</div>
                                        <div class="text-muted small">Pembayaran online. Aktivasi otomatis setelah pembayaran sukses.</div>
                                    </div>
                                </div>
                            </label>
                        @endif

                        @if($manualPaymentReady)
                            <label class="card border p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <input
                                        class="form-check-input mt-1"
                                        type="radio"
                                        name="payment_method"
                                        value="bank_transfer"
                                        @checked(old('payment_method') === 'bank_transfer')
                                        required
                                    >
                                    <div>
                                        <div class="fw-semibold">Transfer Bank</div>
                                        <div class="text-muted small">Pembayaran manual dengan nominal unik. Aktivasi maksimal 1x24 jam setelah verifikasi.</div>
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
            @endif

            <x-button class="w-100" :disabled="$plans->isEmpty()">
                {{ $trialRequested ? __('Mulai Free Trial 14 Hari') : __('Lanjut ke Pembayaran') }}
            </x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
