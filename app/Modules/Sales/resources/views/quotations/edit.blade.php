@extends('layouts.admin')

@section('title', 'Edit Quotation')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Edit {{ $quotation->quotation_number }}</h2>
        </div>
    </div>
</div>

@include('sales::quotations.partials.form', [
    'submitRoute' => route('sales.quotations.update', $quotation),
    'method' => 'PUT',
])
@endsection
