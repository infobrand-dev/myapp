@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Create Purchase</h2>
    <div class="text-muted small">Draft transaksi pembelian supplier dengan snapshot item dan supplier.</div>
</div>

@include('purchases::partials.form', [
    'submitRoute' => route('purchases.store'),
    'method' => 'POST',
])
@endsection
