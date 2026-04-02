@extends('layouts.admin')

@section('title', 'Platform Tenants')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Tenants</h1>
            <div class="text-muted small mt-1">Audit workspace customer, plan aktif, dan volume penggunaan.</div>
        </div>
            <div class="col-auto">
        <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
            <a href="{{ route('platform.tenants.index') }}" class="btn {{ $riskFilter === '' ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            <a href="{{ route('platform.tenants.index', ['risk' => 'near_limit']) }}" class="btn {{ $riskFilter === 'near_limit' ? 'btn-primary' : 'btn-outline-secondary' }}">Near Limit</a>
            <a href="{{ route('platform.tenants.index', ['risk' => 'over_limit']) }}" class="btn {{ $riskFilter === 'over_limit' ? 'btn-primary' : 'btn-outline-secondary' }}">Over Limit</a>
            <a href="{{ route('platform.tenants.index', ['risk' => 'heavy_ai']) }}" class="btn {{ $riskFilter === 'heavy_ai' ? 'btn-primary' : 'btn-outline-secondary' }}">Heavy AI</a>
            <a href="{{ route('platform.tenants.index', ['risk' => 'heavy_contacts']) }}" class="btn {{ $riskFilter === 'heavy_contacts' ? 'btn-primary' : 'btn-outline-secondary' }}">Heavy Contacts</a>
            <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Dashboard
            </a>
        </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Risk</th>
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
                                @php $planName = optional(optional($tenant->activeSubscription)->plan)->display_name ?? optional(optional($tenant->activeSubscription)->plan)->name; @endphp
                                @if($planName)
                                    <span class="badge bg-blue-lt text-blue">{{ $planName }}</span>
                                @else
                                    <span class="text-muted small">Tidak ada plan</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $risk = $tenant->plan_risk ?? ['status' => 'ok'];
                                    $riskInfo = match($risk['status']) {
                                        'near_limit' => ['label' => 'Near limit', 'class' => 'bg-warning-lt text-warning'],
                                        'at_limit' => ['label' => 'At limit', 'class' => 'bg-danger-lt text-danger'],
                                        'over_limit' => ['label' => 'Over limit', 'class' => 'bg-danger-lt text-danger'],
                                        default => ['label' => 'OK', 'class' => 'bg-success-lt text-success'],
                                    };
                                @endphp
                                <span class="badge {{ $riskInfo['class'] }}">{{ $riskInfo['label'] }}</span>
                            </td>
                            <td>{{ $tenant->users_count }}</td>
                            <td>{{ $tenant->companies_count }}</td>
                            <td>{{ $tenant->branches_count }}</td>
                            <td class="text-muted small">{{ optional($tenant->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
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
