<x-guest-layout>
    <x-auth-card>
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <div class="alert alert-info mb-4 py-2 px-3 small">
            Anda diundang ke workspace <strong>{{ optional($invitation->tenant)->name }}</strong> sebagai <strong>{{ $invitation->role_name }}</strong>.
        </div>

        <form method="POST" action="{{ route('register.invitations.store', $invitation) }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <x-label for="name" :value="__('Nama')" />
                <x-input id="name" type="text" name="name" :value="old('name', $invitation->name)" required autofocus />
            </div>

            <div class="mb-3">
                <x-label for="email" :value="__('Email')" />
                <x-input id="email" type="email" :value="$invitation->email" disabled />
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
                {{ __('Aktifkan Akun') }}
            </x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
