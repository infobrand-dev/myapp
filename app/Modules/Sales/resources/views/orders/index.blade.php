@extends('layouts.admin')

@section('title', 'Sales Orders')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Sales Orders</h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Sales</a>
            @can('sales_order.create')
                <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Sales Order
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.orders.index') }}" class="row g-3">
            <div class="col-md-3"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="SO no, customer..."></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><x-contact-select name="contact_id" label="Customer" placeholder="All customers" :value="$filters['contact_id'] ?? null" :show-link="false" /></div>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary">Reset</a></div>
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
                        <th>Customer</th>
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
                                <a href="{{ route('sales.orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                <div class="text-muted small">{{ optional($order->order_date)->format('d M Y, H:i') ?? '-' }}</div>
                                @if($order->expected_delivery_date)
                                    <div class="text-muted small">Delivery {{ $order->expected_delivery_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td>{{ $order->customer_name_snapshot ?: ($order->contact?->name ?? '-') }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td>{{ $money->format((float) $order->grand_total, $order->currency_code) }}</td>
                            <td><span class="badge bg-secondary-lt text-secondary">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('sales.orders.show', $order) }}" class="btn btn-icon btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                                @if($order->status === 'draft')
                                    <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-icon btn-sm btn-outline-primary"><i class="ti ti-pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada sales order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $orders->links() }}</div>
</div>
@endsection
