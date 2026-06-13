@extends('layouts.tenant')

@section('title', 'Discounts')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Penjualan</div>
                <h2 class="page-title">Discounts</h2>
                <p class="text-muted mb-0">Kelola promo, voucher, aturan diskon, serta lifecycle arsip atau hapus.</p>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                @can('discounts.manage-vouchers')
                    <a href="{{ route('discounts.vouchers.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-ticket me-1"></i>Vouchers
                    </a>
                @endcan
                @can('discounts.view-usage')
                    <a href="{{ route('discounts.usages.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-history me-1"></i>Usage
                    </a>
                @endcan
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
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="search">Cari discount</label>
                    <input type="text" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nama internal, public label, code">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="discount_type">Type</label>
                    <select id="discount_type" name="discount_type" class="form-select">
                        <option value="">- Semua type -</option>
                        @foreach(['fixed_amount' => 'Fixed Amount', 'percentage' => 'Percentage', 'buy_x_get_y' => 'Buy X Get Y', 'free_item' => 'Free Item', 'bundle' => 'Bundle'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['discount_type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status_view">Status</label>
                    <select id="status_view" name="status_view" class="form-select">
                        <option value="">- Semua -</option>
                        @foreach(['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status_view'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Filter</button>
                    <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Discount</th>
                        <th>Type / Scope</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Usage</th>
                        <th class="text-end" style="min-width: 14rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $discount)
                        @php
                            $statusClass = match($discount->status_view) {
                                'active' => 'bg-green-lt text-green',
                                'scheduled' => 'bg-azure-lt text-azure',
                                'expired', 'archived' => 'bg-secondary-lt text-secondary',
                                default => 'bg-orange-lt text-orange',
                            };
                            $canDelete = (int) $discount->usages_count < 1;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('discounts.show', $discount) }}" class="fw-semibold text-decoration-none">{{ $discount->internal_name }}</a>
                                <div class="text-muted small mt-1">
                                    {{ $discount->public_label ?: 'Tanpa public label' }}
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
                                <div class="small">{{ $discount->starts_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                <div class="text-muted small">{{ $discount->ends_at?->format('d/m/Y H:i') ?? 'Tanpa batas' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ ucfirst($discount->status_view) }}</span>
                            </td>
                            <td>
                                <div class="small">{{ $discount->usages_count }} applied</div>
                                <div class="text-muted small">{{ $discount->vouchers_count }} vouchers</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                    <a href="{{ route('discounts.show', $discount) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="ti ti-eye me-1"></i>Detail
                                    </a>
                                    @can('discounts.update')
                                        <a href="{{ route('discounts.edit', $discount) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-pencil me-1"></i>Edit
                                        </a>
                                    @endcan
                                    @can('discounts.activate')
                                        <form method="POST" action="{{ route('discounts.toggle-status', $discount) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="ti {{ $discount->is_active ? 'ti-player-pause' : 'ti-player-play' }} me-1"></i>{{ $discount->is_active ? 'Pause' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('discounts.archive')
                                        @if($discount->status_view !== 'archived')
                                            <form method="POST" action="{{ route('discounts.archive', $discount) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="Arsipkan discount '{{ $discount->internal_name }}'?">
                                                    <i class="ti ti-archive me-1"></i>Arsip
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('discounts.delete')
                                        @if($canDelete)
                                            <form method="POST" action="{{ route('discounts.destroy', $discount) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus discount '{{ $discount->internal_name }}'? Data yang sudah dihapus tidak bisa dikembalikan.">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small align-self-center">Delete diblokir: sudah ada usage</span>
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
        <div class="card-footer">
            {{ $discounts->links() }}
        </div>
    </div>
@endsection
