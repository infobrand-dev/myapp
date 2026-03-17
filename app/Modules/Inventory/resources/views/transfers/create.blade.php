@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Buat Stock Transfer</h2>
    <div class="text-muted small">Transfer draft dapat di-approve, dikirim, lalu diterima.</div>
</div>

@include('inventory::partials.document-form', [
    'title' => 'Transfer Header',
    'submitRoute' => route('inventory.transfers.store'),
    'cancelRoute' => route('inventory.transfers.index'),
    'products' => $products,
    'metaFields' => [
        ['name' => 'source_location_id', 'label' => 'Lokasi Asal', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all()],
        ['name' => 'destination_location_id', 'label' => 'Lokasi Tujuan', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all()],
        ['name' => 'transfer_date', 'label' => 'Tanggal', 'type' => 'date', 'value' => now()->toDateString(), 'column' => 'col-md-6'],
        ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
    ],
    'itemFields' => [
        ['name' => 'product_variant_id', 'label' => 'Variant ID', 'type' => 'number', 'value' => ''],
        ['name' => 'requested_quantity', 'label' => 'Qty', 'type' => 'number', 'value' => '0.0000'],
        ['name' => 'notes', 'label' => 'Catatan', 'type' => 'text', 'column' => 'col-md-4'],
    ],
])
@endsection
