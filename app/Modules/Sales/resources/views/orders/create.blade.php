@extends('layouts.admin')

@section('title', 'Create Sales Order')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Sales</div>
            <h2 class="page-title">Create Sales Order</h2>
        </div>
    </div>
</div>

@include('sales::orders.partials.form', [
    'submitRoute' => route('sales.orders.store'),
    'method' => 'POST',
])
@endsection
