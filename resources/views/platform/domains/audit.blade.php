@extends('layouts.admin')

@section('title', 'Tenant Domain Audit')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-secondary text-uppercase fw-bold small">Platform Owner</div>
            <h1 class="page-title mb-1">Tenant Domain Audit</h1>
            <div class="text-muted">Daftar domain yang blocked, failed, atau kehilangan akses control-plane.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('platform.domains.index') }}" class="btn btn-outline-secondary">Kembali ke Domains</a>
        </div>
    </div>

    @if($settings->last_error_summary)
        <div class="alert alert-warning">{{ $settings->last_error_summary }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Hostname</th>
                        <th>Status</th>
                        <th>Error Code</th>
                        <th>Error Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($problematicDomains as $domain)
                        <tr>
                            <td>{{ $domain->tenant?->name ?? ('Tenant #' . $domain->tenant_id) }}</td>
                            <td><code>{{ $domain->normalizedHostname() }}</code></td>
                            <td>{{ $domain->status }}</td>
                            <td>{{ $domain->last_error_code ?: '-' }}</td>
                            <td>{{ $domain->last_error_message ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">Tidak ada domain bermasalah saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
