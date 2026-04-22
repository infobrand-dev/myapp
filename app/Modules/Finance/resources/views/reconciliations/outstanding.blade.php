@extends('layouts.admin')

@section('title', 'Outstanding Reconciliation')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp

@include('finance::partials.accounting-nav')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Finance / Reconciliation</div>
            <h2 class="page-title">Outstanding Unreconciled</h2>
            <p class="text-muted mb-0">Lihat payment yang belum reconciled dan statement line yang masih open dari satu layar kerja.</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Unreconciled Payments</div><div class="fs-3 fw-bold">{{ $summary['unreconciled_payment_count'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Payment Total</div><div class="fs-3 fw-bold">{{ $money->format((float) $summary['unreconciled_payment_total'], 'IDR') }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Open Statement Lines</div><div class="fs-3 fw-bold">{{ $summary['open_statement_count'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Statement Total</div><div class="fs-3 fw-bold">{{ $money->format((float) $summary['open_statement_total'], 'IDR') }}</div></div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Finance Account</label>
                <select name="finance_account_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) ($filters['finance_account_id'] ?? '') === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-12">
                <a href="{{ route('finance.reconciliations.outstanding') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Unreconciled Payments</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Payment</th>
                                <th>Account</th>
                                <th>Paid At</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unreconciledPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><a href="{{ route('payments.show', $payment) }}">{{ $payment->payment_number }}</a></div>
                                        <div class="text-muted small">{{ optional($payment->method)->name ?: '-' }}</div>
                                    </td>
                                    <td>{{ optional(optional($payment->method)->financeAccount)->name ?: '-' }}</td>
                                    <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                                    <td>{{ $money->format((float) $payment->amount, $payment->currency_code ?: 'IDR') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Tidak ada payment unreconciled pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $unreconciledPayments->links() }}</div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Open Statement Lines</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Statement</th>
                                <th>Account</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($openStatementLines as $line)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <a href="{{ route('finance.reconciliations.show', $line->reconciliation) }}">
                                                {{ optional($line->transaction_date)->format('d M Y H:i') ?: '-' }}
                                            </a>
                                        </div>
                                        <div class="text-muted small">{{ $line->reference_number ?: ($line->description ?: '-') }}</div>
                                    </td>
                                    <td>{{ optional(optional($line->reconciliation)->account)->name ?: '-' }}</td>
                                    <td>{{ $money->format((float) $line->amount, 'IDR') }}</td>
                                    <td>
                                        @if($line->match_status === 'suggested')
                                            <span class="badge bg-azure-lt text-azure">Suggested</span>
                                            @if($line->suggestedReconcilable)
                                                <div class="text-muted small">{{ class_basename($line->suggested_reconcilable_type) }} suggestion</div>
                                            @endif
                                        @elseif($line->match_status === 'exception')
                                            <span class="badge bg-red-lt text-red">Exception</span>
                                            @if($line->resolution_reason)
                                                <div class="text-muted small">{{ $line->resolution_reason }}</div>
                                            @endif
                                        @elseif($line->match_status === 'ignored')
                                            <span class="badge bg-yellow-lt text-yellow">Ignored</span>
                                            @if($line->resolution_reason)
                                                <div class="text-muted small">{{ $line->resolution_reason }}</div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Unmatched</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Tidak ada statement line outstanding pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $openStatementLines->links() }}</div>
        </div>
    </div>
</div>
@endsection
