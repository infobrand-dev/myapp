@extends('layouts.admin')

@section('title', 'Stock List')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Stock List</h2>
            <p class="text-muted mb-0">Saldo stok per produk dan lokasi.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('inventory.reports.low-stock', ['location_id' => $filters['location_id'] ?? null]) }}" class="btn btn-outline-warning">Low Stock</a>
            <a href="{{ route('inventory.dashboard', ['location_id' => $filters['location_id'] ?? null]) }}" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Items</div><div class="h2 mb-0">{{ $summary['total_items'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Out of Stock</div><div class="h2 mb-0 text-danger">{{ $summary['out_of_stock'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Reserved Risk</div><div class="h2 mb-0 text-orange">{{ $summary['reserved_risk'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Reorder Candidates</div><div class="h2 mb-0">{{ $summary['reorder_candidates'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Produk, variant, lokasi"></div>
            <div class="col-md-3">
                <label class="form-label">Lokasi</label>
                <select name="location_id" class="form-select">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="in_stock" @selected(($filters['status'] ?? '') === 'in_stock')>In stock</option>
                    <option value="low_stock" @selected(($filters['status'] ?? '') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(($filters['status'] ?? '') === 'out_of_stock')>Out of stock</option>
                    <option value="reserved_risk" @selected(($filters['status'] ?? '') === 'reserved_risk')>Reserved risk</option>
                    <option value="reorder" @selected(($filters['status'] ?? '') === 'reorder')>Reorder candidate</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
        <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="{{ route('inventory.stocks.index', ['location_id' => $filters['location_id'] ?? null, 'status' => 'out_of_stock']) }}" class="btn btn-sm btn-outline-danger">Out of Stock</a>
            <a href="{{ route('inventory.stocks.index', ['location_id' => $filters['location_id'] ?? null, 'status' => 'reserved_risk']) }}" class="btn btn-sm btn-outline-warning">Reserved Risk</a>
            <a href="{{ route('inventory.stocks.index', ['location_id' => $filters['location_id'] ?? null, 'status' => 'reorder']) }}" class="btn btn-sm btn-outline-primary">Reorder Candidates</a>
            <a href="{{ route('inventory.stocks.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Produk</th><th>Lokasi</th><th>Current</th><th>Reserved</th><th>Available</th><th>Reorder</th><th>Status</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $available = $stock->availableQuantity();
                            $status = $stock->stockStatus();
                            $statusClass = $status === 'out_of_stock' ? 'danger' : ($status === 'low_stock' ? 'orange' : 'success');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $stock->product?->name }}</div>
                                <div class="text-muted small">SKU: {{ $stock->variant?->sku ?? $stock->product?->sku }}</div>
                                @if($stock->variant)<div class="text-muted small">Variant: {{ $stock->variant->name }}</div>@endif
                            </td>
                            <td>{{ $stock->location?->name }}</td>
                            <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $stock->reserved_quantity, 2, ',', '.') }}</td>
                            <td class="{{ $available <= 0 && (float) $stock->current_quantity > 0 ? 'text-orange fw-semibold' : '' }}">{{ number_format($available, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $stock->reorder_quantity, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $statusClass }}-lt text-{{ $statusClass }}">{{ $status }}</span>
                                @if($available <= 0 && (float) $stock->current_quantity > 0)
                                    <div class="text-orange small">reserved risk</div>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('inventory.stocks.show', $stock) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="ti ti-package text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted">Belum ada saldo stok.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $stocks->links() }}</div>
</div>
@endsection
