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
        ['name' => 'inventory_location_id', 'label' => 'Location', 'type' => 'select', 'options' => $locations->pluck('name', 'id')->all(), 'required' => true, 'tooltip' => 'Lokasi penyimpanan stok awal yang akan dibuat. Pilih lokasi yang benar agar saldo awal tidak masuk ke gudang yang salah.'],
        ['name' => 'opening_date', 'label' => 'Opening Date', 'type' => 'date', 'value' => now()->toDateString(), 'column' => 'col-md-6', 'required' => true, 'tooltip' => 'Tanggal saat saldo awal stok mulai dianggap berlaku. Biasanya diisi tanggal mulai penggunaan sistem.'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'tooltip' => 'Catatan tambahan untuk dokumen opening stock. Boleh dikosongkan jika tidak ada keterangan khusus.'],
    ],
    'itemFields' => [
        ['name' => 'product_variant_id', 'label' => 'Variant ID', 'type' => 'number', 'value' => '', 'tooltip' => 'Isi jika stok awal ini khusus untuk varian tertentu. Boleh dikosongkan untuk produk utama tanpa varian.'],
        ['name' => 'quantity', 'label' => 'Opening Stock', 'type' => 'number', 'value' => '0.0000', 'required' => true, 'tooltip' => 'Jumlah stok awal yang tersedia di lokasi ini saat mulai menggunakan sistem.'],
        ['name' => 'minimum_quantity', 'label' => 'Minimum Stock', 'type' => 'number', 'value' => '0.0000', 'tooltip' => 'Batas minimum stok yang masih dianggap aman. Digunakan untuk membantu pemantauan stok, bukan menambah stok otomatis.'],
        ['name' => 'reorder_quantity', 'label' => 'Reorder Point', 'type' => 'number', 'value' => '0.0000', 'tooltip' => 'Jumlah yang disarankan untuk dibeli ulang saat stok mulai menipis. Boleh diisi 0 jika belum memakai aturan reorder.'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'column' => 'col-md-3', 'tooltip' => 'Catatan tambahan untuk item opening stock ini.'],
    ],
])
@endsection
