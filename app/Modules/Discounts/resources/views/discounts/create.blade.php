@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Buat Discount</h2>
    <div class="text-muted small">Buat promo, voucher, atau aturan diskon baru.</div>
</div>

@include('discounts::discounts.partials.form', [
    'submitRoute' => route('discounts.store'),
    'method' => 'POST',
])
@endsection
