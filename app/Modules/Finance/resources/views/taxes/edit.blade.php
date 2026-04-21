@extends('layouts.admin')

@section('title', 'Edit Finance Tax')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Edit Finance Tax</h2>
            <p class="text-muted mb-0">Perbarui tarif, mapping akun pajak, dan pengaturan master pajak.</p>
        </div>
        <div class="col-auto">
            @include('shared.accounting.mode-badge')
        </div>
    </div>
</div>

@include('finance::partials.accounting-nav')

<div class="card">
    <form method="POST" action="{{ route('finance.taxes.update', $taxRate) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('finance::taxes.partials.form', [
                'taxRate' => $taxRate,
                'taxTypeOptions' => $taxTypeOptions,
                'chartOfAccountOptions' => $chartOfAccountOptions,
            ])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.taxes.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </div>
    </form>
</div>
@endsection
