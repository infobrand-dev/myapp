@extends('layouts.admin')

@section('title', 'Edit Transaksi')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Edit Transaksi</h2>
            <p class="text-muted mb-0">{{ $transaction->transaction_number }} &mdash; Company: {{ $company?->name ?? '-' }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.transactions.show', $transaction) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('finance.transactions.update', $transaction) }}">
    @csrf
    @method('PUT')
    @include('finance::transactions.partials.form', [
        'transaction' => $transaction,
        'cardTitle' => 'Informasi Transaksi',
        'cancelUrl' => route('finance.transactions.show', $transaction),
        'branchHint' => $branch === null
            ? null
            : 'You can move this transaction to another branch in the same company if needed.',
    ])
</form>

@endsection
