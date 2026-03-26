@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Purchases</h2>
        <div class="text-muted small">Daftar transaksi pembelian dari supplier.</div>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary">Create Purchase</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchases.index') }}" class="row g-3">
            <div class="col-md-3"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Purchase no, supplier, invoice"></div>
            <div class="col-md-3"><label class="form-label">Supplier</label><select name="contact_id" class="form-select"><option value="">Semua supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) ($filters['contact_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Semua status</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Payment</label><select name="payment_status" class="form-select"><option value="">Semua payment</option>@foreach($paymentStatusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-1"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-1"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Purchase</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Totals</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td>
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-decoration-none fw-semibold">{{ $purchase->purchase_number }}</a>
                            <div class="text-muted small">{{ optional($purchase->purchase_date)->format('d M Y H:i') ?? '-' }}</div>
                            <div class="text-muted small">{{ $purchase->supplier_invoice_number ?: '-' }}</div>
                        </td>
                        <td>{{ $purchase->supplier_name_snapshot ?: (optional($purchase->supplier)->name ?: '-') }}</td>
                        <td>{{ $purchase->items_count }}</td>
                        <td>
                            <div>Grand: Rp {{ number_format((float) $purchase->grand_total, 0, ',', '.') }}</div>
                            <div class="text-muted small">Paid: Rp {{ number_format((float) $purchase->paid_total, 0, ',', '.') }}</div>
                        </td>
                        <td>
                            <div><span class="badge bg-secondary-lt text-secondary">{{ $statusOptions[$purchase->status] ?? ucfirst($purchase->status) }}</span></div>
                            <div class="text-muted small">{{ $paymentStatusOptions[$purchase->payment_status] ?? ucfirst($purchase->payment_status) }}</div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                @if($purchase->status === 'draft')
                                    <a class="btn btn-icon btn-outline-secondary" href="{{ route('purchases.edit', $purchase) }}"><i class="ti ti-edit"></i></a>
                                @endif
                                <a class="btn btn-icon btn-outline-primary" href="{{ route('purchases.print', $purchase) }}"><i class="ti ti-printer"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada pembelian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $purchases->links() }}</div>
</div>

<div class="row g-3 mt-1">
    @foreach($dependencies as $dependency)
        <div class="col-md-6">
            <div class="card"><div class="card-body"><div class="fw-semibold text-uppercase small">{{ $dependency['module'] }}</div><div class="text-muted small">{{ $dependency['notes'] }}</div></div></div>
        </div>
    @endforeach
</div>
@endsection
