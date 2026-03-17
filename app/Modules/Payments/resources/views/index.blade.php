@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Payments</h2>
        <div class="text-muted small">Pusat pencatatan pembayaran lintas transaksi.</div>
    </div>
    <div class="btn-list">
        @can('payments.create')
            <a href="{{ route('payments.create') }}" class="btn btn-primary">Create Payment</a>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Payment / reference / sale">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Method</label>
                <select name="payment_method_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) ($filters['payment_method_id'] ?? '') === (string) $method->id)>{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">Semua</option>
                    @foreach($paymentSourceOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Receiver</label>
                <select name="received_by" class="form-select" @disabled(($filters['scope'] ?? '') === 'own')>
                    <option value="">Semua</option>
                    @foreach($receivers as $receiver)
                        <option value="{{ $receiver->id }}" @selected((string) ($filters['received_by'] ?? '') === (string) $receiver->id)>{{ $receiver->name }}</option>
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
            <div class="col-md-8 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Payment</th>
                    <th>Paid At</th>
                    <th>Method</th>
                    <th>Allocation</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Receiver</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>
                            <a href="{{ route('payments.show', $payment) }}" class="fw-semibold text-decoration-none">{{ $payment->payment_number }}</a>
                            <div class="text-muted small">{{ $payment->reference_number ?: ($payment->external_reference ?: '-') }}</div>
                        </td>
                        <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ optional($payment->method)->name ?: '-' }}</td>
                        <td>
                            @foreach($payment->allocations as $allocation)
                                <div class="small">
                                    @if($allocation->payable instanceof \App\Modules\Sales\Models\Sale)
                                        {{ $allocation->payable->sale_number }} | {{ $allocation->payable->customer_name_snapshot ?: 'Guest' }}
                                    @else
                                        {{ class_basename($allocation->payable_type) }} #{{ $allocation->payable_id }}
                                    @endif
                                    : Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}
                                </div>
                            @endforeach
                        </td>
                        <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td><span class="badge bg-{{ $payment->status === 'voided' ? 'red' : 'azure' }}-lt">{{ ucfirst($payment->status) }}</span></td>
                        <td>{{ optional($payment->receiver)->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada payment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $payments->links() }}
    </div>
</div>
@endsection
