@extends('layouts.admin')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="page-pretitle">Customer 360</div>
            <h2 class="page-title">{{ $contact->name }}</h2>
        </div>
        <a href="{{ route('crm.customers') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

@include('crm::partials.nav')
@include('crm::hooks.customer-360', ['contact' => $contact, 'customer360' => $customer360])
@endsection
