<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Installer - {{ config('app.name', 'MyApp') }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="d-flex flex-column bg-light min-vh-100">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="mb-1">Setup Wizard</h2>
                    <div class="text-muted">Lengkapi konfigurasi aplikasi, test database, lalu jalankan instalasi.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">System Check</h3></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>PHP Version (>= 8.2)</span>
                                <span class="badge {{ $checks['php_ok'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $checks['php_version'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>.env writable</span>
                                <span class="badge {{ $checks['env_writable'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $checks['env_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>storage writable</span>
                                <span class="badge {{ $checks['storage_writable'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $checks['storage_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span>bootstrap/cache writable</span>
                                <span class="badge {{ $checks['cache_writable'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $checks['cache_writable'] ? 'OK' : 'NO' }}</span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        @foreach($checks['extensions'] as $ext => $ok)
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between">
                                    <span>{{ $ext }}</span>
                                    <span class="badge {{ $ok ? 'text-bg-success' : 'text-bg-danger' }}">{{ $ok ? 'OK' : 'NO' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('install.run') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title mb-0">Configuration</h3></div>
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

                    <hr>
                    <h4 class="mb-3">Database</h4>
                    <div class="row g-3">
                        <div class="col-md-3">
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
                        <div class="col-md-2">
                            <label class="form-label">DB User</label>
                            <input type="text" class="form-control" name="db_username" value="{{ old('db_username', $defaults['db_username']) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">DB Password</label>
                            <input type="password" class="form-control" name="db_password" value="{{ old('db_password', $defaults['db_password']) }}">
                        </div>
                    </div>

                    <hr>
                    <h4 class="mb-3">Super-admin Account</h4>
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
                <div class="card-footer d-flex gap-2">
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
            </form>
        </div>
    </div>
</div>
<script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>

