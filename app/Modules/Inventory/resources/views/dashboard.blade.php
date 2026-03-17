@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Inventory Dashboard</h2>
        <div class="text-muted small">Summary stok, mutasi, low stock, dan audit ringkas.</div>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="location_id" class="form-select">
            <option value="">Semua lokasi</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary">Filter</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Stock Items</div><div class="h2 mb-0">{{ $summary['stock_items'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Qty</div><div class="h2 mb-0">{{ number_format($summary['total_quantity'], 2, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Low Stock</div><div class="h2 mb-0 text-warning">{{ $summary['low_stock_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Movement Hari Ini</div><div class="h2 mb-0">{{ $summary['movement_today_count'] }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recent Movements</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Lokasi</th><th>Qty</th><th>After</th></tr></thead>
                    <tbody>
                        @forelse($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->product?->name }} @if($movement->variant)<div class="text-muted small">{{ $movement->variant->name }}</div>@endif</td>
                                <td><span class="badge bg-blue-lt text-blue">{{ $movement->movement_type }}</span></td>
                                <td>{{ $movement->location?->name }}</td>
                                <td>{{ $movement->direction === 'out' ? '-' : '+' }}{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $movement->after_quantity, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada movement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Low Stock Snapshot</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter">
                    <thead><tr><th>Produk</th><th>Lokasi</th><th>Qty</th><th>Min</th></tr></thead>
                    <tbody>
                        @forelse($lowStocks as $stock)
                            <tr>
                                <td><a href="{{ route('inventory.stocks.show', $stock) }}">{{ $stock->product?->name }}</a></td>
                                <td>{{ $stock->location?->name }}</td>
                                <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $stock->minimum_quantity, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada low stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
