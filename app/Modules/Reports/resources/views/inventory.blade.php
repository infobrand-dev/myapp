@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp
<div class="mb-3">
    <h2 class="mb-0">Inventory Reports</h2>
    <div class="text-muted small">Data stok, mutasi, dan valuation dari modul Inventory.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><label class="form-label">Location</label><select name="location_id" class="form-select"><option value="">All</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) $filters['location_id'] === (string) $location->id)>{{ $location->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Product</label><input type="text" name="product" class="form-control" value="{{ $filters['product'] }}" placeholder="Product or variant"></div>
            <div class="col-md-2"><label class="form-label">Movement Type</label><input type="text" name="movement_type" class="form-control" value="{{ $filters['movement_type'] }}" placeholder="sale, purchase"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.inventory') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Stock Rows</div><div class="fs-2 fw-bold">{{ $summary['stock_rows'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Qty</div><div class="fs-2 fw-bold">{{ number_format((float) $summary['total_quantity'], 2, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Low Stock</div><div class="fs-2 fw-bold text-warning">{{ $summary['low_stock_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Inventory Value</div><div class="h4 fw-bold">{{ $money->format((float) $summary['total_inventory_value'], $currency) }}</div><div class="text-muted small mt-1">{{ $summary['movement_count'] }} movement rows</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Stock List</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Product</th><th>Location</th><th>Current</th><th>Reserved</th><th>Available</th><th>Avg Cost</th><th>Value</th></tr></thead><tbody>
            @forelse($stockList as $row)
                <tr><td>{{ $row->product_name }}@if($row->variant_name)<div class="text-muted small">{{ $row->variant_name }}</div>@endif</td><td>{{ $row->location_name }}</td><td>{{ number_format((float) $row->current_quantity, 2, ',', '.') }}</td><td>{{ number_format((float) $row->reserved_quantity, 2, ',', '.') }}</td><td>{{ number_format((float) $row->available_quantity, 2, ',', '.') }}</td><td>{{ $money->format((float) $row->average_unit_cost, $currency) }}</td><td>{{ $money->format((float) $row->inventory_value, $currency) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-xl-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Low Stock</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Product</th><th>Location</th><th>Current</th><th>Min</th></tr></thead><tbody>
            @forelse($lowStock as $row)
                <tr><td>{{ $row->product_name }}@if($row->variant_name)<div class="text-muted small">{{ $row->variant_name }}</div>@endif</td><td>{{ $row->location_name }}</td><td>{{ number_format((float) $row->current_quantity, 2, ',', '.') }}</td><td>{{ number_format((float) $row->minimum_quantity, 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-12">
        <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Stock Valuation</h3><div class="text-muted small">Movement value total: {{ $money->format((float) $summary['movement_value_total'], $currency) }}</div></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Product</th><th>Location</th><th>Current Qty</th><th>Avg Cost</th><th>Inventory Value</th></tr></thead><tbody>
            @forelse($stockValuation as $row)
                <tr><td>{{ $row->product_name }}@if($row->variant_name)<div class="text-muted small">{{ $row->variant_name }}</div>@endif</td><td>{{ $row->location_name }}</td><td>{{ number_format((float) $row->current_quantity, 2, ',', '.') }}</td><td>{{ $money->format((float) $row->average_unit_cost, $currency) }}</td><td>{{ $money->format((float) $row->inventory_value, $currency) }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Belum ada stok bernilai.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Stock Movement</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Type</th><th>Direction</th><th>Rows</th><th>Qty</th></tr></thead><tbody>
            @forelse($stockMovement as $row)
                <tr><td>{{ $row->movement_type }}</td><td>{{ $row->direction }}</td><td>{{ $row->movement_count }}</td><td>{{ number_format((float) $row->total_quantity, 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Adjustment</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Code</th><th>Date</th><th>Location</th><th>Lines</th></tr></thead><tbody>
            @forelse($adjustments as $row)
                <tr><td>{{ $row->code }}<div class="text-muted small">{{ $row->status }}</div></td><td>{{ \Illuminate\Support\Carbon::parse($row->adjustment_date)->format('d/m/Y') }}</td><td>{{ $row->location_name }}</td><td>{{ $row->line_count }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Opname</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Code</th><th>Date</th><th>Location</th><th>Diff Qty</th></tr></thead><tbody>
            @forelse($opnames as $row)
                <tr><td>{{ $row->code }}<div class="text-muted small">{{ $row->status }}</div></td><td>{{ \Illuminate\Support\Carbon::parse($row->opname_date)->format('d/m/Y') }}</td><td>{{ $row->location_name }}</td><td>{{ number_format((float) $row->absolute_difference_qty, 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
