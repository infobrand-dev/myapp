@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Finance Reports</h2>
    <div class="text-muted small">Cash in/out dan expense by category untuk transaksi finance non-sales.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">Finance report mengikuti active company/branch context dari topbar switcher, bukan filter outlet manual.</div>
        <form method="GET" class="row g-3">
            <div class="col-md-3"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-3"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><label class="form-label">Category</label><select name="finance_category_id" class="form-select"><option value="">All</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $filters['finance_category_id'] === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Type</label><input type="text" name="transaction_type" class="form-control" value="{{ $filters['transaction_type'] }}" placeholder="cash_in, cash_out, expense"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.finance') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash In</div><div class="fs-2 fw-bold text-success">Rp {{ number_format((float) $summary['cash_in_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash Out + Expense</div><div class="fs-2 fw-bold text-danger">Rp {{ number_format((float) $summary['cash_out_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Net</div><div class="fs-2 fw-bold {{ $summary['net_total'] >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format((float) $summary['net_total'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Cash In / Out by Date</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Date</th><th>Cash In</th><th>Cash Out</th></tr></thead><tbody>
            @forelse($cashInOut as $row)
                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->report_date)->format('d/m/Y') }}</td><td>Rp {{ number_format((float) $row->cash_in_total, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $row->cash_out_total, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Expense by Category</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Category</th><th>Transactions</th><th>Total</th></tr></thead><tbody>
            @forelse($expenseByCategory as $row)
                <tr><td>{{ $row->category_name }}</td><td>{{ $row->transaction_count }}</td><td>Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
