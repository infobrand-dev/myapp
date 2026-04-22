@extends('layouts.admin')

@section('title', 'Sales Quotations')

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Quotations</h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">Sales</a>
            @can('sales_quotation.create')
                <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Quotation
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.quotations.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Quotation no, customer...">
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
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('sales.quotations.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Quotation</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $quotation)
                        <tr>
                            <td>
                                <a href="{{ route('sales.quotations.show', $quotation) }}" class="fw-semibold text-decoration-none">{{ $quotation->quotation_number }}</a>
                                <div class="text-muted small">{{ optional($quotation->quotation_date)->format('d M Y, H:i') ?? '-' }}</div>
                                @if($quotation->valid_until_date)
                                    <div class="text-muted small">Valid until {{ $quotation->valid_until_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td>{{ $quotation->customer_name_snapshot ?: ($quotation->contact?->name ?? '-') }}</td>
                            <td>{{ $quotation->items_count }}</td>
                            <td>{{ $money->format((float) $quotation->grand_total, $quotation->currency_code) }}</td>
                            <td><span class="badge bg-secondary-lt text-secondary">{{ ucfirst($quotation->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-icon btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                                @if($quotation->status === 'draft')
                                    <a href="{{ route('sales.quotations.edit', $quotation) }}" class="btn btn-icon btn-sm btn-outline-primary"><i class="ti ti-pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada quotation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $quotations->links() }}</div>
</div>
@endsection
