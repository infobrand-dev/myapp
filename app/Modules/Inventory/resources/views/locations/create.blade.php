@extends('layouts.admin')

@section('title', 'Buat Inventory Location')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Buat Inventory Location</h2>
            <p class="text-muted mb-0">Tambahkan gudang atau lokasi stok baru dalam scope aktif.</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('inventory.locations.store') }}">
    @include('inventory::locations.partials.form', ['method' => 'POST'])
</form>
@endsection
