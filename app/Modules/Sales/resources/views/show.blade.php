@extends('layouts.admin')

@section('title', 'Sale — ' . $sale->sale_number)

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

@php
    $isOverdue = $sale->isOverdue();
    $statusBadge = match($sale->status) {
        'finalized' => 'bg-green-lt text-green',
        'draft'     => 'bg-secondary-lt text-secondary',
        'voided'    => 'bg-red-lt text-red',
        default     => 'bg-orange-lt text-orange',
    };
    $payBadge = match($sale->payment_status) {
        'paid'      => 'bg-green-lt text-green',
        'partial'   => 'bg-orange-lt text-orange',
        'overpaid'  => 'bg-azure-lt text-azure',
        default     => 'bg-secondary-lt text-secondary',
    };
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">{{ $sale->sale_number }}</h2>
            <p class="text-muted mb-0">
                <span class="badge {{ $statusBadge }} me-1">{{ ucfirst($sale->status) }}</span>
                <span class="badge {{ $payBadge }} me-1">{{ ucfirst($sale->payment_status) }}</span>
                @if($isOverdue)
                    <span class="badge bg-red-lt text-red me-1">Overdue</span>
                @endif
                {{ optional($sale->transaction_date)->format('d M Y, H:i') ?? '-' }}
                @if($isAdvancedMode)
                    &middot; <span class="badge bg-blue-lt text-blue">{{ strtoupper($sale->source) }}</span>
                @endif
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            @if($sale->status === 'draft')
                <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-pencil me-1"></i>Edit Draft
                </a>
                <form method="POST" action="{{ route('sales.finalize', $sale) }}" class="d-inline-block m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-check me-1"></i>Finalize
                    </button>
                </form>
            @endif
            @if($sale->status === 'finalized')
                <a href="{{ route('sales.returns.create', ['sale_id' => $sale->id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-back me-1"></i>Create Return
                </a>
            @endif
            @if($sale->status === 'finalized' && (float) $sale->balance_due > 0 && Route::has('payments.create'))
                <a href="{{ route('payments.create', ['sale_id' => $sale->id]) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-cash me-1"></i>Record Payment
                </a>
            @endif
            @if($sale->source === 'pos' && $sale->status === 'finalized' && Route::has('pos.receipts.show') && auth()->user()->can('pos.print-receipt'))
                <a href="{{ route('pos.receipts.show', $sale) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-receipt me-1"></i>POS Receipt
                </a>
            @endif
            <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-printer me-1"></i>Print / Invoice
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Left: Items + Returns + History --}}
    <div class="col-lg-8">

        {{-- Items --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                @if($isAdvancedMode)
                                    <th>Discount</th>
                                    <th>Tax</th>
                                @endif
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                        <div class="text-muted small">
                                            {{ $item->variant_name_snapshot ?: '' }}
                                            @if($item->sku_snapshot) &middot; SKU: {{ $item->sku_snapshot }} @endif
                                        </div>
                                    </td>
                                    <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td>{{ $money->format((float) $item->unit_price, $sale->currency_code) }}</td>
                                    @if($isAdvancedMode)
                                        <td>{{ $money->format((float) $item->discount_total, $sale->currency_code) }}</td>
                                        <td>{{ $money->format((float) $item->tax_total, $sale->currency_code) }}</td>
                                    @endif
                                    <td class="fw-medium">{{ $money->format((float) $item->line_total, $sale->currency_code) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sales Returns --}}
        @if($sale->saleReturns->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Sales Returns</h3>
                <div class="card-options">
                    <span class="badge bg-secondary-lt text-secondary">{{ $sale->saleReturns->count() }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Return No.</th>
                                <th>Date</th>
                                <th>Grand Total</th>
                                <th>Status</th>
                                <th>Refund</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->saleReturns as $saleReturn)
                                @php
                                    $retBadge = match($saleReturn->status) {
                                        'finalized' => 'bg-green-lt text-green',
                                        'draft'     => 'bg-secondary-lt text-secondary',
                                        default     => 'bg-orange-lt text-orange',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $saleReturn->return_number }}</td>
                                    <td>{{ optional($saleReturn->return_date)->format('d M Y, H:i') ?? '-' }}</td>
                                    <td>{{ $money->format((float) $saleReturn->grand_total, $saleReturn->currency_code) }}</td>
                                    <td><span class="badge {{ $retBadge }}">{{ ucfirst($saleReturn->status) }}</span></td>
                                    <td><span class="text-muted small">{{ ucfirst($saleReturn->refund_status) }}</span></td>
                                    <td class="text-end align-middle">
                                        <a href="{{ route('sales.returns.show', $saleReturn) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="View">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Status History --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Status History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Event</th>
                                <th>Transition</th>
                                <th>Actor</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->statusHistories as $history)
                                <tr>
                                    <td class="text-muted small">{{ $history->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge bg-secondary-lt text-secondary">{{ ucfirst($history->event) }}</span></td>
                                    <td class="text-muted small">
                                        {{ $history->from_status ?: '—' }}
                                        <i class="ti ti-arrow-right mx-1" style="font-size:.75rem;"></i>
                                        {{ $history->to_status }}
                                    </td>
                                    <td>{{ $history->actor?->name ?? '-' }}</td>
                                    <td class="text-muted small">{{ $history->reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No history yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Receipt Reprint Audit (POS only) --}}
        @if($sale->source === 'pos')
            @php $reprintHistories = $sale->statusHistories->where('event', 'receipt_reprinted')->values(); @endphp
            @if($reprintHistories->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Receipt Reprint Audit</h3>
                    <div class="card-options">
                        <span class="badge bg-orange-lt text-orange">{{ $reprintHistories->count() }} reprints</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-vcenter">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Time</th>
                                    <th>Actor</th>
                                    <th>Reason</th>
                                    <th>Outlet / Shift</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reprintHistories as $history)
                                    <tr>
                                        <td class="text-muted small">{{ data_get($history->meta, 'reprint_sequence', '?') }}</td>
                                        <td class="text-muted small">{{ $history->created_at->format('d M Y, H:i') }}</td>
                                        <td>{{ $history->actor?->name ?? '-' }}</td>
                                        <td class="text-muted small">{{ $history->reason ?: '-' }}</td>
                                        <td class="text-muted small">
                                            {{ data_get($history->meta, 'outlet_id', '-') }}
                                            / {{ data_get($history->meta, 'pos_cash_session_id', '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        @endif

    </div>

    {{-- Right: Summary + Actions --}}
    <div class="col-lg-4">

        {{-- Sale Summary --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Summary</h3>
            </div>
            <div class="card-body p-0">
                {{-- Customer --}}
                <div class="px-4 py-3">
                    <div class="text-muted small mb-1"><i class="ti ti-user me-1"></i>Customer</div>
                    <div class="fw-medium">{{ $sale->customer_name_snapshot ?: ($sale->contact?->name ?? 'Guest / Walk-in') }}</div>
                    @if($sale->customer_email_snapshot)
                        <div class="text-muted small">{{ $sale->customer_email_snapshot }}</div>
                    @endif
                    @if($sale->customer_phone_snapshot)
                        <div class="text-muted small">{{ $sale->customer_phone_snapshot }}</div>
                    @endif
                </div>
                <hr class="m-0">
                {{-- Totals --}}
                <div class="px-4 py-3">
                    <div class="text-muted small mb-2"><i class="ti ti-calculator me-1"></i>Totals</div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Subtotal</span>
                        <span>{{ $money->format((float) $sale->subtotal, $sale->currency_code) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Discount</span>
                        <span>{{ $money->format((float) $sale->discount_total, $sale->currency_code) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Tax</span>
                        <span>{{ $money->format((float) $sale->tax_total, $sale->currency_code) }}</span>
                    </div>
                    <div class="d-flex justify-content-between fw-semibold border-top pt-2 mb-1">
                        <span>Grand Total</span>
                        <span>{{ $money->format((float) $sale->grand_total, $sale->currency_code) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Paid</span>
                        <span class="text-green">{{ $money->format((float) $sale->paid_total, $sale->currency_code) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small fw-medium">
                        <span class="text-muted">Balance Due</span>
                        <span class="{{ (float) $sale->balance_due > 0 ? 'text-orange' : 'text-green' }}">
                            {{ $money->format((float) $sale->balance_due, $sale->currency_code) }}
                        </span>
                    </div>
                </div>
                @if($sale->due_date)
                <hr class="m-0">
                <div class="px-4 py-3">
                    <div class="text-muted small mb-1"><i class="ti ti-calendar-due me-1"></i>Receivable</div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Due Date</span>
                        <span>{{ $sale->due_date->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Status</span>
                        <span class="{{ $isOverdue ? 'text-red fw-semibold' : 'text-muted' }}">
                            {{ $isOverdue ? 'Overdue' : ((float) $sale->balance_due > 0 ? 'Open receivable' : 'Settled') }}
                        </span>
                    </div>
                </div>
                @endif
                @if($sale->customer_note)
                <hr class="m-0">
                <div class="px-4 py-3">
                    <div class="text-muted small mb-1"><i class="ti ti-message-2 me-1"></i>Customer Note</div>
                    <div class="small">{{ $sale->customer_note }}</div>
                </div>
                @endif
                @if($sale->notes || $sale->attachment_path)
                <hr class="m-0">
                <div class="px-4 py-3">
                    @if($sale->notes)
                        <div class="text-muted small mb-1"><i class="ti ti-notes me-1"></i>Internal Notes</div>
                        <div class="small mb-2">{{ $sale->notes }}</div>
                    @endif
                    @if($sale->attachment_path)
                        <a href="{{ asset('storage/'.$sale->attachment_path) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-paperclip me-1"></i>Lihat Attachment
                        </a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Payments --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Payments</h3>
            </div>
            <div class="card-body">
                @php
                    $paymentAllocations = $sale->relationLoaded('paymentAllocations')
                        ? $sale->paymentAllocations->sortByDesc(fn ($a) => optional(optional($a->payment)->paid_at)->timestamp ?? 0)->values()
                        : collect();
                    $paymentProgress = (float) $sale->grand_total > 0
                        ? min(100, max(0, ((float) $sale->paid_total / (float) $sale->grand_total) * 100))
                        : 0;
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small">Payment progress</span>
                    <span class="fw-medium small">{{ number_format($paymentProgress, 0) }}%</span>
                </div>
                <div class="progress progress-sm mb-3">
                    <div class="progress-bar bg-primary" style="width:{{ $paymentProgress }}%"
                        role="progressbar" aria-valuenow="{{ $paymentProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                @if($paymentAllocations->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-vcenter">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentAllocations as $allocation)
                                    @php $payment = $allocation->payment; @endphp
                                    <tr>
                                        <td class="text-muted small">{{ optional(optional($payment)->paid_at)->format('d M Y') ?? '-' }}</td>
                                        <td class="small">{{ optional(optional($payment)->method)->name ?: '-' }}</td>
                                        <td class="small fw-medium">{{ $money->format((float) $allocation->amount, optional($payment)->currency_code ?: $sale->currency_code) }}</td>
                                        <td class="text-end">
                                            @if($payment)
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Detail">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted small mb-3">No payments recorded yet.</p>
                @endif

                @if($sale->status === 'finalized' && Route::has('payments.create'))
                    <a href="{{ route('payments.create', ['sale_id' => $sale->id]) }}" class="btn btn-outline-primary w-100">
                        <i class="ti ti-plus me-1"></i>Add Payment
                    </a>
                @endif
            </div>
        </div>

        {{-- Actions (Cancel / Void / Reprint) --}}
        @if(in_array($sale->status, ['draft', 'finalized']))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="card-body">
                @if($sale->status === 'draft')
                    <form method="POST" action="{{ route('sales.cancel', $sale) }}">
                        @csrf
                        <label class="form-label">Cancel reason</label>
                        <textarea name="reason" class="form-control mb-2" rows="2" placeholder="Optional"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100"
                            data-confirm="Cancel this draft sale?">
                            <i class="ti ti-ban me-1"></i>Cancel Draft
                        </button>
                    </form>
                @endif

                @if($sale->status === 'finalized')
                    <form method="POST" action="{{ route('sales.void', $sale) }}">
                        @csrf
                        <label class="form-label">Void reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control mb-2" rows="2" required
                            placeholder="Reason for voiding is required"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100"
                            data-confirm="Void this sale? This cannot be undone.">
                            <i class="ti ti-trash me-1"></i>Void Sale
                        </button>
                    </form>

                    @if($sale->source === 'pos' && Route::has('pos.receipts.reprint') && auth()->user()->can('pos.reprint-receipt'))
                        <hr>
                        <form method="POST" action="{{ route('pos.receipts.reprint', $sale) }}">
                            @csrf
                            <label class="form-label">Reprint reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control mb-2" rows="2" required minlength="10"
                                placeholder="e.g. Customer lost original receipt and requested a duplicate."></textarea>
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="ti ti-printer me-1"></i>Reprint Receipt
                            </button>
                            <div class="form-hint">Authorized users only.</div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
        @endif

        @if($sale->status === 'voided')
        <div class="alert alert-danger mt-3">
            <div class="d-flex gap-2">
                <i class="ti ti-ban flex-shrink-0"></i>
                <div>
                    <div class="fw-semibold">Sale Voided</div>
                    @if($sale->void_reason)
                        <div class="small mt-1">{{ $sale->void_reason }}</div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Audit --}}
        @include('shared.accounting.audit-summary', [
            'cardClass' => 'mt-3',
            'entries' => [
                ['label' => 'Dibuat oleh', 'user' => $sale->creator, 'timestamp' => $sale->created_at, 'icon' => 'ti-user-plus', 'color' => 'green'],
                ['label' => 'Diubah terakhir', 'user' => $sale->updater, 'timestamp' => $sale->updated_at, 'icon' => 'ti-user-edit', 'color' => 'blue'],
                ['label' => 'Finalized oleh', 'user' => $sale->finalizer, 'timestamp' => $sale->finalized_at, 'icon' => 'ti-check', 'color' => 'green'],
                ['label' => 'Void oleh', 'user' => $sale->voider, 'timestamp' => $sale->voided_at, 'icon' => 'ti-ban', 'color' => 'red'],
                ['label' => 'Cancelled oleh', 'user' => $sale->canceller, 'timestamp' => $sale->cancelled_at, 'icon' => 'ti-x', 'color' => 'orange'],
            ],
        ])

    </div>
</div>
@include('shared.accounting.activity-log', [
    'activities' => $activities,
    'fieldLabels' => [
        'contact_id' => 'Customer',
        'status' => 'Status',
        'payment_status' => 'Payment status',
        'source' => 'Source',
        'branch_id' => 'Branch',
        'pos_cash_session_id' => 'POS session',
        'transaction_date' => 'Transaction date',
        'due_date' => 'Due date',
        'subtotal' => 'Subtotal',
        'discount_total' => 'Discount',
        'tax_total' => 'Tax',
        'grand_total' => 'Grand total',
        'paid_total' => 'Paid',
        'balance_due' => 'Balance due',
        'currency_code' => 'Currency',
        'notes' => 'Notes',
        'customer_note' => 'Customer note',
        'attachment_path' => 'Attachment',
        'external_reference' => 'External reference',
    ],
    'money' => $money,
    'currency' => $sale->currency_code,
])
@endsection
