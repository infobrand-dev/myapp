@extends('layouts.admin')

@section('title', $payment->payment_number)

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Keuangan / Payments</div>
        <h2 class="page-title">{{ $payment->payment_number }}</h2>
        <p class="text-muted mb-0">
            {{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }} &middot; {{ optional($payment->method)->name ?: '-' }}
            &middot; {{ $reconciliationStatusOptions[$payment->reconciliation_status] ?? ucfirst(str_replace('_', ' ', $payment->reconciliation_status)) }}
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @include('shared.accounting.mode-badge')
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment Allocation</h3></div>
            <div class="card-body p-0">
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
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Status History</h3></div>
            <div class="card-body p-0">
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
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Summary</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">{{ $paymentStatusOptions[$payment->status] ?? ucfirst($payment->status) }}</div>
                    <div class="text-muted small mt-1">Reconciliation: {{ $reconciliationStatusOptions[$payment->reconciliation_status] ?? ucfirst(str_replace('_', ' ', $payment->reconciliation_status)) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Amount</div>
                    <div class="fw-semibold">{{ $money->format((float) $payment->amount, $payment->currency_code) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Reference</div>
                    <div>{{ $isAdvancedMode ? ($payment->reference_number ?: '-') : '-' }}</div>
                    @if($isAdvancedMode)
                        <div class="text-muted small">{{ $payment->external_reference ?: '-' }}</div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Receiver</div>
                    <div>{{ $isAdvancedMode ? (optional($payment->receiver)->name ?: '-') : (optional($payment->creator)->name ?: '-') }}</div>
                    @if($isAdvancedMode && $payment->reconciled_at)
                        <div class="text-muted small">Reconciled {{ $payment->reconciled_at->format('d M Y H:i') }} oleh {{ optional($payment->reconciler)->name ?: '-' }}</div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Proof of Payment</div>
                    @if($payment->hasProof())
                        <a href="{{ asset('storage/'.$payment->proof_file_path) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-paperclip me-1"></i>Lihat Bukti
                        </a>
                    @else
                        <div>-</div>
                    @endif
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

        @include('shared.accounting.audit-summary', [
            'cardClass' => 'mt-3',
            'entries' => [
                ['label' => 'Dibuat oleh', 'user' => $payment->creator, 'timestamp' => $payment->created_at, 'icon' => 'ti-user-plus', 'color' => 'green'],
                ['label' => 'Diubah terakhir', 'user' => $payment->updater, 'timestamp' => $payment->updated_at, 'icon' => 'ti-user-edit', 'color' => 'blue'],
                ['label' => 'Void oleh', 'user' => $payment->voider, 'timestamp' => $payment->voided_at, 'icon' => 'ti-ban', 'color' => 'red'],
            ],
        ])
    </div>
</div>
@include('shared.accounting.activity-log', [
    'activities' => $activities,
    'fieldLabels' => [
        'payment_method_id' => 'Payment method',
        'amount' => 'Amount',
        'currency_code' => 'Currency',
        'paid_at' => 'Paid at',
        'status' => 'Status',
        'reconciliation_status' => 'Reconciliation status',
        'source' => 'Source',
        'channel' => 'Channel',
        'reference_number' => 'Reference number',
        'external_reference' => 'External reference',
        'proof_file_path' => 'Proof of payment',
        'branch_id' => 'Branch',
        'notes' => 'Notes',
        'received_by' => 'Received by',
    ],
    'money' => $money,
    'currency' => $payment->currency_code,
])
@endsection
