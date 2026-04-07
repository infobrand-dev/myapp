@extends('layouts.admin')

@section('title', 'Buat Opening Stock')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Opening Stock</div>
            <h2 class="page-title">Buat Opening Stock</h2>
            <p class="text-muted mb-0">Input stok awal per lokasi.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.openings.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
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
