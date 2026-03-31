<x-guest-layout>
    <x-auth-card>
        <h2 class="h4 mb-1">Lupa password?</h2>
        <p class="text-muted small mb-4">
            Masukkan email Anda dan kami akan mengirimkan link untuk membuat password baru.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <x-label for="email" :value="__('Email')" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2">
                <a class="text-secondary small" href="{{ route('login') }}">
                    Kembali ke login
                </a>
                <x-button>
                    {{ __('Kirim Link Reset') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
