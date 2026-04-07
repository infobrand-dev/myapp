@extends('layouts.admin')

@section('title', 'Buat Stock Opname')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock Opname</div>
            <h2 class="page-title">Buat Stock Opname</h2>
            <p class="text-muted mb-0">Buat sesi opname stok fisik.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.opnames.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.opnames.create') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Lokasi / Outlet</label>
                <select name="location_id" class="form-select" required>
                    <option value="">Pilih lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $selectedLocationId === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100">Tampilkan Stok</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('inventory.opnames.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Header</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Lokasi / Outlet</label>
                        <select name="inventory_location_id" class="form-select" required>
                            <option value="">Pilih lokasi</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('inventory_location_id', $selectedLocationId) === (string) $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tanggal Opname</label>
                        <input type="date" name="opname_date" class="form-control" value="{{ old('opname_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Preview Stok Sistem</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Produk</th><th>Lokasi</th><th>Stok Sistem</th></tr></thead>
                        <tbody>
                            @forelse($previewStocks as $stock)
                                <tr>
                                    <td>{{ $stock->product ? $stock->product->name : '-' }} @if($stock->variant)<div class="text-muted small">{{ $stock->variant->name }}</div>@endif</td>
                                    <td>{{ $stock->location ? $stock->location->name : '-' }}</td>
                                    <td>{{ number_format((float) $stock->current_quantity, 4, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Pilih lokasi untuk menampilkan stok sistem yang akan dijadikan snapshot.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.opnames.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary" {{ $previewStocks->isEmpty() ? 'disabled' : '' }}>
                    <i class="ti ti-device-floppy me-1"></i>Buat Draft Opname
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
