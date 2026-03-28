<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo />
            </a>
        </x-slot>

        <h2 class="h4 mb-1">Verifikasi email Anda</h2>
        <p class="text-muted small mb-4">
            Terima kasih sudah mendaftar. Silakan klik link verifikasi yang sudah kami kirim ke email Anda sebelum mulai menggunakan aplikasi.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4">
                <i class="ti ti-circle-check me-2"></i>
                Link verifikasi baru sudah dikirim ke email Anda.
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between gap-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button>
                    {{ __('Kirim Ulang Email Verifikasi') }}
                </x-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link text-secondary text-decoration-none p-0">
                    {{ __('Keluar') }}
                </button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
