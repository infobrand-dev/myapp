@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Stock List</h2>
        <div class="text-muted small">Saldo stok per product atau variant dan per lokasi.</div>
    </div>
    <a href="{{ route('inventory.reports.low-stock') }}" class="btn btn-outline-warning">Low Stock</a>
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
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Produk</th><th>Lokasi</th><th>Current</th><th>Reserved</th><th>Available</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $stock->product?->name }}</div>
                            <div class="text-muted small">SKU: {{ $stock->variant?->sku ?? $stock->product?->sku }}</div>
                            @if($stock->variant)<div class="text-muted small">Variant: {{ $stock->variant->name }}</div>@endif
                        </td>
                        <td>{{ $stock->location?->name }}</td>
                        <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) $stock->reserved_quantity, 2, ',', '.') }}</td>
                        <td>{{ number_format($stock->availableQuantity(), 2, ',', '.') }}</td>
                        <td><span class="badge bg-{{ $stock->stockStatus() === 'out_of_stock' ? 'danger' : ($stock->stockStatus() === 'low_stock' ? 'warning' : 'success') }}-lt text-{{ $stock->stockStatus() === 'out_of_stock' ? 'danger' : ($stock->stockStatus() === 'low_stock' ? 'warning' : 'success') }}">{{ $stock->stockStatus() }}</span></td>
                        <td class="text-end"><a href="{{ route('inventory.stocks.show', $stock) }}" class="btn btn-outline-secondary btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada saldo stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $stocks->links() }}</div>
</div>
@endsection
