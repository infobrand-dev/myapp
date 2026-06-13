@extends('layouts.tenant')

@section('title', 'Discounts')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Penjualan</div>
            <h2 class="page-title">Discounts</h2>
            <p class="text-muted mb-0">Kelola promo, voucher, dan aturan diskon.</p>
        </div>
        <div class="col-auto">
            @can('discounts.create')
                <a href="{{ route('discounts.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Buat Discount
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="search">Cari discount</label>
                <input type="text" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nama internal, label, code">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="discount_type">Type</label>
                <select id="discount_type" name="discount_type" class="form-select">
                    <option value="">— Semua type —</option>
                    @foreach(['fixed_amount' => 'Fixed Amount', 'percentage' => 'Percentage', 'buy_x_get_y' => 'Buy X Get Y', 'free_item' => 'Free Item', 'bundle' => 'Bundle'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['discount_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status_view">Status</label>
                <select id="status_view" name="status_view" class="form-select">
                    <option value="">— Semua —</option>
                    @foreach(['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status_view'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary flex-fill">Filter</button>
                <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Discount</th>
                        <th>Type / Scope</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Usage</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $discount)
                        <tr>
                            <td>
                                <a href="{{ route('discounts.show', $discount) }}" class="fw-semibold text-decoration-none">{{ $discount->internal_name }}</a>
                                <div class="text-muted small">
                                    {{ $discount->public_label ?: '—' }}
                                    @if($discount->code)
                                        <span class="badge bg-secondary-lt text-secondary ms-1">{{ $discount->code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}</div>
                                <div class="text-muted small">{{ ucfirst($discount->application_scope) }}</div>
                            </td>
                            <td>
                                <div class="small">{{ $discount->starts_at?->format('d/m/Y H:i') ?? '—' }}</div>
                                <div class="text-muted small">{{ $discount->ends_at?->format('d/m/Y H:i') ?? 'Tanpa batas' }}</div>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($discount->status_view) {
                                        'active'    => 'bg-green-lt text-green',
                                        'scheduled' => 'bg-azure-lt text-azure',
                                        'expired',
                                        'archived'  => 'bg-secondary-lt text-secondary',
                                        default     => 'bg-orange-lt text-orange',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($discount->status_view) }}</span>
                            </td>
                            <td>
                                <div class="small">{{ $discount->usages_count }} applied</div>
                                <div class="text-muted small">{{ $discount->vouchers_count }} vouchers</div>
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('discounts.show', $discount) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @can('discounts.update')
                                        <a href="{{ route('discounts.edit', $discount) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('discounts.activate')
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('discounts.toggle-status', $discount) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-secondary" title="{{ $discount->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="ti {{ $discount->is_active ? 'ti-player-pause' : 'ti-player-play' }}"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('discounts.archive')
                                        @if($discount->status_view !== 'archived')
                                            <form class="d-inline-block m-0" method="POST" action="{{ route('discounts.archive', $discount) }}" data-confirm="Arsipkan diskon &quot;{{ $discount->internal_name }}&quot;?">
                                                @csrf
                                                <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Arsipkan">
                                                    <i class="ti ti-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-tag text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada discount.</div>
                                @can('discounts.create')
                                    <a href="{{ route('discounts.create') }}" class="btn btn-sm btn-primary">Buat Discount Pertama</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $discounts->links() }}
    </div>
</div>

@endsection
