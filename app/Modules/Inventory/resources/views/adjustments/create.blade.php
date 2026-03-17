@extends('layouts.admin')

@section('content')
<div class="mb-3">
    <h2 class="mb-0">Buat Stock Adjustment</h2>
    <div class="text-muted small">Alasan adjustment wajib tersimpan untuk audit.</div>
</div>

@include('inventory::partials.document-form', [
    'title' => 'Adjustment Header',
    'submitRoute' => route('inventory.adjustments.store'),
    'cancelRoute' => route('inventory.adjustments.index'),
    'products' => $products,
    'metaFields' => [
        ['name' => 'inventory_location_id', 'label' => 'Lokasi', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all()],
        ['name' => 'adjustment_date', 'label' => 'Tanggal', 'type' => 'date', 'value' => now()->toDateString(), 'column' => 'col-md-6'],
        ['name' => 'reason_code', 'label' => 'Reason Code', 'type' => 'text', 'value' => 'manual_correction', 'column' => 'col-md-6'],
        ['name' => 'reason_text', 'label' => 'Reason', 'type' => 'textarea'],
    ],
    'itemFields' => [
        ['name' => 'product_variant_id', 'label' => 'Variant ID', 'type' => 'number', 'value' => ''],
        ['name' => 'direction', 'label' => 'Arah', 'type' => 'select', 'options' => ['in' => 'Tambah', 'out' => 'Kurang']],
        ['name' => 'quantity', 'label' => 'Qty', 'type' => 'number', 'value' => '0.0000'],
        ['name' => 'movement_type', 'label' => 'Type', 'type' => 'text', 'value' => 'stock_adjustment'],
        ['name' => 'notes', 'label' => 'Catatan', 'type' => 'text', 'column' => 'col-md-2'],
    ],
])
@endsection
