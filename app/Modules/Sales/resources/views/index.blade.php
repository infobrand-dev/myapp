@extends('layouts.admin')

@section('title', 'Sales')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Sales Transactions</h2>
        </div>
        <div class="col-auto">
            @can('sales.create')
                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Sale
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Sale no, customer, item…">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payment</label>
                <select name="payment_status" class="form-select">
                    <option value="">All payment</option>
                    @foreach($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Source</label>
                <select name="source" class="form-select">
                    <option value="">All sources</option>
                    @foreach($sourceOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <x-contact-select
                    name="contact_id"
                    label="Customer"
                    placeholder="All customers"
                    :value="$filters['contact_id'] ?? null"
                    :show-link="false"
                />
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
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i>Filter
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-x me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Customer</th>
                        <th>Source</th>
                        <th>Items</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="fw-semibold text-decoration-none">
                                    {{ $sale->sale_number }}
                                </a>
                                <div class="text-muted small">{{ optional($sale->transaction_date)->format('d M Y, H:i') ?? '-' }}</div>
                                @if($sale->due_date)
                                    <div class="text-muted small">
                                        Due {{ $sale->due_date->format('d M Y') }}
                                        @if($sale->isOverdue())
                                            <span class="text-red fw-semibold">· Overdue</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $sale->customer_name_snapshot ?: ($sale->contact?->name ?? 'Guest / Walk-in') }}</div>
                                <div class="text-muted small">{{ $sale->customer_phone_snapshot ?: ($sale->contact ? ($sale->contact->mobile ?: $sale->contact->phone) : '-') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-blue-lt text-blue">{{ strtoupper($sale->source) }}</span>
                            </td>
                            <td>{{ $sale->items_count }}</td>
                            <td>
                                <div class="fw-medium">{{ $money->format((float) $sale->grand_total, $sale->currency_code) }}</div>
                                <div class="text-muted small">Sub: {{ $money->format((float) $sale->subtotal, $sale->currency_code) }}</div>
                                <div class="text-muted small">Due: {{ $money->format((float) $sale->balance_due, $sale->currency_code) }}</div>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match($sale->status) {
                                        'finalized' => 'bg-green-lt text-green',
                                        'draft'     => 'bg-secondary-lt text-secondary',
                                        default     => 'bg-red-lt text-red',
                                    };
                                    $payBadge = match($sale->payment_status) {
                                        'paid'    => 'bg-green-lt text-green',
                                        'partial' => 'bg-orange-lt text-orange',
                                        default   => 'bg-secondary-lt text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($sale->status) }}</span>
                                <div class="mt-1">
                                    <span class="badge {{ $payBadge }}">{{ ucfirst($sale->payment_status) }}</span>
                                </div>
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @if($sale->status === 'draft')
                                        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit draft">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Print / Invoice">
                                        <i class="ti ti-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-receipt text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">No sales transactions yet.</div>
                                @can('sales.create')
                                    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">Create First Sale</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $sales->links() }}
    </div>
</div>
@endsection
