<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo />
            </a>
        </x-slot>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <h2 class="h4 mb-1">Daftar akun baru</h2>
        <p class="text-muted small mb-4">Buat tenant Anda dan mulai dalam hitungan menit.</p>

        <form method="POST" action="{{ route('onboarding.store') }}">
            @csrf

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

            <hr class="my-3">

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
                {{ __('Buat Akun') }}
            </x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
