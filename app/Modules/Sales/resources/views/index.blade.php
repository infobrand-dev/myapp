@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Sales</h2>
        <div class="text-muted small">Source of truth transaksi penjualan dari manual entry, POS, online, dan channel integrasi lainnya.</div>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-primary">Create Sale</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Sale no, customer, item">
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
                <label class="form-label">Payment</label>
                <select name="payment_status" class="form-select">
                    <option value="">Semua payment</option>
                    @foreach($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">Semua source</option>
                    @foreach($sourceOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer</label>
                <select name="contact_id" class="form-select">
                    <option value="">Semua customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) ($filters['contact_id'] ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
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
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="text-muted small">Boundary: stock tetap di Inventory, rule diskon di Discounts, master customer di Contacts, dan master produk di Products.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Sale</th>
                    <th>Customer</th>
                    <th>Source</th>
                    <th>Items</th>
                    <th>Totals</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>
                            <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-semibold">{{ $sale->sale_number }}</a>
                            <div class="text-muted small">{{ optional($sale->transaction_date)->format('d M Y H:i') ?? '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $sale->customer_name_snapshot ?: ($sale->contact ? $sale->contact->name : 'Guest / Walk-in') }}</div>
                            <div class="text-muted small">{{ $sale->customer_phone_snapshot ?: ($sale->contact ? ($sale->contact->mobile ?: $sale->contact->phone) : '-') }}</div>
                        </td>
                        <td><span class="badge bg-blue-lt text-blue">{{ strtoupper($sale->source) }}</span></td>
                        <td>{{ $sale->items_count }}</td>
                        <td>
                            <div>Subtotal: Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</div>
                            <div class="text-muted small">Grand: Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</div>
                        </td>
                        <td>
                            <div><span class="badge bg-{{ $sale->status === 'finalized' ? 'success' : ($sale->status === 'draft' ? 'secondary' : 'danger') }}-lt text-{{ $sale->status === 'finalized' ? 'success' : ($sale->status === 'draft' ? 'secondary' : 'danger') }}">{{ ucfirst($sale->status) }}</span></div>
                            <div class="text-muted small">{{ ucfirst($sale->payment_status) }}</div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                @if($sale->status === 'draft')
                                    <a class="btn btn-icon btn-outline-secondary" href="{{ route('sales.edit', $sale) }}" title="Edit draft">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                @endif
                                <a class="btn btn-icon btn-outline-primary" href="{{ route('sales.invoice', $sale) }}" title="Invoice">
                                    <i class="ti ti-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada sales.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $sales->links() }}
    </div>
</div>

<div class="row g-3 mt-1">
    @foreach($dependencies as $dependency)
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="fw-semibold text-uppercase small">{{ $dependency['module'] }}</div>
                    <div class="text-muted small">{{ $dependency['notes'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
