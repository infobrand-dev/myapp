@extends('layouts.admin')

@section('title', 'Buat Transaksi')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Buat Transaksi</h2>
            <p class="text-muted mb-0">Pencatatan kas masuk dan keluar. Company: {{ $company?->name ?? '-' }}</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@include('finance::partials.accounting-nav')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('finance.transactions.store') }}" enctype="multipart/form-data">
    @csrf
    @include('finance::transactions.partials.form', [
        'transaction' => new \App\Modules\Finance\Models\FinanceTransaction(),
        'cardTitle' => 'Informasi Transaksi',
        'cancelUrl' => route('finance.transactions.index'),
        'branchHint' => null,
    ])
</form>

@endsection
