<x-guest-layout>
    <x-auth-card>
        @php
            $money = app(\App\Support\MoneyFormatter::class);
        @endphp
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <h2 class="h4 mb-1">Jualan omnichannel mulai dari sini</h2>
        <p class="text-muted small mb-4">Pilih paket, buat workspace, lalu lanjutkan ke pembayaran untuk mengaktifkan modul yang Anda beli.</p>
        @if(!empty($affiliate))
            <div class="alert alert-info py-2 px-3 small">
                Anda mendaftar melalui partner affiliate <strong>{{ $affiliate->name }}</strong>.
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.store') }}">
            @csrf

            <div class="mb-4" x-data="{ selected: {{ old('subscription_plan_id', $preferredPlanId ?: ($plans->first()->id ?? 0)) }} }">
                <label class="form-label fw-semibold">Pilih paket</label>
                <div class="row g-3">
                    @foreach ($plans as $plan)
                        @php
                            $sales = $plan->sales_meta ?? [];
                            $features = (array) ($plan->features ?? []);
                            $limits = (array) ($plan->limits ?? []);
                            $priceCurrency = strtoupper((string) ($sales['currency'] ?? 'IDR'));
                            $recommended = (bool) ($sales['recommended'] ?? false);
                            $fit = (string) ($sales['audience'] ?? 'Paket omnichannel');
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
                                                    @checked(old('subscription_plan_id', $preferredPlanId ?: ($loop->first ? $plan->id : null)) == $plan->id)
                                                    required
                                                >
                                                <span class="fw-semibold">{{ $sales['display_name'] ?? $plan->display_name }}</span>
                                                @if($recommended)
                                                    <span class="badge bg-primary-lt text-primary">Paling populer</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small mt-2">{{ $sales['tagline'] ?? 'Paket omnichannel' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold fs-5">{{ $money->format((float) ($sales['price'] ?? 0), $priceCurrency) }}</div>
                                            <div class="text-muted small">/{{ $plan->billing_interval_label }}</div>
                                        </div>
                                    </div>

                                    @if(!empty($sales['description']))
                                        <div class="small text-muted mb-2">{{ $sales['description'] }}</div>
                                    @endif

                                    <div class="small mb-2 text-dark">{{ $fit }}</div>

                                    <div class="d-flex flex-wrap gap-2 small mb-3">
                                        <span class="badge bg-light text-dark border">Social Inbox</span>
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
                                    </div>

                                    <div class="row g-2 small mb-3">
                                        <div class="col-sm-6">
                                            <div class="border rounded p-2 h-100">
                                                <div class="text-muted mb-1">AI</div>
                                                <div class="fw-semibold">{{ ($limits[\App\Support\PlanLimit::AI_CREDITS_MONTHLY] ?? 0) > 0 ? 'Termasuk kuota + top up tersedia' : 'Belum termasuk' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="border rounded p-2 h-100">
                                                <div class="text-muted mb-1">WhatsApp</div>
                                                <div class="fw-semibold">{{ !empty($features[\App\Support\PlanFeature::WHATSAPP_API]) || !empty($features[\App\Support\PlanFeature::WHATSAPP_WEB]) ? 'Hubungkan akun WhatsApp Anda sendiri' : 'Belum termasuk' }}</div>
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

            <x-button class="w-100">
                {{ __('Lanjut ke Pembayaran') }}
            </x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
