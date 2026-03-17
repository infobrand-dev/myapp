@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Stock Movements</h2>
    <div class="text-muted small">Audit trail semua perubahan stok.</div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Lokasi</label>
                <select name="location_id" class="form-select">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="movement_type" class="form-control" value="{{ $filters['movement_type'] ?? '' }}" placeholder="sale_deduction"></div>
            <div class="col-md-2"><label class="form-label">Reference</label><input type="text" name="reference_type" class="form-control" value="{{ $filters['reference_type'] ?? '' }}" placeholder="App\\Modules\\..."></div>
            <div class="col-md-2"><label class="form-label">Date from</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">Date to</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100">Go</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Waktu</th><th>Produk</th><th>Type</th><th>Lokasi</th><th>Before</th><th>Qty</th><th>After</th><th>Ref</th></tr></thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $movement->product?->name }} @if($movement->variant)<div class="text-muted small">{{ $movement->variant->name }}</div>@endif</td>
                        <td>{{ $movement->movement_type }}</td>
                        <td>{{ $movement->location?->name }}</td>
                        <td>{{ number_format((float) $movement->before_quantity, 2, ',', '.') }}</td>
                        <td>{{ $movement->direction === 'out' ? '-' : '+' }}{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) $movement->after_quantity, 2, ',', '.') }}</td>
                        <td>{{ $movement->reference_type ? class_basename($movement->reference_type) . '#' . $movement->reference_id : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Belum ada movement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $movements->links() }}</div>
</div>
@endsection
