<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo />
            </a>
        </x-slot>

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

            {{-- Plan selection with Alpine.js interactive state --}}
            <div class="mb-4" x-data="{ selected: {{ old('subscription_plan_id', $preferredPlanId ?: ($plans->first()->id ?? 0)) }} }">
                <label class="form-label fw-semibold">Pilih paket</label>
                <div class="row g-3">
                    @foreach ($plans as $plan)
                        @php($sales = $plan->sales_meta ?? [])
                        <div class="col-12">
                            <label
                                class="card h-100 cursor-pointer"
                                :class="selected == {{ $plan->id }} ? 'border-primary shadow-sm' : 'border'"
                                style="transition: border-color 0.15s, box-shadow 0.15s;"
                            >
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
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
                                                <span class="fw-semibold">{{ $plan->name }}</span>
                                            </div>
                                            <div class="text-muted small mt-2">{{ $sales['tagline'] ?? 'Paket omnichannel' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold fs-5">Rp {{ number_format((float) ($sales['price'] ?? 0), 0, ',', '.') }}</div>
                                            <div class="text-muted small">/{{ $plan->billing_interval ?: 'sekali bayar' }}</div>
                                        </div>
                                    </div>

                                    @if(!empty($sales['description']))
                                        <div class="small text-muted mb-2">{{ $sales['description'] }}</div>
                                    @endif

                                    @if(!empty($sales['highlights']))
                                        <div class="small">
                                            @foreach ($sales['highlights'] as $highlight)
                                                <div class="mb-1">• {{ $highlight }}</div>
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

            {{-- Company / Tenant name --}}
            <div class="mb-3">
                <x-label for="company_name" :value="__('Nama Perusahaan / Bisnis')" />
                <x-input id="company_name" type="text" name="company_name" :value="old('company_name')" required autofocus />
            </div>

            {{-- Subdomain slug --}}
            <div class="mb-3">
                <x-label for="slug" :value="__('Subdomain')" />
                <div class="input-group">
                    <x-input id="slug" type="text"
                        name="slug"
                        :value="old('slug')"
                        placeholder="namabisnis"
                        pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
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

            {{-- Admin name --}}
            <div class="mb-3">
                <x-label for="name" :value="__('Nama Anda')" />
                <x-input id="name" type="text" name="name" :value="old('name')" required />
            </div>

            {{-- Admin email --}}
            <div class="mb-3">
                <x-label for="email" :value="__('Email')" />
                <x-input id="email" type="email" name="email" :value="old('email')" required />
            </div>

            {{-- Admin password --}}
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
