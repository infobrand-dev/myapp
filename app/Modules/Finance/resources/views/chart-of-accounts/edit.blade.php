@extends('layouts.admin')

@section('title', 'Edit Chart of Accounts')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Edit Chart of Accounts</h2>
            <p class="text-muted mb-0">{{ $account->code }} - {{ $account->name }}</p>
        </div>
        <div class="col-auto">
            @include('shared.accounting.mode-badge')
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

<form method="POST" action="{{ route('finance.chart-accounts.update', $account) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Akun COA</h3>
        </div>
        <div class="card-body">
            @include('finance::chart-of-accounts.partials.form', [
                'account' => $account,
                'parentOptions' => $parentOptions,
                'typeOptions' => $typeOptions,
                'normalBalanceOptions' => $normalBalanceOptions,
                'reportSectionOptions' => $reportSectionOptions,
            ])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.chart-accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>
@endsection
