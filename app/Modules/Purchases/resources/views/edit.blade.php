@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Draft {{ $purchase->purchase_number }}</h2>
    <div class="text-muted small">Edit draft pembelian.</div>
</div>

@include('purchases::partials.form', [
    'submitRoute' => route('purchases.update', $purchase),
    'method' => 'PUT',
])
@endsection
