@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Draft Sales</h2>
    <div class="text-muted small">Nomor transaksi: {{ $sale->sale_number }}</div>
</div>

@include('sales::partials.form', [
    'submitRoute' => url('/sales/' . $sale->id),
    'method' => 'PUT',
])
@endsection
