<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo />
            </a>
        </x-slot>

        <h2 class="h4 mb-1">Konfirmasi password</h2>
        <p class="text-muted small mb-4">
            Ini adalah area aman. Masukkan password Anda untuk melanjutkan.
        </p>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <x-label for="password" :value="__('Password')" />
                <x-input id="password" type="password" name="password" required autocomplete="current-password" />
            </div>

            <x-button class="w-100">
                {{ __('Konfirmasi') }}
            </x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
