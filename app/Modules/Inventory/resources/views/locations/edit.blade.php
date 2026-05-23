@extends('layouts.admin')

@section('title', 'Edit Inventory Location')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Edit Inventory Location</h2>
            <p class="text-muted mb-0">Perbarui struktur dan status lokasi stok aktif.</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('inventory.locations.update', $location) }}">
    @include('inventory::locations.partials.form', ['method' => 'PUT'])
</form>
@endsection
