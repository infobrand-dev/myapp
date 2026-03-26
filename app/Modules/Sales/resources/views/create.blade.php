@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Create Sales</h2>
    <div class="text-muted small">Buat transaksi penjualan baru.</div>
</div>

@include('sales::partials.form', [
    'submitRoute' => route('sales.store'),
    'method' => 'POST',
])
@endsection
