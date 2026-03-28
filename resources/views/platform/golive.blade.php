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
