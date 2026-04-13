@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp
<div class="mb-3">
    <h2 class="mb-0">Finance Reports</h2>
    <div class="text-muted small">Ringkasan kas masuk, kas keluar, dan pengeluaran.</div>
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
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash In</div><div class="fs-2 fw-bold text-success">{{ $money->format((float) $summary['cash_in_total'], $currency) }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash Out + Expense</div><div class="fs-2 fw-bold text-danger">{{ $money->format((float) $summary['cash_out_total'], $currency) }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Net</div><div class="fs-2 fw-bold {{ $summary['net_total'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ $money->format((float) $summary['net_total'], $currency) }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Arus Kas</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating inflow</span><span>{{ $money->format((float) $cashFlowSummary['operating_inflow'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating outflow</span><span>{{ $money->format((float) $cashFlowSummary['operating_outflow'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Expense outflow</span><span>{{ $money->format((float) $cashFlowSummary['expense_outflow'], $currency) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-semibold"><span>Net cash flow</span><span>{{ $money->format((float) $cashFlowSummary['net_cash_flow'], $currency) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Laba Rugi Sederhana</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Revenue</span><span>{{ $money->format((float) $profitLoss['revenue'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Estimated COGS</span><span>{{ $money->format((float) $profitLoss['estimated_cogs'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Gross profit</span><span>{{ $money->format((float) $profitLoss['gross_profit'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating expenses</span><span>{{ $money->format((float) $profitLoss['operating_expenses'], $currency) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-semibold"><span>Net profit</span><span>{{ $money->format((float) $profitLoss['net_profit'], $currency) }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Cash In / Out by Date</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Date</th><th>Cash In</th><th>Cash Out</th></tr></thead><tbody>
            @forelse($cashInOut as $row)
                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->report_date)->format('d/m/Y') }}</td><td>{{ $money->format((float) $row->cash_in_total, $currency) }}</td><td>{{ $money->format((float) $row->cash_out_total, $currency) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Expense by Category</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Category</th><th>Transactions</th><th>Total</th></tr></thead><tbody>
            @forelse($expenseByCategory as $row)
                <tr><td>{{ $row->category_name }}</td><td>{{ $row->transaction_count }}</td><td>{{ $money->format((float) $row->total_amount, $currency) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
