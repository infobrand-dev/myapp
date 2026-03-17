@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Tambah Product</h2>
    <div class="text-muted small">Buat master data produk yang siap dipakai module POS lain.</div>
</div>

@include('products::partials.form', [
    'submitRoute' => route('products.store'),
    'method' => 'POST',
])
@endsection
