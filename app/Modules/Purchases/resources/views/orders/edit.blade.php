@extends('layouts.admin')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">Edit {{ $order->order_number }}</h2>
        </div>
    </div>
</div>

@include('purchases::orders.partials.form', [
    'submitRoute' => route('purchases.orders.update', $order),
    'method' => 'PUT',
])
@endsection
