@extends('layouts.admin')

@section('title', 'Go-Live Audit')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-secondary text-uppercase fw-bold small">Platform Owner</div>
            <h1 class="page-title mb-1">Go-Live Audit</h1>
            <div class="text-muted">Pantau konfigurasi production, queue, billing platform, dan secret yang masih kurang dari panel `dash`.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
            <a href="{{ route('platform.orders.index') }}" class="btn btn-outline-secondary">Orders</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Overall</div>
                    <div class="fs-2 fw-bold mt-2 {{ $ready ? 'text-success' : 'text-danger' }}">{{ $ready ? 'Ready' : 'Not Ready' }}</div>
                    <div class="text-muted small mt-1">{{ $stats['total'] }} checks scanned</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Fail</div>
                    <div class="fs-1 fw-bold mt-2 text-danger">{{ $stats['fail'] }}</div>
                    <div class="text-muted small mt-1">Harus ditutup sebelum launch</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Warn</div>
                    <div class="fs-1 fw-bold mt-2 text-warning">{{ $stats['warn'] }}</div>
                    <div class="text-muted small mt-1">Tidak blok langsung, tapi sebaiknya dirapikan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Pass</div>
                    <div class="fs-1 fw-bold mt-2 text-success">{{ $stats['pass'] }}</div>
                    <div class="text-muted small mt-1">Sudah aman</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Automatic Audit</h3>
                </div>
                <div class="card-body text-muted">
                    Bagian ini membaca konfigurasi runtime dan kondisi tabel langsung dari aplikasi. Item `FAIL` harus nol sebelum launch.
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Launch Runbook</h3>
                </div>
                <div class="card-body">
                    <div class="text-muted">Ikuti `GO_LIVE_RUNBOOK.md` di repo untuk urutan queue worker, scheduler, DNS, webhook, dan smoke test produksi.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================
         Background Services
         Tiga proses server yang harus berjalan di production.
         Bridge dicek otomatis; queue & scheduler manual.
         ==================================================== --}}
    @php
        $bridgeUrlCheck     = collect($checks)->firstWhere('key', 'whatsapp_web_bridge_url');
        $bridgeRuntimeCheck = collect($checks)->firstWhere('key', 'whatsapp_web_bridge_runtime');
        $showBridgeSection  = $bridgeUrlCheck !== null;
        $bridgeOnline       = $bridgeRuntimeCheck ? ($bridgeRuntimeCheck['status'] === 'pass') : null;
        $bridgeUrlMissing   = $bridgeUrlCheck ? ($bridgeUrlCheck['status'] !== 'pass') : false;
    @endphp

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Background Services</h3>
        </div>
        <div class="card-body text-muted small mb-0 pb-0">
            Tiga proses ini harus berjalan di server secara bersamaan. Queue worker dan scheduler tidak dapat dicek secara otomatis dari sini — verifikasi manual wajib dilakukan sebelum launch.
        </div>
        <div class="list-group list-group-flush">

            {{-- 1. Queue Worker --}}
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-stack-2 mt-1" style="font-size:1.3rem; color:var(--tblr-azure);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">Laravel Queue Worker</span>
                            <span class="badge bg-secondary-lt text-secondary">Perlu dicek manual</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Memproses job asynchronous: pengiriman pesan WhatsApp, email, blast campaign, dll.
                            Tanpa worker ini semua job tertumpuk di antrian dan tidak tereksekusi.
                        </p>
                        <code class="d-block bg-dark text-white px-3 py-2 rounded small">
                            php artisan queue:work --tries=3 --timeout=120
                        </code>
                        <div class="text-muted small mt-1">
                            Gunakan <strong>Supervisor</strong> atau <strong>PM2</strong> agar otomatis restart jika crash.
                            Cek item <code>queue_connection</code> di tabel audit di bawah.
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Scheduler --}}
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-clock mt-1" style="font-size:1.3rem; color:var(--tblr-azure);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">Laravel Scheduler (Cron)</span>
                            <span class="badge bg-secondary-lt text-secondary">Perlu dicek manual</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Menjalankan task terjadwal: health check WhatsApp instance setiap 10 menit,
                            dispatch blast terjadwal setiap menit, pruning webhook payload setiap malam.
                        </p>
                        <code class="d-block bg-dark text-white px-3 py-2 rounded small">
                            * * * * * php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1
                        </code>
                    </div>
                </div>
            </div>

            {{-- 3. WhatsApp Web Bridge (hanya tampil jika modul aktif) --}}
            @if($showBridgeSection)
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-brand-whatsapp mt-1" style="font-size:1.3rem; color:var(--tblr-green);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">Node.js WhatsApp Web Bridge</span>
                            @if($bridgeOnline === true)
                                <span class="badge bg-green-lt text-green">Online</span>
                            @elseif($bridgeOnline === false)
                                <span class="badge bg-red-lt text-red">Offline</span>
                            @elseif($bridgeUrlMissing)
                                <span class="badge bg-orange-lt text-orange">URL belum diisi</span>
                            @else
                                <span class="badge bg-secondary-lt text-secondary">Tidak dicek</span>
                            @endif
                        </div>
                        <p class="text-muted small mb-2">
                            Server Node.js yang menghubungkan WhatsApp Web (non-API) dengan aplikasi.
                            Harus berjalan di luar PHP — proses terpisah di server.
                        </p>

                        @if($bridgeOnline === false || $bridgeUrlMissing)
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Start bridge (production via PM2):</div>
                            <code class="d-block bg-dark text-white px-3 py-2 rounded small">
                                pm2 start server.js --name wa-bridge --cwd {{ base_path('app/Modules/WhatsAppWeb/node') }}
                            </code>
                        </div>
                        @endif

                        @if($bridgeUrlCheck && $bridgeUrlCheck['status'] !== 'pass')
                        <div class="text-muted small mb-1">
                            <i class="ti ti-alert-triangle text-orange me-1"></i>
                            {{ $bridgeUrlCheck['hint'] }}
                        </div>
                        @endif

                        @if($bridgeRuntimeCheck && $bridgeRuntimeCheck['status'] !== 'pass')
                        <div class="text-muted small mb-2">
                            <i class="ti ti-alert-circle text-red me-1"></i>
                            {{ $bridgeRuntimeCheck['hint'] }}
                            @if($bridgeRuntimeCheck['value'] !== '-')
                                <code class="ms-1">{{ $bridgeRuntimeCheck['value'] }}</code>
                            @endif
                        </div>
                        @endif

                        @if($bridgeOnline !== true)
                        <div class="mt-2">
                            <a href="{{ route('whatsappweb.settings.edit') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-settings me-1"></i>Buka WhatsApp Web Settings
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @if(!$ready)
        <div class="alert alert-danger mb-4">
            Masih ada blocker go-live. Fokus utamanya adalah item `FAIL`, terutama tenancy, session/cookie, queue, billing platform, mail, dan Midtrans.
        </div>
    @endif

    @if($stats['warn'] > 0)
        <div class="alert alert-warning mb-4">
            Ada item `WARN` yang tidak langsung memblokir launch, tapi akan lebih aman jika dirapikan sebelum trafik naik.
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Audit Checks</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Check</th>
                        <th>Current Value</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checks as $check)
                        @php
                            $badge = match ($check['status']) {
                                'pass' => 'bg-success-lt text-success',
                                'warn' => 'bg-warning-lt text-warning',
                                default => 'bg-danger-lt text-danger',
                            };
                        @endphp
                        <tr>
                            <td><span class="badge {{ $badge }}">{{ strtoupper($check['status']) }}</span></td>
                            <td class="fw-semibold">{{ $check['label'] }}</td>
                            <td><code>{{ $check['value'] }}</code></td>
                            <td class="text-muted">{{ $check['hint'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Manual Infra Checklist</h3>
        </div>
        <div class="list-group list-group-flush">
            @foreach($manualChecks as $label => $description)
                <div class="list-group-item">
                    <div class="fw-semibold">{{ $label }}</div>
                    <div class="text-muted small mt-1">{{ $description }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
