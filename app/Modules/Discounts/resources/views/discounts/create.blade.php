@extends('layouts.tenant')

@section('title', 'Buat Discount')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Penjualan · Discounts</div>
                <h2 class="page-title">Buat Discount</h2>
                <p class="text-muted mb-0">Siapkan rule promo, target, condition, dan voucher dalam satu form.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    @include('discounts::discounts.partials.form', [
        'submitRoute' => route('discounts.store'),
        'method' => 'POST',
    ])
@endsection
