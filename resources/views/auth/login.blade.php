<x-guest-layout>
    <x-auth-card>
        @if (config('multitenancy.mode') === 'saas' && ($saasLoginTenant = \App\Support\TenantContext::currentTenant()))
            <div class="alert alert-info mb-3 py-2 px-3 small">
                <i class="ti ti-building me-1"></i>
                Masuk ke <strong>{{ $saasLoginTenant->name }}</strong>
            </div>
        @endif

        @if (request()->attributes->get('platform_admin_host'))
            <div class="alert alert-warning mb-3 py-2 px-3 small">
                <i class="ti ti-shield-lock me-1"></i>
                Login khusus platform admin.
            </div>
        @endif

        <x-auth-session-status class="mb-4" :status="session('status')" />
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <x-label for="email" :value="__('Email')" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            </div>

            <div class="mb-3">
                <x-label for="password" :value="__('Password')" />
                <x-input id="password" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="mb-3">
                <label class="form-check">
                    <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                    <span class="form-check-label text-secondary">Ingat saya</span>
                </label>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2 mt-4">
                @if (Route::has('password.request'))
                    <a class="text-secondary small" href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
                <x-button class="ms-auto">
                    {{ __('Masuk') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
