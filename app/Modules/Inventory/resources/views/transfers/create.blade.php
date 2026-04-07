@extends('layouts.admin')

@section('title', 'Buat Stock Transfer')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock Transfer</div>
            <h2 class="page-title">Buat Stock Transfer</h2>
            <p class="text-muted mb-0">Transfer draft dapat di-approve, dikirim, lalu diterima.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
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
