@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Sales Returns</h2>
        <div class="text-muted small">Daftar retur penjualan.</div>
    </div>
    <a href="{{ route('sales.returns.create') }}" class="btn btn-primary">Create Sales Return</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Return no, sale no, customer">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sale</label>
                <select name="sale_id" class="form-select">
                    <option value="">Semua sale</option>
                    @foreach($sales as $sale)
                        <option value="{{ $sale->id }}" @selected((string) ($filters['sale_id'] ?? '') === (string) $sale->id)>{{ $sale->sale_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Customer</label>
                <select name="contact_id" class="form-select">
                    <option value="">Semua customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) ($filters['contact_id'] ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Refund</label>
                <select name="refund_status" class="form-select">
                    <option value="">Semua refund</option>
                    @foreach($refundStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['refund_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('sales.returns.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Return</th>
                    <th>Sale</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $saleReturn)
                    <tr>
                        <td>
                            <a href="{{ route('sales.returns.show', $saleReturn) }}" class="text-decoration-none fw-semibold">{{ $saleReturn->return_number }}</a>
                            <div class="text-muted small">{{ optional($saleReturn->return_date)->format('d M Y H:i') ?? '-' }}</div>
                        </td>
                        <td><a href="{{ route('sales.show', $saleReturn->sale_id) }}">{{ $saleReturn->sale_number_snapshot }}</a></td>
                        <td>{{ $saleReturn->customer_name_snapshot ?: '-' }}</td>
                        <td>{{ $saleReturn->items_count }}</td>
                        <td>Rp {{ number_format((float) $saleReturn->grand_total, 0, ',', '.') }}</td>
                        <td>
                            <div><span class="badge bg-{{ $saleReturn->status === 'finalized' ? 'success' : ($saleReturn->status === 'draft' ? 'secondary' : 'warning') }}-lt text-{{ $saleReturn->status === 'finalized' ? 'success' : ($saleReturn->status === 'draft' ? 'secondary' : 'warning') }}">{{ ucfirst($saleReturn->status) }}</span></div>
                            <div class="text-muted small">Refund: {{ $refundStatusOptions[$saleReturn->refund_status] ?? ucfirst($saleReturn->refund_status) }}</div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sales.returns.print', $saleReturn) }}" class="btn btn-icon btn-outline-primary" title="Print return note">
                                <i class="ti ti-printer"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada retur penjualan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $returns->links() }}
    </div>
</div>
@endsection
