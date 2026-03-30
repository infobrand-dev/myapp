@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $saleReturn->return_number }}</h2>
        <div class="text-muted small">{{ optional($saleReturn->return_date)->format('d M Y H:i') ?? '-' }} | Sale: {{ $saleReturn->sale_number_snapshot }}</div>
    </div>
    <div class="btn-list">
        @if($saleReturn->status === 'draft')
            <form method="POST" action="{{ route('sales.returns.finalize', $saleReturn) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Finalize Return</button>
            </form>
        @endif
        <a href="{{ route('sales.returns.print', $saleReturn) }}" class="btn btn-outline-primary">Print Return Note</a>
        <a href="{{ route('sales.returns.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Return Detail</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Original Qty</th>
                            <th>Prev Returned</th>
                            <th>Qty Return</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($saleReturn->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                    <div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }} | SKU: {{ $item->sku_snapshot ?: '-' }}</div>
                                </td>
                                <td>{{ number_format((float) $item->sale_qty_snapshot, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->previous_returned_qty_snapshot, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->qty_returned, 2, ',', '.') }}</td>
                                <td>{{ $money->format((float) $item->line_total, $saleReturn->currency_code) }}</td>
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
                        @forelse($saleReturn->statusLogs as $log)
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
                    <div class="text-muted small">Sale Reference</div>
                    <div><a href="{{ route('sales.show', $saleReturn->sale_id) }}">{{ $saleReturn->sale_number_snapshot }}</a></div>
                    <div class="text-muted small">{{ $saleReturn->customer_name_snapshot ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">{{ $statusOptions[$saleReturn->status] ?? ucfirst($saleReturn->status) }}</div>
                    <div class="text-muted small">Inventory: {{ $inventoryStatusOptions[$saleReturn->inventory_status] ?? ucfirst($saleReturn->inventory_status) }}</div>
                    <div class="text-muted small">Refund: {{ $refundStatusOptions[$saleReturn->refund_status] ?? ucfirst($saleReturn->refund_status) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Reason</div>
                    <div>{{ $saleReturn->reason }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Notes</div>
                    <div>{{ $saleReturn->notes ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Totals</div>
                    <div>Subtotal: {{ $money->format((float) $saleReturn->subtotal, $saleReturn->currency_code) }}</div>
                    <div>Discount: {{ $money->format((float) $saleReturn->discount_total, $saleReturn->currency_code) }}</div>
                    <div>Tax: {{ $money->format((float) $saleReturn->tax_total, $saleReturn->currency_code) }}</div>
                    <div class="fw-semibold">Grand Return: {{ $money->format((float) $saleReturn->grand_total, $saleReturn->currency_code) }}</div>
                    <div>Refunded: {{ $money->format((float) $saleReturn->refunded_total, $saleReturn->currency_code) }}</div>
                    <div>Refund Balance: {{ $money->format((float) $saleReturn->refund_balance, $saleReturn->currency_code) }}</div>
                </div>

                @if($saleReturn->status === 'draft')
                    <form method="POST" action="{{ route('sales.returns.cancel', $saleReturn) }}" class="mb-3">
                        @csrf
                        <label class="form-label">Cancel reason</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Opsional"></textarea>
                        <button type="submit" class="btn btn-outline-warning w-100 mt-2">Cancel Draft Return</button>
                    </form>
                @endif

                @if($saleReturn->status === 'finalized' && $saleReturn->refund_required && Route::has('payments.create'))
                    <a href="{{ route('payments.create', ['sale_return_id' => $saleReturn->id]) }}" class="btn btn-outline-primary w-100">Process Refund via Payments</a>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Audit</h3></div>
            <div class="card-body">
                <div class="text-muted small">Created by</div>
                <div class="mb-2">{{ optional($saleReturn->creator)->name ?: '-' }}</div>
                <div class="text-muted small">Updated by</div>
                <div class="mb-2">{{ optional($saleReturn->updater)->name ?: '-' }}</div>
                <div class="text-muted small">Finalized by</div>
                <div class="mb-2">{{ optional($saleReturn->finalizer)->name ?: '-' }}</div>
                <div class="text-muted small">Cancelled by</div>
                <div>{{ optional($saleReturn->canceller)->name ?: '-' }}</div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Refund Allocations</h3></div>
            <div class="card-body">
                @forelse($saleReturn->paymentAllocations as $allocation)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ optional(optional($allocation->payment)->method)->name ?: '-' }}</div>
                        <div class="text-muted small">{{ optional(optional($allocation->payment)->paid_at)->format('d M Y H:i') ?? '-' }}</div>
                        <div>{{ $money->format((float) $allocation->amount, optional($allocation->payment)->currency_code ?: $saleReturn->currency_code) }}</div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada refund tercatat.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
