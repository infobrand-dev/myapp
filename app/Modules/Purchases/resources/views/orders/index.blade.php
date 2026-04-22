@extends('layouts.admin')

@section('title', 'Purchase Orders')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">Purchase Orders</h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Purchases</a>
            @can('purchase_order.create')
                <a href="{{ route('purchases.orders.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Purchase Order
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchases.orders.index') }}" class="row g-3">
            <div class="col-md-3"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="PO no, supplier..."></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><x-contact-select name="contact_id" label="Supplier" placeholder="All suppliers" :value="$filters['contact_id'] ?? null" :show-link="false" /></div>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                <div class="text-muted small">{{ optional($order->order_date)->format('d M Y, H:i') ?? '-' }}</div>
                                @if($order->expected_receive_date)
                                    <div class="text-muted small">ETA {{ $order->expected_receive_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td>{{ $order->supplier_name_snapshot ?: ($order->supplier?->name ?? '-') }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td>{{ $money->format((float) $order->grand_total, $order->currency_code) }}</td>
                            <td><span class="badge bg-secondary-lt text-secondary">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('purchases.orders.show', $order) }}" class="btn btn-icon btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                                @if($order->status === 'draft')
                                    <a href="{{ route('purchases.orders.edit', $order) }}" class="btn btn-icon btn-sm btn-outline-primary"><i class="ti ti-pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada purchase order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $orders->links() }}</div>
</div>
@endsection
