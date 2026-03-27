<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - {{ config('app.name', 'MyApp') }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-body">
<style>
    .install-submit-spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 999px;
        display: inline-block;
        animation: install-spin .7s linear infinite;
    }

    @keyframes install-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
<div class="page">
    <div class="container-xl py-4">
        <div class="text-center mb-4">
            <h1 class="h2 mb-1">Setup Wizard</h1>
            <p class="text-muted mb-0">Lengkapi konfigurasi aplikasi, uji koneksi database, lalu jalankan instalasi.</p>
        </div>

        @if(!empty($statusMessage))
            <div class="alert alert-{{ $statusLevel === 'success' ? 'success' : ($statusLevel === 'error' ? 'danger' : 'info') }} mb-3" role="alert">
                {{ $statusMessage }}
            </div>
        @endif

        <form method="POST" action="{{ route('install.run') }}" id="install-form">
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
                                    <input type="text" class="form-control" name="app_name" value="{{ $defaults['app_name'] }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">App URL</label>
                                    <input type="url" class="form-control" name="app_url" value="{{ $defaults['app_url'] }}" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <h4 class="mb-1">Database</h4>
                                <div class="text-muted">Pilih driver database. Untuk Supabase gunakan <code>pgsql</code> dan isi <code>sslmode=require</code>.</div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">DB Driver</label>
                                    <select class="form-select" name="db_connection" required>
                                        <option value="mysql" @selected($defaults['db_connection'] === 'mysql')>MySQL</option>
                                        <option value="pgsql" @selected($defaults['db_connection'] === 'pgsql')>PostgreSQL / Supabase</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DB Host</label>
                                    <input type="text" class="form-control" name="db_host" value="{{ $defaults['db_host'] }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">DB Port</label>
                                    <input type="number" class="form-control" name="db_port" value="{{ $defaults['db_port'] }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DB Name</label>
                                    <input type="text" class="form-control" name="db_database" value="{{ $defaults['db_database'] }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DB User</label>
                                    <input type="text" class="form-control" name="db_username" value="{{ $defaults['db_username'] }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">DB Password</label>
                                    <input type="password" class="form-control" name="db_password" value="{{ $defaults['db_password'] }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DB SSL Mode</label>
                                    <input type="text" class="form-control" name="db_sslmode" value="{{ $defaults['db_sslmode'] }}" placeholder="prefer / require">
                                    <div class="form-hint">Kosongkan untuk MySQL biasa. Untuk Supabase umumnya <code>require</code>.</div>
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
                                    <input type="text" class="form-control" name="admin_name" value="{{ $defaults['admin_name'] }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="admin_email" value="{{ $defaults['admin_email'] }}" required>
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
                                data-submit-label="Testing database..."
                            >
                                Test Database
                            </button>
                            <button class="btn btn-primary" type="submit" data-submit-label="Running installation...">Run Installation</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="{{ mix('js/app.js') }}" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('install-form');

        if (!form) {
            return;
        }

        const buttons = Array.from(form.querySelectorAll('button[type="submit"]'));

        form.addEventListener('submit', function (event) {
            const submitter = event.submitter;

            if (!submitter) {
                return;
            }

            buttons.forEach(function (button) {
                button.disabled = true;
            });

            const spinner = document.createElement('span');
            spinner.className = 'install-submit-spinner me-2';
            spinner.setAttribute('aria-hidden', 'true');

            const label = submitter.getAttribute('data-submit-label') || 'Processing...';
            submitter.dataset.originalHtml = submitter.innerHTML;
            submitter.innerHTML = '';
            submitter.appendChild(spinner);
            submitter.appendChild(document.createTextNode(label));
        });
    });
</script>
</body>
</html>
