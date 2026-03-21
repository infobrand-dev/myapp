@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Payment Reports</h2>
    <div class="text-muted small">Ringkasan pembayaran posted berdasarkan method dan bucket cash vs non-cash.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">Payment report mengikuti active company/branch context dari topbar switcher, bukan filter outlet manual.</div>
        <form method="GET" class="row g-3">
            <div class="col-md-4"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-4"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-2"><label class="form-label">Method</label><select name="payment_method_id" class="form-select"><option value="">All</option>@foreach($methods as $method)<option value="{{ $method->id }}" @selected((string) $filters['payment_method_id'] === (string) $method->id)>{{ $method->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Source</label><input type="text" name="source" class="form-control" value="{{ $filters['source'] }}" placeholder="pos, manual, api"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.payments') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Posted Payments</div><div class="fs-2 fw-bold">{{ $summary['payment_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Amount</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['total_amount'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Cash</div><div class="fs-2 fw-bold text-success">Rp {{ number_format((float) $summary['cash_amount'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Non-cash</div><div class="fs-2 fw-bold text-primary">Rp {{ number_format((float) $summary['non_cash_amount'], 0, ',', '.') }}</div><div class="text-muted small mt-1">Avg: Rp {{ number_format((float) $summary['average_payment'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">By Method</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Method</th><th>Payments</th><th>Total</th></tr></thead><tbody>
            @forelse($byMethod as $row)
                <tr><td>{{ $row->method_name }}</td><td>{{ $row->payment_count }}</td><td>Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Cash vs Non-cash</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Bucket</th><th>Payments</th><th>Total</th></tr></thead><tbody>
            @forelse($cashVsNonCash as $row)
                <tr><td>{{ $row->payment_bucket }}</td><td>{{ $row->payment_count }}</td><td>Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
