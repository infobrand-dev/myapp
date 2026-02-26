@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp API Instances</h2>
        <div class="text-muted small">Kelola koneksi WA API (hanya Super-admin).</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('whatsapp-api.logs.index') }}" class="btn btn-outline-secondary">Logs</a>
        <a href="{{ route('whatsapp-api.instances.create') }}" class="btn btn-primary">Tambah Instance</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted">Total</div>
                <div class="h3 mb-0">{{ $summary['total'] ?? $instances->total() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Provider</th>
                    <th>Nomor</th>
                    <th>Config</th>
                    <th>Aktif</th>
                    <th>Last Health</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($instances as $instance)
                    @php
                        $provider = strtolower((string) ($instance->provider ?? ''));
                        $isCloud = $provider === 'cloud';
                        $settings = is_array($instance->settings) ? $instance->settings : [];
                        $hasVerify = !empty($settings['wa_cloud_verify_token']) || !empty($settings['verify_token']);
                        $isConfigured = $isCloud
                            ? !empty($instance->phone_number_id) && !empty($instance->cloud_business_account_id) && !empty($instance->cloud_token) && $hasVerify
                            : !empty($instance->api_base_url) && !empty($instance->api_token);
                    @endphp
                    <tr>
                        <td class="fw-bold">{{ $instance->name }}</td>
                        <td><span class="badge bg-azure-lt text-azure">{{ strtoupper($instance->provider) }}</span></td>
                        <td>{{ $instance->phone_number ?? '—' }}</td>
                        <td>
                            @if($isConfigured)
                                <span class="badge bg-green-lt text-green">Ready</span>
                            @else
                                <span class="badge bg-yellow-lt text-yellow">Incomplete</span>
                            @endif
                        </td>
                        <td>{{ $instance->is_active ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ optional($instance->last_health_check_at)->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('whatsapp-api.instances.edit', $instance) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="View" aria-label="View">
                                    <i class="ti ti-eye icon" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('whatsapp-api.instances.edit', $instance) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit" aria-label="Edit">
                                    <i class="ti ti-pencil icon" aria-hidden="true"></i>
                                </a>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.instances.destroy', $instance) }}" onsubmit="return confirm('Hapus instance?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Delete" aria-label="Delete">
                                        <i class="ti ti-trash icon" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Belum ada instance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $instances->links() }}</div>
@endsection
