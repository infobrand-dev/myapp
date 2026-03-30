@extends('layouts.admin')

@section('title', 'Platform Dashboard')

@section('content')
    @php
        $money = app(\App\Support\MoneyFormatter::class);
    @endphp
    <div class="page-header d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-3">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Control Plane</h1>
            <div class="text-muted small mt-1">Pantau pertumbuhan tenant, distribusi plan, dan workspace yang butuh perhatian.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap flex-shrink-0">
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-primary">
                <i class="ti ti-buildings me-1"></i>Tenants
            </a>
            <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-badge-dollar-sign me-1"></i>Plans
            </a>
        </div>
    </div>

    {{-- KPI Cards — 7 cards, Revenue berdiri sendiri --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Tenants</div>
                        <span class="text-primary"><i class="ti ti-buildings" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $stats['total_tenants'] }}</div>
                    <div class="text-muted small mt-1">
                        <span class="text-success fw-semibold">{{ $stats['active_tenants'] }}</span> aktif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Akuisisi</div>
                        <span class="text-success"><i class="ti ti-user-plus" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $stats['new_this_month'] }}</div>
                    <div class="text-muted small mt-1">
                        <span class="fw-semibold">{{ $stats['new_this_week'] }}</span> minggu ini
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Users</div>
                        <span class="text-azure"><i class="ti ti-users" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $stats['total_users'] }}</div>
                    <div class="text-muted small mt-1">
                        <span class="fw-semibold">{{ $stats['total_companies'] }}</span> perusahaan
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Branches</div>
                        <span class="text-cyan"><i class="ti ti-git-branch" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $stats['total_branches'] }}</div>
                    <div class="text-muted small mt-1">Di semua tenant</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Revenue</div>
                        <span class="text-green"><i class="ti ti-currency-dollar" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $money->format((float) $stats['paid_revenue'], 'IDR') }}</div>
                    <div class="text-muted small mt-1">
                        Dari <span class="fw-semibold">{{ $stats['paid_orders'] }}</span> order terbayar
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">Orders</div>
                        <span class="text-yellow"><i class="ti ti-receipt-2" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ $stats['paid_orders'] }}</div>
                    <div class="text-muted small mt-1">Order terbayar</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary text-uppercase small fw-bold">AI Credits</div>
                        <span class="text-purple"><i class="ti ti-brain" style="font-size:1.3rem;"></i></span>
                    </div>
                    <div class="fs-1 fw-bold">{{ number_format($stats['ai_credits_this_month']) }}</div>
                    <div class="text-muted small mt-1">Dipakai bulan ini</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tren Akuisisi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($acquisitionSeries as $point)
                            <div class="list-group-item d-flex align-items-center justify-content-between px-3">
                                <span class="text-secondary">{{ $point['label'] }}</span>
                                <span class="badge bg-primary-lt text-primary fw-semibold">{{ $point['count'] }} tenant</span>
                            </div>
                        @empty
                            <div class="list-group-item text-muted">Belum ada data akuisisi.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Distribusi Plan</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($planDistribution as $row)
                            <div class="list-group-item d-flex align-items-center justify-content-between px-3">
                                <span class="text-secondary">{{ $row['label'] }}</span>
                                <span class="badge bg-azure-lt text-azure fw-semibold">{{ $row['count'] }} tenant</span>
                            </div>
                        @empty
                            <div class="list-group-item text-muted">Belum ada data plan tenant.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Tenant Terbaru</h3>
                    <a href="{{ route('platform.tenants.index') }}" class="btn btn-sm btn-outline-secondary">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Users</th>
                                <th>Didaftarkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTenants as $tenant)
                                <tr>
                                    <td>
                                        <a href="{{ route('platform.tenants.show', $tenant) }}" class="fw-semibold text-reset">{{ $tenant->name }}</a>
                                        <div class="text-muted small">{{ $tenant->slug }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt text-blue">
                                            {{ optional(optional($tenant->activeSubscription)->plan)->display_name ?? optional(optional($tenant->activeSubscription)->plan)->name ?? 'No plan' }}
                                        </span>
                                    </td>
                                    <td>{{ $tenant->users_count }}</td>
                                    <td class="text-muted small">{{ optional($tenant->created_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada tenant.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="ti ti-alert-triangle text-warning me-1"></i>Perlu Perhatian
                    </h3>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($tenantsAtRisk as $row)
                        <a href="{{ route('platform.tenants.show', $row['tenant']) }}" class="list-group-item list-group-item-action px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold">{{ $row['tenant']->name }}</div>
                                    <div class="text-muted small">
                                        {{ $row['tenant']->is_active ? 'Aktif' : 'Nonaktif' }}
                                        &middot; {{ optional(optional($row['tenant']->activeSubscription)->plan)->display_name ?? optional(optional($row['tenant']->activeSubscription)->plan)->name ?? 'No active plan' }}
                                    </div>
                                </div>
                                @php
                                    $riskInfo = match($row['risk']['status'] ?? 'ok') {
                                        'near_limit' => ['label' => 'Near limit', 'class' => 'bg-warning-lt text-warning'],
                                        'at_limit' => ['label' => 'At limit', 'class' => 'bg-danger-lt text-danger'],
                                        'over_limit' => ['label' => 'Over limit', 'class' => 'bg-danger-lt text-danger'],
                                        default => ['label' => 'Nonaktif', 'class' => 'bg-danger-lt text-danger'],
                                    };
                                @endphp
                                <span class="badge {{ $riskInfo['class'] }}">
                                    {{ $row['tenant']->is_active ? $riskInfo['label'] : 'Nonaktif' }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="ti ti-circle-check text-success d-block mb-1" style="font-size:1.5rem;"></i>
                            Semua tenant dalam kondisi baik.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Top Pemakaian AI Credits</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Dipakai</th>
                                <th>Limit</th>
                                <th>Tersisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenantAiLeaderboard as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('platform.tenants.show', $row['tenant']) }}" class="fw-semibold text-reset">{{ $row['tenant']->name }}</a>
                                        <div class="text-muted small">{{ $row['tenant']->slug }}</div>
                                    </td>
                                    <td class="fw-semibold">{{ number_format($row['used']) }}</td>
                                    <td>{{ $row['limit'] ?? 'Unlimited' }}</td>
                                    <td>{{ $row['remaining'] ?? 'Unlimited' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        {{ $aiUsageReady ? 'Belum ada pemakaian AI Credits bulan ini.' : 'Data AI usage belum tersedia.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
