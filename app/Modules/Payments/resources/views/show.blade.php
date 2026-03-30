@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $payment->payment_number }}</h2>
        <div class="text-muted small">{{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }} | {{ optional($payment->method)->name ?: '-' }}</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment Allocation</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Target</th>
                            <th>Reference</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->allocations as $allocation)
                            <tr>
                                <td>{{ class_basename($allocation->payable_type) }}</td>
                                <td>
                                    @if($allocation->payable instanceof \App\Modules\Sales\Models\Sale)
                                        <a href="{{ route('sales.show', $allocation->payable) }}">{{ $allocation->payable->sale_number }}</a>
                                    @elseif($allocation->payable instanceof \App\Modules\Sales\Models\SaleReturn)
                                        <a href="{{ route('sales.returns.show', $allocation->payable) }}">{{ $allocation->payable->return_number }}</a>
                                    @elseif($allocation->payable instanceof \App\Modules\Purchases\Models\Purchase)
                                        <a href="{{ route('purchases.show', $allocation->payable) }}">{{ $allocation->payable->purchase_number }}</a>
                                    @else
                                        #{{ $allocation->payable_id }}
                                    @endif
                                </td>
                                <td>{{ $money->format((float) $allocation->amount, $payment->currency_code) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Status History</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Transition</th>
                            <th>Reason</th>
                            <th>Actor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->statusLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td>{{ ucfirst($log->event) }}</td>
                                <td>{{ $log->from_status ?: '-' }} -> {{ $log->to_status }}</td>
                                <td>{{ $log->reason ?: '-' }}</td>
                                <td>{{ optional($log->actor)->name ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Summary</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">{{ $paymentStatusOptions[$payment->status] ?? ucfirst($payment->status) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Amount</div>
                    <div class="fw-semibold">{{ $money->format((float) $payment->amount, $payment->currency_code) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Reference</div>
                    <div>{{ $payment->reference_number ?: '-' }}</div>
                    <div class="text-muted small">{{ $payment->external_reference ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Receiver</div>
                    <div>{{ optional($payment->receiver)->name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Notes</div>
                    <div>{{ $payment->notes ?: '-' }}</div>
                </div>

                @if($payment->status === 'posted')
                    <form method="POST" action="{{ route('payments.void', $payment) }}">
                        @csrf
                        <label class="form-label">Void reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Alasan void wajib diisi"></textarea>
                        <button type="submit" class="btn btn-danger w-100 mt-2">Void Payment</button>
                    </form>
                @else
                    <div class="alert alert-danger mb-0">
                        <div class="fw-semibold">Voided</div>
                        <div class="small">{{ $payment->void_reason ?: '-' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
