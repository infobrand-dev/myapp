<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <x-application-logo />
        </x-slot>

        <div class="mb-4 text-center">
            <h2 class="h4 fw-bold mb-1">Verifikasi Dua Langkah</h2>
            <p class="text-secondary small">Masukkan kode dari aplikasi autentikator Anda.</p>
        </div>

        <x-auth-validation-errors :errors="$errors" />

        {{-- TOTP code form --}}
        <div id="totp-section">
            <form method="POST" action="{{ route('two-factor.challenge') }}">
                @csrf
                <div class="mb-3">
                    <x-label for="code" :value="__('Kode Autentikator')" />
                    <x-input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        placeholder="000 000"
                        class="text-center letter-spacing-wide"
                    />
                </div>
                <div class="d-grid">
                    <x-button>Verifikasi</x-button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="#" class="small text-secondary" id="toggle-recovery">
                    Tidak punya akses ke aplikasi autentikator? Gunakan kode pemulihan
                </a>
            </div>
        </div>

        {{-- Recovery code form (hidden by default) --}}
        <div id="recovery-section" style="display:none;">
            <form method="POST" action="{{ route('two-factor.challenge') }}">
                @csrf
                <div class="mb-3">
                    <x-label for="recovery_code" :value="__('Kode Pemulihan')" />
                    <x-input
                        id="recovery_code"
                        name="recovery_code"
                        type="text"
                        autocomplete="off"
                        placeholder="XXXXX-XXXXX"
                    />
                </div>
                <div class="d-grid">
                    <x-button>Gunakan Kode Pemulihan</x-button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="#" class="small text-secondary" id="toggle-totp">
                    Kembali ke kode autentikator
                </a>
            </div>
        </div>
    </x-auth-card>

    @push('scripts')
    <script>
        document.getElementById('toggle-recovery').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('totp-section').style.display = 'none';
            document.getElementById('recovery-section').style.display = 'block';
            document.getElementById('recovery_code').focus();
        });
        document.getElementById('toggle-totp').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('recovery-section').style.display = 'none';
            document.getElementById('totp-section').style.display = 'block';
            document.getElementById('code').focus();
        });
    </script>
    @endpush
</x-guest-layout>
