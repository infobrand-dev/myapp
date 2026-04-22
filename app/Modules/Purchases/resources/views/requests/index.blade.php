@extends('layouts.admin')

@section('title', 'Purchase Requests')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">Purchase Requests</h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">Purchase Orders</a>
            @can('purchase_request.create')
                <a href="{{ route('purchases.requests.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Purchase Request
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchases.requests.index') }}" class="row g-3">
            <div class="col-md-3"><label class="form-label">Search</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="PR no, supplier..."></div>
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><x-contact-select name="contact_id" label="Supplier" placeholder="All suppliers" :value="$filters['contact_id'] ?? null" :show-link="false" /></div>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ route('purchases.requests.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestModel)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.requests.show', $requestModel) }}" class="fw-semibold text-decoration-none">{{ $requestModel->request_number }}</a>
                                <div class="text-muted small">{{ optional($requestModel->request_date)->format('d M Y, H:i') ?? '-' }}</div>
                                @if($requestModel->needed_by_date)
                                    <div class="text-muted small">Needed by {{ $requestModel->needed_by_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td>{{ $requestModel->supplier_name_snapshot ?: ($requestModel->supplier ? $requestModel->supplier->name : '-') }}</td>
                            <td>{{ $requestModel->items_count }}</td>
                            <td>{{ $money->format((float) $requestModel->grand_total, $requestModel->currency_code) }}</td>
                            <td><span class="badge bg-secondary-lt text-secondary">{{ ucfirst($requestModel->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('purchases.requests.show', $requestModel) }}" class="btn btn-icon btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                                @if($requestModel->status === 'draft')
                                    <a href="{{ route('purchases.requests.edit', $requestModel) }}" class="btn btn-icon btn-sm btn-outline-primary"><i class="ti ti-pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada purchase request.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $requests->links() }}</div>
</div>
@endsection
