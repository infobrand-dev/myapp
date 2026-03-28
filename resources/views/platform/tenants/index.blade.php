@extends('layouts.admin')

@section('title', 'Platform Tenants')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Tenants</h1>
            <div class="text-muted small mt-1">Audit workspace customer, plan aktif, dan volume penggunaan.</div>
        </div>
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Users</th>
                        <th>Perusahaan</th>
                        <th>Cabang</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td>
                                <a href="{{ route('platform.tenants.show', $tenant) }}" class="fw-semibold text-reset">{{ $tenant->name }}</a>
                                <div class="text-muted small">{{ $tenant->slug }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $tenant->is_active ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                    {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                @php $planName = optional(optional($tenant->activeSubscription)->plan)->name; @endphp
                                @if($planName)
                                    <span class="badge bg-blue-lt text-blue">{{ $planName }}</span>
                                @else
                                    <span class="text-muted small">Tidak ada plan</span>
                                @endif
                            </td>
                            <td>{{ $tenant->users_count }}</td>
                            <td>{{ $tenant->companies_count }}</td>
                            <td>{{ $tenant->branches_count }}</td>
                            <td class="text-muted small">{{ optional($tenant->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-buildings text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted">Belum ada tenant terdaftar.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($tenants, 'links'))
        <div class="card-footer">{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
