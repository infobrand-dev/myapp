@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Edit Discount</h2>
    <div class="text-muted small">Perubahan rule akan memengaruhi evaluasi berikutnya, tetapi histori transaksi tetap aman lewat usage snapshot.</div>
</div>

@include('discounts::discounts.partials.form', [
    'submitRoute' => route('discounts.update', $discount),
    'method' => 'PUT',
])
@endsection
