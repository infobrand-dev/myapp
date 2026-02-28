<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - {{ config('app.name', 'MyApp') }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-body">
<div class="page">
    <div class="container-xl py-4">
        <div class="text-center mb-4">
            <h1 class="h2 mb-1">Setup Wizard</h1>
            <p class="text-muted mb-0">Lengkapi konfigurasi aplikasi, uji koneksi database, lalu jalankan instalasi.</p>
        </div>

        @if(request('message'))
            <div class="alert alert-{{ request('level', 'info') === 'success' ? 'success' : (request('level') === 'error' ? 'danger' : 'info') }} mb-3" role="alert">
                {{ request('message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('install.run') }}">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">System Check</h3>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <span>PHP Version (&gt;= 8.2)</span>
                                <span class="badge {{ $checks['php_ok'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $checks['php_version'] }}</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <span>.env writable</span>
                                <span class="badge {{ $checks['env_writable'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $checks['env_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <span>storage writable</span>
                                <span class="badge {{ $checks['storage_writable'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $checks['storage_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <span>bootstrap/cache writable</span>
                                <span class="badge {{ $checks['cache_writable'] ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $checks['cache_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <div class="text-muted text-uppercase fw-bold small mb-2">Extensions</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($checks['extensions'] as $ext => $ok)
                                    <span class="badge {{ $ok ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                        {{ $ext }}: {{ $ok ? 'OK' : 'NO' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Configuration</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">App Name</label>
                                    <input type="text" class="form-control" name="app_name" value="{{ old('app_name', $defaults['app_name']) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">App URL</label>
                                    <input type="url" class="form-control" name="app_url" value="{{ old('app_url', $defaults['app_url']) }}" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <h4 class="mb-1">Database</h4>
                                <div class="text-muted">Data ini dipakai untuk koneksi aplikasi ke MySQL.</div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">DB Host</label>
                                    <input type="text" class="form-control" name="db_host" value="{{ old('db_host', $defaults['db_host']) }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">DB Port</label>
                                    <input type="number" class="form-control" name="db_port" value="{{ old('db_port', $defaults['db_port']) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DB Name</label>
                                    <input type="text" class="form-control" name="db_database" value="{{ old('db_database', $defaults['db_database']) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DB User</label>
                                    <input type="text" class="form-control" name="db_username" value="{{ old('db_username', $defaults['db_username']) }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">DB Password</label>
                                    <input type="password" class="form-control" name="db_password" value="{{ old('db_password', $defaults['db_password']) }}">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <h4 class="mb-1">Super-admin Account</h4>
                                <div class="text-muted">Akun ini dipakai login pertama kali setelah instalasi selesai.</div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="admin_name" value="{{ old('admin_name', $defaults['admin_name']) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="admin_email" value="{{ old('admin_email', $defaults['admin_email']) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="admin_password" required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-column flex-sm-row gap-2 justify-content-between">
                            <button
                                class="btn btn-outline-secondary"
                                type="submit"
                                formaction="{{ route('install.test-db') }}"
                                formnovalidate
                            >
                                Test Database
                            </button>
                            <button class="btn btn-primary" type="submit">Run Installation</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>
