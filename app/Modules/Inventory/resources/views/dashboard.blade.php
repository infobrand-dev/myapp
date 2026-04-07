@extends('layouts.admin')

@section('title', 'Inventory Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Inventory Dashboard</h2>
            <p class="text-muted mb-0">Ringkasan stok, mutasi, dan peringatan stok rendah.</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <select name="location_id" class="form-select">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-primary">Filter</button>
            </form>
            <a href="{{ route('inventory.stocks.index', ['location_id' => $locationId]) }}" class="btn btn-outline-secondary">Stock List</a>
            <a href="{{ route('inventory.reports.low-stock', ['location_id' => $locationId]) }}" class="btn btn-outline-warning">Low Stock</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Stock Items</div><div class="h2 mb-0">{{ $summary['stock_items'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Qty</div><div class="h2 mb-0">{{ number_format($summary['total_quantity'], 2, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Low Stock</div><div class="h2 mb-0 text-orange">{{ $summary['low_stock_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Movement Hari Ini</div><div class="h2 mb-0">{{ $summary['movement_today_count'] }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Action Queue</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Out of Stock</div>
                            <div class="h2 mb-2 text-danger">{{ $summary['out_of_stock_count'] }}</div>
                            <a href="{{ route('inventory.stocks.index', ['location_id' => $locationId, 'status' => 'out_of_stock']) }}" class="btn btn-sm btn-outline-danger">Review</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Adjustment</div>
                            <div class="small mb-2">Koreksi stok fisik vs sistem.</div>
                            @can('inventory.manage-stock-adjustment')
                                <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-sm btn-outline-primary">New Adjustment</a>
                            @else
                                <span class="text-muted small">Tidak ada akses adjustment.</span>
                            @endcan
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Transfer</div>
                            <div class="small mb-2">Pindah stok antar lokasi.</div>
                            @can('inventory.manage-stock-transfer')
                                <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-outline-primary">New Transfer</a>
                            @else
                                <span class="text-muted small">Tidak ada akses transfer.</span>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Movement Breakdown</h3></div>
            <div class="list-group list-group-flush">
                @forelse($movementBreakdown as $row)
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ \Illuminate\Support\Str::headline((string) $row->movement_type) }}</div>
                            <div class="text-muted small">{{ strtoupper((string) $row->direction) }} | Qty {{ number_format((float) $row->total_quantity, 2, ',', '.') }}</div>
                        </div>
                        <span class="badge bg-blue-lt text-blue">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada mutasi stok.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recent Movements</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
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
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada movement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Critical Stock Snapshot</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead><tr><th>Produk</th><th>Lokasi</th><th>Qty</th><th>Min</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($criticalStocks as $stock)
                                @php
                                    $status = $stock->stockStatus();
                                    $statusClass = $status === 'out_of_stock' ? 'danger' : 'orange';
                                @endphp
                                <tr>
                                    <td><a href="{{ route('inventory.stocks.show', $stock) }}">{{ $stock->product?->name }}</a></td>
                                    <td>{{ $stock->location?->name }}</td>
                                    <td>{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $stock->minimum_quantity, 2, ',', '.') }}</td>
                                    <td><span class="badge bg-{{ $statusClass }}-lt text-{{ $statusClass }}">{{ \Illuminate\Support\Str::headline($status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada stok kritis.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('inventory.reports.low-stock', ['location_id' => $locationId]) }}" class="btn btn-outline-warning btn-sm">Open Low Stock Report</a>
            </div>
        </div>
    </div>
</div>
@endsection
