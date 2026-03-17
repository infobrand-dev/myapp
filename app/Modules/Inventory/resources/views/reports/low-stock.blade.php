@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Low Stock Report</h2>
    <div class="text-muted small">Daftar produk dengan stok menipis berdasarkan minimum quantity.</div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-md-4">
                <label class="form-label">Lokasi</label>
                <select name="location_id" class="form-select">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Produk</th><th>Lokasi</th><th>Current</th><th>Min</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td><a href="{{ route('inventory.stocks.show', $stock) }}">{{ $stock->product?->name }}</a></td>
                        <td>{{ $stock->location?->name }}</td>
                        <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) $stock->minimum_quantity, 2, ',', '.') }}</td>
                        <td><span class="badge bg-warning-lt text-warning">{{ $stock->stockStatus() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Tidak ada low stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $stocks->links() }}</div>
</div>
@endsection
