@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">Create Purchase Order</h2>
        </div>
    </div>
</div>

@include('purchases::orders.partials.form', [
    'submitRoute' => route('purchases.orders.store'),
    'method' => 'POST',
])
@endsection
