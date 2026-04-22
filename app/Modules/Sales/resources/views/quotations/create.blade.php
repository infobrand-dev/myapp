@extends('layouts.admin')

@section('title', 'Create Quotation')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Create Quotation</h2>
        </div>
    </div>
</div>

@include('sales::quotations.partials.form', [
    'submitRoute' => route('sales.quotations.store'),
    'method' => 'POST',
])
@endsection
