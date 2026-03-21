@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">POS / Cashier Reports</h2>
    <div class="text-muted small">Ringkasan shift cashier dan cash difference per sesi kasir.</div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">POS report mengikuti active company/branch context dari topbar switcher, bukan filter outlet manual.</div>
        <form method="GET" class="row g-3">
            <div class="col-md-4"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-4"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-2"><label class="form-label">Cashier ID</label><input type="number" min="1" name="cashier_user_id" class="form-control" value="{{ $filters['cashier_user_id'] }}"></div>
            <div class="col-md-2"><label class="form-label">Status</label><input type="text" name="status" class="form-control" value="{{ $filters['status'] }}" placeholder="active, closed"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.pos') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Shift Count</div><div class="fs-2 fw-bold">{{ $summary['shift_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Sales Total</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['sales_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Payment Total</div><div class="fs-2 fw-bold">Rp {{ number_format((float) $summary['payment_total'], 0, ',', '.') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Cash Difference</div><div class="fs-2 fw-bold {{ $summary['difference_total'] == 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format((float) $summary['difference_total'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Shift Summary</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Shift</th><th>Cashier</th><th>Sales</th><th>Payments</th><th>Expected</th><th>Closing</th><th>Diff</th></tr></thead><tbody>
            @forelse($shiftSummary as $row)
                <tr>
                    <td>{{ $row->code }}<div class="text-muted small">{{ $row->status }}</div></td>
                    <td>{{ $row->cashier_name ?? '-' }}</td>
                    <td>{{ $row->sales_count }}<div class="text-muted small">Rp {{ number_format((float) $row->sales_total, 0, ',', '.') }}</div></td>
                    <td>Rp {{ number_format((float) $row->payment_total, 0, ',', '.') }}<div class="text-muted small">Cash: Rp {{ number_format((float) $row->cash_total, 0, ',', '.') }}</div></td>
                    <td>Rp {{ number_format((float) $row->expected_cash_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((float) $row->closing_cash_amount, 0, ',', '.') }}</td>
                    <td class="{{ (float) $row->difference_amount == 0.0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format((float) $row->difference_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Cash Difference</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Shift</th><th>Cashier</th><th>Closed</th><th>Diff</th></tr></thead><tbody>
            @forelse($cashDifference as $row)
                <tr><td>{{ $row->code }}</td><td>{{ $row->cashier_name ?? '-' }}</td><td>{{ $row->closed_at ? \Illuminate\Support\Carbon::parse($row->closed_at)->format('d/m/Y H:i') : '-' }}</td><td class="{{ (float) $row->difference_amount == 0.0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format((float) $row->difference_amount, 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
