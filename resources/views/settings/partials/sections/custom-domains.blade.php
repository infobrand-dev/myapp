<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Custom Domains</h3>
    </div>
    <div class="card-body">
        @unless($customDomainsEnabled ?? false)
            <div class="alert alert-warning mb-4">
                <div class="fw-semibold">Fitur premium</div>
                <div class="small mt-1">{{ $customDomainsUpgradeMessage ?? 'Upgrade plan untuk menggunakan custom domain.' }}</div>
                <div class="small mt-2">Hubungi admin platform untuk upgrade plan tenant ini.</div>
            </div>
        @endunless

        @if($customDomainsEnabled ?? false)
            <form method="POST" action="{{ route('settings.custom-domains.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-lg-8">
                    <label class="form-label">Hostname</label>
                    <input type="text" name="hostname" value="{{ old('hostname') }}" class="form-control @error('hostname') is-invalid @enderror" placeholder="app.customer.com atau customer.com" required>
                    @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">Tenant cukup memasukkan domain. Sistem akan menyiapkan instruksi DNS dan verifikasi Cloudflare for SaaS.</div>
                </div>
                <div class="col-lg-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Tambah Domain</button>
                </div>
            </form>
        @endif

        <div class="d-flex flex-column gap-3">
            @forelse($tenantDomains as $row)
                @php($domain = $row['domain'])
                <div class="border rounded-3 p-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <div class="fw-semibold">{{ $domain->normalizedHostname() }}</div>
                                <span class="badge bg-secondary-lt text-secondary">{{ strtoupper($domain->hostname_type ?: 'host') }}</span>
                                <span class="badge {{ $domain->status === 'active' ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">{{ strtoupper($domain->status) }}</span>
                                @if($domain->is_primary)
                                    <span class="badge bg-primary-lt text-primary">PRIMARY</span>
                                @endif
                                @if($domain->is_canonical)
                                    <span class="badge bg-azure-lt text-azure">CANONICAL</span>
                                @endif
                            </div>
                            @if($domain->last_error_message)
                                <div class="text-danger small mt-2">{{ $domain->last_error_message }}</div>
                            @endif
                            @if(!empty($row['instructions']))
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Purpose</th>
                                                <th>Type</th>
                                                <th>Name</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($row['instructions'] as $instruction)
                                                <tr>
                                                    <td>{{ strtoupper($instruction['kind']) }}</td>
                                                    <td><code>{{ $instruction['type'] }}</code></td>
                                                    <td><code>{{ $instruction['name'] }}</code></td>
                                                    <td><code>{{ $instruction['value'] }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            @if($customDomainsEnabled ?? false)
                                <form method="POST" action="{{ route('settings.custom-domains.sync', $domain) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Sync</button>
                                </form>
                                @if($domain->status === 'active' && !$domain->is_canonical)
                                    <form method="POST" action="{{ route('settings.custom-domains.promote', $domain) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Jadikan Canonical</button>
                                    </form>
                                @endif
                                @if(!$domain->is_primary && !$domain->is_canonical)
                                    <form method="POST" action="{{ route('settings.custom-domains.destroy', $domain) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                    </form>
                                @endif
                            @else
                                <span class="badge bg-warning-lt text-warning">Upgrade plan untuk mengelola custom domain</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted">Belum ada custom domain untuk tenant ini.</div>
            @endforelse
        </div>
    </div>
</div>
