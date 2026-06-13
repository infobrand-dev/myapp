@extends('layouts.tenant')

@section('content')
<div class="page-header mb-4">
    <div>
        <div class="page-pretitle">CRM</div>
        <h2 class="page-title">Customers</h2>
    </div>
</div>

@include('crm::partials.nav')

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari nama, email, atau phone">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($customers as $customer)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-semibold">{{ $customer->name }}</div>
                    <div class="small text-muted">{{ $customer->email ?: ($customer->mobile ?: $customer->phone ?: 'Tanpa kontak utama') }}</div>
                    <div class="row g-2 mt-2">
                        <div class="col-6"><div class="border rounded p-2 small"><div class="text-muted">Open Deals</div><div class="fw-semibold">{{ $customer->crm_open_deals_count }}</div></div></div>
                        <div class="col-6"><div class="border rounded p-2 small"><div class="text-muted">Pending Follow-Up</div><div class="fw-semibold">{{ $customer->crm_pending_follow_ups_count }}</div></div></div>
                    </div>
                    <a href="{{ route('crm.customers.show', $customer) }}" class="btn btn-outline-primary w-100 mt-3">Open Customer 360</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Belum ada customer yang bisa ditampilkan.</div></div></div>
    @endforelse
</div>

@if($customers->hasPages())
    <div class="mt-4">{{ $customers->links() }}</div>
@endif
@endsection

