@extends('layouts.tenant')

@section('title', 'Edit Discount')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Penjualan · Discounts</div>
                <h2 class="page-title">Edit Discount</h2>
                <p class="text-muted mb-0">{{ $discount->internal_name }}</p>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <a href="{{ route('discounts.show', $discount) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-eye me-1"></i>Detail
                </a>
                <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    @include('discounts::discounts.partials.form', [
        'submitRoute' => route('discounts.update', $discount),
        'method' => 'PUT',
    ])
@endsection
