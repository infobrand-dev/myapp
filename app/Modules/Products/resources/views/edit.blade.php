@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Product</h2>
    <div class="text-muted small">Perbarui detail, harga, stok, dan varian produk.</div>
</div>

@include('products::partials.form', [
    'submitRoute' => route('products.update', $product),
    'method' => 'PUT',
])
@endsection
