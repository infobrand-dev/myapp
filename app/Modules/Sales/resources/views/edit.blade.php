@extends('layouts.admin')

@section('title', 'Edit Draft — ' . $sale->sale_number)

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Edit Draft</h2>
            <p class="text-muted mb-0">{{ $sale->sale_number }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

@include('sales::partials.form', [
    'submitRoute' => route('sales.update', $sale),
    'method'      => 'PUT',
])
@endsection
