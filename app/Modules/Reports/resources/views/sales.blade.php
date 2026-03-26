@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Sales Reports</h2>
    <div class="text-muted small">Ringkasan transaksi penjualan.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">
            Filter report mengikuti context aktif dari topbar switcher. Jika branch tidak dipilih, report hanya menampilkan data company-level dengan <code>branch_id = null</code>.
        </div>
        <form method="GET" class="row g-3">
            <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-2"><label class="form-label">Source</label><select name="source" class="form-select"><option value="">All</option>@foreach($sourceOptions as $value => $label)<option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Customer</label><input type="text" name="customer" class="form-control" value="{{ $filters['customer'] }}" placeholder="Name snapshot"></div>
            <div class="col-md-2"><label class="form-label">Cashier ID</label><input type="number" min="1" name="cashier_user_id" class="form-control" value="{{ $filters['cashier_user_id'] }}"></div>
            <div class="col-md-4"><label class="form-label">Product</label><input type="text" name="product" class="form-control" value="{{ $filters['product'] }}" placeholder="Product, variant, or SKU"></div>
            <div class="col-md-8 d-flex align-items-end gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.sales') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Transactions</div><div class="fs-2 fw-bold">{{ $summary['transaction_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Gross Sales</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['gross_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Paid Total</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['paid_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Item Qty</div><div class="fs-2 fw-bold">{{ number_format((float) $summary['item_qty'], 2, ',', '.') }}</div><div class="text-muted small mt-1">Avg ticket: Rp {{ number_format((float) $summary['average_ticket'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">By Date</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Date</th><th>Transactions</th><th>Gross</th><th>Paid</th></tr></thead><tbody>
            @forelse($byDate as $row)
                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->report_date)->format('d/m/Y') }}</td><td>{{ $row->transaction_count }}</td><td>Rp {{ number_format((float) $row->gross_total, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $row->paid_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">By Cashier</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Cashier</th><th>Transactions</th><th>Gross</th><th>Paid</th></tr></thead><tbody>
            @forelse($byCashier as $row)
                <tr><td>{{ $row->cashier_name }}</td><td>{{ $row->transaction_count }}</td><td>Rp {{ number_format((float) $row->gross_total, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $row->paid_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">By Product</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Product</th><th>Qty</th><th>Transactions</th><th>Gross</th></tr></thead><tbody>
            @forelse($byProduct as $row)
                <tr><td>{{ $row->product_name_snapshot }}@if($row->variant_name_snapshot)<div class="text-muted small">{{ $row->variant_name_snapshot }}</div>@endif</td><td>{{ number_format((float) $row->qty_sold, 2, ',', '.') }}</td><td>{{ $row->transaction_count }}</td><td>Rp {{ number_format((float) $row->gross_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">By Customer</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Customer</th><th>Transactions</th><th>Gross</th><th>Paid</th></tr></thead><tbody>
            @forelse($byCustomer as $row)
                <tr><td>{{ $row->customer_name }}</td><td>{{ $row->transaction_count }}</td><td>Rp {{ number_format((float) $row->gross_total, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $row->paid_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
