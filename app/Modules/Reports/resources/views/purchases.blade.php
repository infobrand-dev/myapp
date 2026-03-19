@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Purchase Reports</h2>
    <div class="text-muted small">Summary pembelian, performa supplier, dan bucket received vs pending.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-3"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-4"><label class="form-label">Supplier</label><input type="text" name="supplier" class="form-control" value="{{ $filters['supplier'] }}" placeholder="Supplier snapshot"></div>
            <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-primary w-100">Filter</button><a href="{{ route('reports.purchases') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Purchases</div><div class="fs-2 fw-bold">{{ $summary['purchase_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Grand Total</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['grand_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Received Qty</div><div class="fs-2 fw-bold">{{ number_format((float) $summary['received_qty_total'], 2, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Balance Due</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['balance_due_total'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Supplier Report</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Supplier</th><th>Purchases</th><th>Grand Total</th><th>Balance Due</th></tr></thead><tbody>
            @forelse($supplierReport as $row)
                <tr><td>{{ $row->supplier_name }}</td><td>{{ $row->purchase_count }}</td><td>Rp {{ number_format((float) $row->grand_total, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $row->balance_due_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Received vs Pending</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Bucket</th><th>Purchases</th><th>Grand Total</th><th>Remaining Qty</th></tr></thead><tbody>
            @forelse($receivedVsPending as $row)
                <tr><td>{{ $row->receipt_bucket }}</td><td>{{ $row->purchase_count }}</td><td>Rp {{ number_format((float) $row->grand_total, 0, ',', '.') }}</td><td>{{ number_format((float) $row->remaining_qty, 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
