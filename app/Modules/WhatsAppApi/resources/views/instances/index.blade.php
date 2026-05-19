@extends('layouts.admin')

@section('title', 'WA API Instances')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">WhatsApp API</div>
            <h2 class="page-title">Instances</h2>
            <p class="text-muted mb-0">Kelola koneksi WA API (hanya Super-admin).</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('whatsapp-api.logs.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-list me-1"></i>Logs
            </a>
            <a href="{{ route('whatsapp-api.instances.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Tambah Instance
            </a>
        </div>
    </div>
</div>

{{-- KPI --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Total Instance</div>
                    <i class="ti ti-device-mobile" style="font-size:1.3rem; color:var(--tblr-green);"></i>
                </div>
                <div class="fs-1 fw-bold">{{ $summary['total'] ?? $instances->total() }}</div>
                <div class="text-muted small mt-1">Instance terdaftar</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Provider</th>
                        <th>Nomor</th>
                        <th>Config</th>
                        <th>Aktif</th>
                        <th>Last Health Check</th>
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
                            <td class="fw-semibold">{{ $instance->name }}</td>
                            <td><span class="badge bg-azure-lt text-azure">{{ strtoupper($instance->provider) }}</span></td>
                            <td>{{ $instance->phone_number ?? '—' }}</td>
                            <td>
                                @if($isConfigured)
                                    <span class="badge bg-green-lt text-green">Ready</span>
                                @else
                                    <span class="badge bg-orange-lt text-orange">Incomplete</span>
                                @endif
                            </td>
                            <td>
                                @if($instance->is_active)
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ optional($instance->last_health_check_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('whatsapp-api.instances.edit', $instance) }}"
                                       class="btn btn-icon btn-sm btn-outline-primary"
                                       title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.instances.destroy', $instance) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-sm btn-outline-danger"
                                                type="submit"
                                                title="Hapus"
                                                data-confirm="Hapus instance {{ $instance->name }}?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-device-mobile text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada instance.</div>
                                <a href="{{ route('whatsapp-api.instances.create') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Tambah Instance
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $instances->links() }}
    </div>
</div>

@endsection
