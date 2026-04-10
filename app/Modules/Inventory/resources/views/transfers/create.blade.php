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
        ['name' => 'source_location_id', 'label' => 'Source Location', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all(), 'required' => true, 'tooltip' => 'Lokasi asal tempat stok akan dikurangi sebelum dipindahkan ke lokasi tujuan.'],
        ['name' => 'destination_location_id', 'label' => 'Destination Location', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all(), 'required' => true, 'tooltip' => 'Lokasi tujuan penerimaan stok transfer. Pastikan berbeda dari lokasi asal.'],
        ['name' => 'transfer_date', 'label' => 'Transfer Date', 'type' => 'date', 'value' => now()->toDateString(), 'column' => 'col-md-6', 'required' => true, 'tooltip' => 'Tanggal saat dokumen transfer dibuat atau mulai berlaku.'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'tooltip' => 'Catatan tambahan untuk transfer stok ini, misalnya alasan pemindahan atau instruksi penerimaan.'],
    ],
    'itemFields' => [
        ['name' => 'product_variant_id', 'label' => 'Variant ID', 'type' => 'number', 'value' => '', 'tooltip' => 'Isi jika transfer ditujukan untuk varian tertentu. Boleh dikosongkan untuk produk utama.'],
        ['name' => 'requested_quantity', 'label' => 'Transfer Qty', 'type' => 'number', 'value' => '0.0000', 'required' => true, 'tooltip' => 'Jumlah stok yang diminta untuk dipindahkan dari lokasi asal ke lokasi tujuan.'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'column' => 'col-md-4', 'tooltip' => 'Catatan item transfer, misalnya kondisi barang atau instruksi khusus.'],
    ],
])
@endsection
