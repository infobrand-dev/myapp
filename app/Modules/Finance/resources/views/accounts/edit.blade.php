@extends('layouts.admin')

@section('title', 'Edit Finance Account')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Edit Finance Account</h2>
            <p class="text-muted mb-0">{{ $account->name }}</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary">
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

<form method="POST" action="{{ route('finance.accounts.update', $account) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Account</h3>
        </div>
        <div class="card-body">
            @include('finance::accounts.partials.form', [
                'account' => $account,
                'typeOptions' => $typeOptions,
                'showSlug' => true,
                'notesRows' => 4,
            ])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>
@endsection
