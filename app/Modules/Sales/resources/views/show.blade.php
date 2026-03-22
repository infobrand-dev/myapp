@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $sale->sale_number }}</h2>
        <div class="text-muted small">{{ optional($sale->transaction_date)->format('d M Y H:i') ?? '-' }} | Source: {{ strtoupper($sale->source) }}</div>
    </div>
    <div class="btn-list">
        @if($sale->status === 'draft')
            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline-secondary">Edit Draft</a>
            <form method="POST" action="{{ route('sales.finalize', $sale) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Finalize</button>
            </form>
        @endif
        @if($sale->status === 'finalized')
            <a href="{{ route('sales.returns.create', ['sale_id' => $sale->id]) }}" class="btn btn-outline-warning">Create Return</a>
        @endif
        @if($sale->status === 'finalized' && (float) $sale->balance_due > 0 && Route::has('payments.create'))
            <a href="{{ route('payments.create', ['sale_id' => $sale->id]) }}" class="btn btn-primary">Record Payment</a>
        @endif
        @if($sale->source === 'pos' && $sale->status === 'finalized' && Route::has('pos.receipts.show') && auth()->user()->can('pos.print-receipt'))
            <a href="{{ route('pos.receipts.show', $sale) }}" class="btn btn-outline-dark">POS Receipt</a>
        @endif
        <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-outline-primary">Print / Invoice</a>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Sales Detail</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                    <div class="text-muted small">{{ $item->variant_name_snapshot ?: 'No variant' }} | SKU: {{ $item->sku_snapshot ?: '-' }}</div>
                                </td>
                                <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $item->discount_total, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $item->tax_total, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Sales Returns</h3></div>
            <div class="card-body">
                @if($sale->saleReturns->isNotEmpty())
                    @foreach($sale->saleReturns as $saleReturn)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <a href="{{ route('sales.returns.show', $saleReturn) }}" class="fw-semibold text-decoration-none">{{ $saleReturn->return_number }}</a>
                                    <div class="text-muted small">{{ optional($saleReturn->return_date)->format('d M Y H:i') ?? '-' }}</div>
                                </div>
                                <span class="badge bg-{{ $saleReturn->status === 'finalized' ? 'success' : ($saleReturn->status === 'draft' ? 'secondary' : 'warning') }}-lt text-{{ $saleReturn->status === 'finalized' ? 'success' : ($saleReturn->status === 'draft' ? 'secondary' : 'warning') }}">{{ ucfirst($saleReturn->status) }}</span>
                            </div>
                            <div class="small mt-1">Grand: Rp {{ number_format((float) $saleReturn->grand_total, 0, ',', '.') }}</div>
                            <div class="text-muted small">Refund: {{ ucfirst($saleReturn->refund_status) }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">Belum ada sales return untuk transaksi ini.</div>
                @endif
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
                        @forelse($sale->statusHistories as $history)
                            <tr>
                                <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                                <td>{{ ucfirst($history->event) }}</td>
                                <td>{{ $history->from_status ?: '-' }} -> {{ $history->to_status }}</td>
                                <td>{{ $history->reason ?: '-' }}</td>
                                <td>{{ $history->actor ? $history->actor->name : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $reprintHistories = $sale->statusHistories->filter(fn ($history) => $history->event === 'receipt_reprinted')->values();
        @endphp
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Receipt Reprint Audit</h3></div>
            <div class="card-body">
                @if($sale->source !== 'pos')
                    <div class="text-muted">Transaksi ini bukan transaksi POS.</div>
                @elseif($reprintHistories->isNotEmpty())
                    @foreach($reprintHistories as $history)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="fw-semibold">Reprint #{{ data_get($history->meta, 'reprint_sequence', '?') }}</div>
                                <div class="text-muted small">{{ $history->created_at->format('d M Y H:i') }}</div>
                            </div>
                            <div class="small mt-1">Actor: {{ $history->actor ? $history->actor->name : '-' }}</div>
                            <div class="small">Reason: {{ $history->reason ?: '-' }}</div>
                            <div class="text-muted small">
                                Outlet: {{ data_get($history->meta, 'outlet_id', '-') }}
                                | Shift: {{ data_get($history->meta, 'pos_cash_session_id', '-') }}
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">Belum ada reprint receipt untuk transaksi ini.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Customer</div>
                    <div>{{ $sale->customer_name_snapshot ?: ($sale->contact ? $sale->contact->name : 'Guest / Walk-in') }}</div>
                    <div class="text-muted small">{{ $sale->customer_email_snapshot ?: '-' }}</div>
                    <div class="text-muted small">{{ $sale->customer_phone_snapshot ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">{{ $statusOptions[$sale->status] ?? ucfirst($sale->status) }}</div>
                    <div class="text-muted small">Payment: {{ $paymentStatusOptions[$sale->payment_status] ?? ucfirst($sale->payment_status) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Notes</div>
                    <div>{{ $sale->notes ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Totals</div>
                    <div>Subtotal: Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</div>
                    <div>Discount: Rp {{ number_format((float) $sale->discount_total, 0, ',', '.') }}</div>
                    <div>Tax: Rp {{ number_format((float) $sale->tax_total, 0, ',', '.') }}</div>
                    <div class="fw-semibold">Grand: Rp {{ number_format((float) $sale->grand_total, 0, ',', '.') }}</div>
                    <div>Paid: Rp {{ number_format((float) $sale->paid_total, 0, ',', '.') }}</div>
                    <div>Balance Due: Rp {{ number_format((float) $sale->balance_due, 0, ',', '.') }}</div>
                </div>

                @if($sale->status === 'draft')
                    <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="mb-3">
                        @csrf
                        <label class="form-label">Cancel reason</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Opsional"></textarea>
                        <button type="submit" class="btn btn-outline-warning w-100 mt-2">Cancel Draft</button>
                    </form>
                @endif

                @if($sale->status === 'finalized')
                    <form method="POST" action="{{ route('sales.void', $sale) }}">
                        @csrf
                        <label class="form-label">Void reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Alasan void wajib diisi"></textarea>
                        <button type="submit" class="btn btn-danger w-100 mt-2">Void Sale</button>
                    </form>
                @endif

                @if($sale->source === 'pos' && $sale->status === 'finalized' && Route::has('pos.receipts.reprint') && auth()->user()->can('pos.reprint-receipt'))
                    <form method="POST" action="{{ route('pos.receipts.reprint', $sale) }}" class="mt-3">
                        @csrf
                        <label class="form-label">Receipt reprint reason</label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="10" placeholder="Contoh: Customer kehilangan struk original dan meminta duplikat."></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100 mt-2">Reprint POS Receipt</button>
                        <div class="form-text">Hanya user dengan permission reprint yang dapat melihat aksi ini.</div>
                    </form>
                @endif

                @if($sale->status === 'voided')
                    <div class="alert alert-danger mb-0">
                        <div class="fw-semibold">Voided</div>
                        <div class="small">{{ $sale->void_reason }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="card-body">
                @php
                    $paymentAllocations = $sale->relationLoaded('paymentAllocations')
                        ? $sale->paymentAllocations->sortByDesc(fn ($allocation) => optional(optional($allocation->payment)->paid_at)->timestamp ?? 0)->values()
                        : collect();
                    $paymentProgress = (float) $sale->grand_total > 0 ? min(100, max(0, ((float) $sale->paid_total / (float) $sale->grand_total) * 100)) : 0;
                @endphp

                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Payment Progress</div>
                            <div class="fw-semibold">{{ number_format($paymentProgress, 0) }}%</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Balance Due</div>
                            <div class="fw-semibold {{ (float) $sale->balance_due > 0 ? 'text-warning' : 'text-success' }}">Rp {{ number_format((float) $sale->balance_due, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-2">
                        <div class="progress-bar bg-primary" style="width: {{ $paymentProgress }}%" role="progressbar" aria-valuenow="{{ $paymentProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                @if($paymentAllocations->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-vcenter">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Allocated</th>
                                    <th>Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentAllocations as $allocation)
                                    @php $payment = $allocation->payment; @endphp
                                    <tr>
                                        <td>{{ optional(optional($payment)->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                                        <td>{{ optional(optional($payment)->method)->name ?: '-' }}</td>
                                        <td>{{ optional($payment)->reference_number ?: (optional($payment)->external_reference ?: '-') }}</td>
                                        <td>Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                                        <td>{{ ucfirst(optional($payment)->status ?: '-') }}</td>
                                        <td class="text-end">
                                            @if($payment)
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted mb-3">Belum ada payment tercatat.</div>
                @endif

                @if($sale->status === 'finalized' && Route::has('payments.create'))
                    <div class="d-grid gap-2">
                        <a href="{{ route('payments.create', ['sale_id' => $sale->id]) }}" class="btn btn-outline-primary">Add Payment via Payments Module</a>
                        @if((float) $sale->balance_due > 0)
                            <div class="text-muted small">Gunakan tombol ini untuk mencatat pelunasan atau partial payment atas outstanding sale ini.</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Audit</h3></div>
            <div class="card-body">
                <div class="text-muted small">Created by</div>
                <div class="mb-2">{{ $sale->creator ? $sale->creator->name : '-' }}</div>
                <div class="text-muted small">Updated by</div>
                <div class="mb-2">{{ $sale->updater ? $sale->updater->name : '-' }}</div>
                <div class="text-muted small">Finalized by</div>
                <div class="mb-2">{{ $sale->finalizer ? $sale->finalizer->name : '-' }}</div>
                <div class="text-muted small">Voided by</div>
                <div class="mb-2">{{ $sale->voider ? $sale->voider->name : '-' }}</div>
                <div class="text-muted small">Cancelled by</div>
                <div>{{ $sale->canceller ? $sale->canceller->name : '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
