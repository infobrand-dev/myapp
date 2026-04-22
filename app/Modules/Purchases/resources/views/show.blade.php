@extends('layouts.admin')

@section('title', 'Detail Purchase')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
    $isOverdue = $purchase->isOverdue();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Pembelian</div>
            <h2 class="page-title">{{ $purchase->purchase_number }}</h2>
            <p class="text-muted mb-0">
                {{ optional($purchase->purchase_date)->format('d M Y H:i') ?? '-' }} | Supplier: {{ $purchase->supplier_name_snapshot ?: '-' }}
                @if($isOverdue)
                    | <span class="text-red fw-semibold">Overdue</span>
                @endif
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            @if($purchase->status === 'draft')
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-pencil me-1"></i>Edit Draft
                </a>
                <form method="POST" action="{{ route('purchases.finalize', $purchase) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Finalize
                    </button>
                </form>
            @endif
            @if(in_array($purchase->status, ['confirmed', 'partial_received']))
                <a href="{{ route('purchases.receive', $purchase) }}" class="btn btn-outline-success">
                    <i class="ti ti-package me-1"></i>Receive Goods
                </a>
            @endif
            <a href="{{ route('purchases.print', $purchase) }}" class="btn btn-outline-secondary" title="Print">
                <i class="ti ti-printer me-1"></i>Print
            </a>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Purchase Items</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Item</th><th>Qty</th><th>Received</th><th>Cost</th><th>Total</th></tr></thead>
                        <tbody>
                        @foreach($purchase->items as $item)
                            <tr>
                                <td><div class="fw-semibold">{{ $item->product_name_snapshot }}</div><div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }} | SKU: {{ $item->sku_snapshot ?: '-' }}</div></td>
                                <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                                <td>{{ $money->format((float) $item->unit_cost, $purchase->currency_code) }}</td>
                                <td>{{ $money->format((float) $item->line_total, $purchase->currency_code) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Goods Receipts</h3></div>
            <div class="card-body">
                @forelse($purchase->receipts as $receipt)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <div><div class="fw-semibold">{{ $receipt->receipt_number }}</div><div class="text-muted small">{{ optional($receipt->receipt_date)->format('d M Y H:i') ?? '-' }}</div></div>
                            <div class="text-end">
                                <div>{{ optional($receipt->inventoryLocation)->name ?: '-' }}</div>
                                <div class="text-muted small">Qty: {{ number_format((float) $receipt->total_received_qty, 2, ',', '.') }}</div>
                                @if($receipt->inventoryJournal)
                                    <div class="text-muted small">
                                        Journal:
                                        <a href="{{ route('finance.journals.show', $receipt->inventoryJournal) }}">
                                            {{ $receipt->inventoryJournal->journal_number }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada receiving.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-3"><div class="text-muted small">Supplier</div><div>{{ $purchase->supplier_name_snapshot ?: '-' }}</div><div class="text-muted small">{{ $purchase->supplier_phone_snapshot ?: '-' }}</div></div>
                <div class="mb-3"><div class="text-muted small">Status</div><div>{{ $statusOptions[$purchase->status] ?? ucfirst($purchase->status) }}</div><div class="text-muted small">Payment: {{ $paymentStatusOptions[$purchase->payment_status] ?? ucfirst($purchase->payment_status) }}</div></div>
                @if($purchase->due_date)
                    <div class="mb-3">
                        <div class="text-muted small">Payable</div>
                        <div>Due Date: {{ $purchase->due_date->format('d M Y') }}</div>
                        <div class="text-muted small {{ $isOverdue ? 'text-red' : '' }}">
                            {{ $isOverdue ? 'Overdue' : ((float) $purchase->balance_due > 0 ? 'Open payable' : 'Settled') }}
                        </div>
                    </div>
                @endif
                @if($purchase->expected_receive_date)
                    <div class="mb-3">
                        <div class="text-muted small">Expected Receive</div>
                        <div>{{ $purchase->expected_receive_date->format('d M Y') }}</div>
                    </div>
                @endif
                @if($isAdvancedMode)
                    <div class="mb-3"><div class="text-muted small">Supplier Ref</div><div>{{ $purchase->supplier_reference ?: '-' }}</div><div class="text-muted small">Invoice: {{ $purchase->supplier_invoice_number ?: '-' }}</div></div>
                    <div class="mb-3">
                        <div class="text-muted small">Supplier Bill Tracking</div>
                        <div>{{ $supplierBillStatusOptions[$purchase->supplier_bill_status] ?? ucfirst((string) $purchase->supplier_bill_status) }}</div>
                        <div class="text-muted small">Received at: {{ optional($purchase->supplier_bill_received_at)->format('d M Y') ?: '-' }}</div>
                    </div>
                @endif
                <div class="mb-3"><div class="text-muted small">Totals</div><div>Subtotal: {{ $money->format((float) $purchase->subtotal, $purchase->currency_code) }}</div><div>Discount: {{ $money->format((float) $purchase->discount_total, $purchase->currency_code) }}</div><div>Tax: {{ $money->format((float) $purchase->tax_total, $purchase->currency_code) }}</div><div>Landed Cost: {{ $money->format((float) $purchase->landed_cost_total, $purchase->currency_code) }}</div><div class="fw-semibold">Grand: {{ $money->format((float) $purchase->grand_total, $purchase->currency_code) }}</div><div>Paid: {{ $money->format((float) $purchase->paid_total, $purchase->currency_code) }}</div><div>Balance: {{ $money->format((float) $purchase->balance_due, $purchase->currency_code) }}</div></div>
                <div class="mb-3"><div class="text-muted small">Notes</div><div>{{ $purchase->notes ?: '-' }}</div></div>

                @if($purchase->status === 'draft')
                    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" class="mb-3">@csrf<label class="form-label">Cancel Reason</label><textarea name="reason" class="form-control" rows="2"></textarea><button type="submit" class="btn btn-outline-warning w-100 mt-2">Cancel Draft</button></form>
                @endif
                @if(in_array($purchase->status, ['confirmed', 'partial_received', 'received']))
                    <form method="POST" action="{{ route('purchases.void', $purchase) }}">@csrf<label class="form-label">Void Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea><button type="submit" class="btn btn-outline-danger w-100 mt-2">Void Purchase</button></form>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="card-body">
                @if($purchase->paymentAllocations->isNotEmpty())
                    @foreach($purchase->paymentAllocations as $allocation)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ optional(optional($allocation->payment)->method)->name ?: '-' }}</div>
                            <div class="text-muted small">{{ optional(optional($allocation->payment)->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                            <div>Allocated: {{ $money->format((float) $allocation->amount, optional($allocation->payment)->currency_code ?: $purchase->currency_code) }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted mb-3">Belum ada payment tercatat.</div>
                @endif
                @if($purchase->status !== 'draft' && Route::has('payments.create'))
                    <a href="{{ route('payments.create', ['purchase_id' => $purchase->id]) }}" class="btn btn-outline-primary w-100">Tambah Pembayaran</a>
                @endif
            </div>
        </div>

        @include('shared.accounting.audit-summary', [
            'cardClass' => 'mt-3',
            'entries' => [
                ['label' => 'Dibuat oleh', 'user' => $purchase->creator, 'timestamp' => $purchase->created_at, 'icon' => 'ti-user-plus', 'color' => 'green'],
                ['label' => 'Diubah terakhir', 'user' => $purchase->updater, 'timestamp' => $purchase->updated_at, 'icon' => 'ti-user-edit', 'color' => 'blue'],
                ['label' => 'Confirmed oleh', 'user' => $purchase->confirmer, 'timestamp' => $purchase->confirmed_at, 'icon' => 'ti-check', 'color' => 'green'],
                ['label' => 'Void oleh', 'user' => $purchase->voider, 'timestamp' => $purchase->voided_at, 'icon' => 'ti-ban', 'color' => 'red'],
                ['label' => 'Cancelled oleh', 'user' => $purchase->canceller, 'timestamp' => $purchase->cancelled_at, 'icon' => 'ti-x', 'color' => 'orange'],
            ],
        ])
    </div>
</div>
@include('shared.accounting.activity-log', [
    'activities' => $activities,
    'fieldLabels' => [
        'contact_id' => 'Supplier',
        'supplier_reference' => 'Supplier reference',
        'supplier_invoice_number' => 'Invoice number',
        'supplier_notes' => 'Supplier notes',
        'status' => 'Status',
        'payment_status' => 'Payment status',
        'purchase_date' => 'Purchase date',
        'due_date' => 'Due date',
        'expected_receive_date' => 'Expected receive date',
        'subtotal' => 'Subtotal',
        'discount_total' => 'Discount',
        'tax_total' => 'Tax',
        'landed_cost_total' => 'Landed cost',
        'grand_total' => 'Grand total',
        'paid_total' => 'Paid',
        'balance_due' => 'Balance due',
        'supplier_bill_status' => 'Supplier bill status',
        'supplier_bill_received_at' => 'Bill received date',
        'currency_code' => 'Currency',
        'notes' => 'Notes',
        'internal_notes' => 'Internal notes',
        'branch_id' => 'Branch',
    ],
    'money' => $money,
    'currency' => $purchase->currency_code,
])
@endsection
