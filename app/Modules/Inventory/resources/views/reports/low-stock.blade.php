@extends('layouts.admin')

@section('title', 'Low Stock Report')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Laporan</div>
            <h2 class="page-title">Low Stock Report</h2>
            <p class="text-muted mb-0">Daftar produk dengan stok menipis berdasarkan minimum quantity.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.dashboard') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>
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
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Produk</th><th>Lokasi</th><th>Current</th><th>Min</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td><a href="{{ route('inventory.stocks.show', $stock) }}">{{ $stock->product?->name }}</a></td>
                            <td>{{ $stock->location?->name }}</td>
                            <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $stock->minimum_quantity, 2, ',', '.') }}</td>
                            <td><span class="badge bg-orange-lt text-orange">{{ $stock->stockStatus() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="ti ti-circle-check text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted">Tidak ada low stock.</div>
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
