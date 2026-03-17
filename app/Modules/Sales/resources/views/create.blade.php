@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Create Sales</h2>
    <div class="text-muted small">Sales menjadi core transaction penjualan. Draft boleh diedit, tetapi transaksi final harus immutable dan channel POS cukup mengirim source `pos` ke sini.</div>
</div>

@include('sales::partials.form', [
    'submitRoute' => route('sales.store'),
    'method' => 'POST',
])
@endsection
