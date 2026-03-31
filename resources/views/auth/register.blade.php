<x-guest-layout>
    <x-auth-card>
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <x-label for="name" :value="__('Nama')" />
                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus />
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

            <div class="d-flex align-items-center justify-content-between gap-2">
                <a class="text-secondary small" href="{{ route('login') }}">
                    Sudah punya akun?
                </a>
                <x-button>
                    {{ __('Daftar') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
