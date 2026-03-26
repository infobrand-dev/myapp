@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Buat Opening Stock</h2>
    <div class="text-muted small">Input stok awal per lokasi.</div>
</div>

@include('inventory::partials.document-form', [
    'title' => 'Opening Stock Header',
    'submitRoute' => route('inventory.openings.store'),
    'cancelRoute' => route('inventory.openings.index'),
    'products' => $products,
    'metaFields' => [
        ['name' => 'inventory_location_id', 'label' => 'Lokasi', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all()],
        ['name' => 'opening_date', 'label' => 'Tanggal', 'type' => 'date', 'value' => now()->toDateString(), 'column' => 'col-md-6'],
        ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
    ],
    'itemFields' => [
        ['name' => 'product_variant_id', 'label' => 'Variant ID', 'type' => 'number', 'value' => ''],
        ['name' => 'quantity', 'label' => 'Qty', 'type' => 'number', 'value' => '0.0000'],
        ['name' => 'minimum_quantity', 'label' => 'Min Qty', 'type' => 'number', 'value' => '0.0000'],
        ['name' => 'reorder_quantity', 'label' => 'Reorder', 'type' => 'number', 'value' => '0.0000'],
        ['name' => 'notes', 'label' => 'Catatan', 'type' => 'text', 'column' => 'col-md-3'],
    ],
])
@endsection
