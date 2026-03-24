@extends('layouts.admin')

@section('title', 'Kode Pemulihan 2FA')

@section('content')
<div class="container-xl">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Kode Pemulihan</h2>
                <div class="text-secondary mt-1">Simpan kode ini di tempat yang aman.</div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">

            @if (session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif

            <div class="alert alert-warning mb-4">
                <div class="d-flex">
                    <div class="me-3"><i class="ti ti-alert-triangle fs-2"></i></div>
                    <div>
                        <h4 class="alert-title">Simpan kode ini sekarang!</h4>
                        <p class="mb-0">
                            Kode pemulihan hanya ditampilkan <strong>sekali</strong>.
                            Jika Anda kehilangan akses ke aplikasi autentikator, kode ini
                            adalah satu-satunya cara untuk masuk. Setiap kode hanya bisa dipakai satu kali.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-2">
                        @foreach ($codes as $code)
                            <div class="col-6">
                                <code class="d-block text-center py-2 px-3 bg-light rounded user-select-all">
                                    {{ $code }}
                                </code>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        onclick="navigator.clipboard.writeText(`{{ implode('\n', $codes) }}`).then(() => AppToast.success('Disalin ke clipboard'))"
                    >
                        <i class="ti ti-copy me-1"></i> Salin semua
                    </button>
                    <a
                        href="data:text/plain;charset=utf-8,{{ rawurlencode(implode("\n", $codes)) }}"
                        download="{{ config('app.name') }}-recovery-codes.txt"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="ti ti-download me-1"></i> Download
                    </a>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Saya sudah menyimpan kode ini
                </a>
            </div>

            <hr class="my-4">

            {{-- Regenerate section --}}
            <div class="card border-danger">
                <div class="card-header text-danger fw-semibold">Buat Ulang Kode Pemulihan</div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">
                        Membuat kode baru akan <strong>menghapus semua kode lama</strong>.
                        Masukkan password untuk konfirmasi.
                    </p>
                    <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}">
                        @csrf
                        <div class="mb-3">
                            <input
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Password Anda"
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="ti ti-refresh me-1"></i> Buat Kode Baru
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
