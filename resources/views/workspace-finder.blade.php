<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | Login Workspace</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-body">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="text-center mb-4">
                    <h1 class="h2 mb-2">Masuk ke Workspace</h1>
                    <p class="text-muted mb-0">Masukkan subdomain workspace Anda untuk diarahkan ke halaman login tenant.</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('workspace.redirect') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="workspace" class="form-label">Nama Workspace</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        id="workspace"
                                        name="workspace"
                                        value="{{ old('workspace') }}"
                                        class="form-control @error('workspace') is-invalid @enderror"
                                        placeholder="contoh: tokosaya"
                                        autocomplete="off"
                                        autofocus
                                    >
                                    <span class="input-group-text">.{{ config('multitenancy.saas_domain') }}</span>
                                    @error('workspace')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Lanjut ke Login</button>
                        </form>

                        <div class="mt-3 text-center small text-muted">
                            Belum punya workspace?
                            <a href="{{ route('onboarding.create') }}" class="text-decoration-none">Daftar sekarang</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
