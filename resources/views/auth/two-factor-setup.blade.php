@extends('layouts.admin')

@section('title', 'Aktifkan Verifikasi Dua Langkah')

@section('content')
<div class="container-xl">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Aktifkan Verifikasi Dua Langkah (2FA)</h2>
                <div class="text-secondary mt-1">Tambahkan lapisan keamanan ekstra ke akun Anda.</div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">

                    <x-auth-validation-errors :errors="$errors" />

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Step 1: Scan QR --}}
                    <h3 class="card-title mb-3">Langkah 1 — Scan QR Code</h3>
                    <p class="text-secondary mb-3">
                        Buka aplikasi autentikator (Google Authenticator, Authy, Microsoft Authenticator)
                        lalu scan QR code berikut.
                    </p>

                    <div class="text-center mb-4">
                        {!! $qrCodeInline !!}
                    </div>

                    <p class="text-secondary small mb-4">
                        Tidak bisa scan? Masukkan kode manual:
                        <code class="user-select-all fw-bold">{{ $secret }}</code>
                    </p>

                    <hr class="my-4">

                    {{-- Step 2: Confirm code --}}
                    <h3 class="card-title mb-3">Langkah 2 — Konfirmasi Kode</h3>
                    <p class="text-secondary mb-3">
                        Masukkan kode 6 digit yang muncul di aplikasi autentikator untuk mengonfirmasi setup.
                    </p>

                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="code">Kode Verifikasi</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                class="form-control text-center @error('code') is-invalid @enderror"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                placeholder="000000"
                                autofocus
                            >
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-shield-check me-1"></i> Aktifkan 2FA
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="{{ route('profile.edit') }}" class="text-secondary small">Batalkan</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
