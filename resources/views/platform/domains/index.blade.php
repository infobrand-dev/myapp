@extends('layouts.platform')

@section('title', 'Tenant Domains')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-secondary text-uppercase fw-bold small">Platform Owner</div>
            <h1 class="page-title mb-1">Cloudflare for SaaS Domains</h1>
            <div class="text-muted">Owner hanya mengisi koneksi global Cloudflare. Tenant mengelola domain dari Settings masing-masing.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
            <a href="{{ route('platform.domains.audit') }}" class="btn btn-outline-secondary">Audit</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Cloudflare Settings</h3>
                </div>
                <form method="POST" action="{{ route('platform.domains.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Account ID</label>
                                <input type="text" name="account_id" value="{{ old('account_id', $settings->account_id) }}" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Zone ID</label>
                                <input type="text" name="zone_id" value="{{ old('zone_id', $settings->zone_id) }}" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">API Token</label>
                                <textarea name="api_token" rows="3" class="form-control" placeholder="Kosongkan untuk tetap pakai nilai sekarang"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fallback Origin Hostname</label>
                                <input type="text" name="fallback_origin_hostname" value="{{ old('fallback_origin_hostname', $settings->fallback_origin_hostname) }}" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">CNAME Target</label>
                                <input type="text" name="cname_target" value="{{ old('cname_target', $settings->cname_target) }}" class="form-control" placeholder="customers.example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Apex IPv4 Targets</label>
                                <textarea name="apex_ipv4_targets" rows="3" class="form-control">{{ old('apex_ipv4_targets', implode(PHP_EOL, $settings->apex_ipv4_targets ?? [])) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Apex IPv6 Targets</label>
                                <textarea name="apex_ipv6_targets" rows="3" class="form-control">{{ old('apex_ipv6_targets', implode(PHP_EOL, $settings->apex_ipv6_targets ?? [])) }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="settings-active" name="is_active" value="1" @checked(old('is_active', $settings->is_active))>
                                    <label class="form-check-label" for="settings-active">Cloudflare control plane active</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="apex-active" name="apex_proxying_enabled" value="1" @checked(old('apex_proxying_enabled', $settings->apex_proxying_enabled))>
                                    <label class="form-check-label" for="apex-active">Apex Proxying enabled</label>
                                </div>
                            </div>
                            @if($settings->last_error_summary)
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">{{ $settings->last_error_summary }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Simpan Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Status Ringkas</h3>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @forelse($statusCounts as $status => $count)
                        <span class="badge bg-blue-lt text-blue px-3 py-2">{{ strtoupper($status) }}: {{ $count }}</span>
                    @empty
                        <div class="text-muted">Belum ada custom domain tenant.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Domain Terdaftar</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Hostname</th>
                                <th>Status</th>
                                <th>SSL</th>
                                <th>Canonical</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($domains as $domain)
                                <tr>
                                    <td>{{ $domain->tenant?->name ?? ('Tenant #' . $domain->tenant_id) }}</td>
                                    <td><code>{{ $domain->normalizedHostname() }}</code></td>
                                    <td>{{ $domain->status }}</td>
                                    <td>{{ $domain->cloudflare_ssl_status ?: '-' }}</td>
                                    <td>{{ $domain->is_canonical ? 'Yes' : 'No' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('platform.domains.sync', $domain) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Sync</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">Belum ada custom domain tenant.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

