@extends('layouts.admin')

@section('title', 'Edit Purchase')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Pembelian</div>
            <h2 class="page-title">Edit Draft {{ $purchase->purchase_number }}</h2>
            <p class="text-muted mb-0">Edit draft pembelian.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@include('purchases::partials.form', [
    'submitRoute' => route('purchases.update', $purchase),
    'method' => 'PUT',
])
@endsection
