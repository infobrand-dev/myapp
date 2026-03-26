@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Discounts</h2>
        <div class="text-muted small">Kelola promo, voucher, dan aturan diskon.</div>
    </div>
    <a href="{{ route('discounts.create') }}" class="btn btn-primary">Buat Discount</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Cari discount</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nama internal, label, code">
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="discount_type" class="form-select">
                    <option value="">Semua type</option>
                    @foreach(['fixed_amount' => 'Fixed Amount', 'percentage' => 'Percentage', 'buy_x_get_y' => 'Buy X Get Y', 'free_item' => 'Free Item', 'bundle' => 'Bundle'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['discount_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status_view" class="form-select">
                    <option value="">Semua status</option>
                    @foreach(['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status_view'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Discount</th>
                    <th>Type / Scope</th>
                    <th>Priority</th>
                    <th>Window</th>
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
                            <div class="text-muted small">{{ $discount->public_label ?: '-' }} @if($discount->code) | Code: {{ $discount->code }} @endif</div>
                        </td>
                        <td>
                            <div>{{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}</div>
                            <div class="text-muted small">{{ ucfirst($discount->application_scope) }}</div>
                        </td>
                        <td>{{ $discount->priority }} / {{ $discount->sequence }}</td>
                        <td>
                            <div>{{ $discount->starts_at?->format('d/m/Y H:i') ?? 'No start' }}</div>
                            <div class="text-muted small">{{ $discount->ends_at?->format('d/m/Y H:i') ?? 'No end' }}</div>
                        </td>
                        <td><span class="badge bg-{{ in_array($discount->status_view, ['active']) ? 'success' : (in_array($discount->status_view, ['scheduled']) ? 'warning' : 'secondary') }}-lt text-{{ in_array($discount->status_view, ['active']) ? 'success' : (in_array($discount->status_view, ['scheduled']) ? 'warning' : 'secondary') }}">{{ ucfirst($discount->status_view) }}</span></td>
                        <td>
                            <div>{{ $discount->usages_count }} applied</div>
                            <div class="text-muted small">{{ $discount->vouchers_count }} vouchers</div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <form method="POST" action="{{ route('discounts.toggle-status', $discount) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-icon btn-outline-warning" title="Toggle"><i class="ti ti-switch-3"></i></button>
                                </form>
                                <a href="{{ route('discounts.edit', $discount) }}" class="btn btn-icon btn-outline-secondary" title="Edit"><i class="ti ti-edit"></i></a>
                                <form method="POST" action="{{ route('discounts.archive', $discount) }}">
                                    @csrf
                                    <button class="btn btn-icon btn-outline-danger" title="Archive" data-confirm="Arsipkan diskon ini?"><i class="ti ti-archive"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada diskon.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $discounts->links() }}</div>
</div>
@endsection
