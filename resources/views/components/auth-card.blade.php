<div class="auth-card-wrapper">
    {{-- Brand header --}}
    <div class="auth-brand">
        {{ $logo }}
        <x-app-logo variant="default" :height="44" class="auth-brand-logo mb-2" />
        <div class="auth-brand-name">Workspace {{ config('app.name') }}</div>
    </div>

    {{-- Auth card --}}
    <div class="card auth-card">
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>

    {{-- Footer --}}
    <p class="text-center text-secondary small mt-3 mb-0">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>
</div>
