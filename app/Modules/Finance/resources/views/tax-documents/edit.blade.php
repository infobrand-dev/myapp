@extends('layouts.admin')

@section('title', 'Edit Tax Register')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Edit Tax Register</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.tax-documents.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</div>

@include('finance::partials.accounting-nav')

<div class="card">
    <form method="POST" action="{{ route('finance.tax-documents.update', $taxDocument) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('finance::tax-documents.partials.form', [
                'taxDocument' => $taxDocument,
                'documentTypeOptions' => $documentTypeOptions,
                'documentStatusOptions' => $documentStatusOptions,
                'taxRateOptions' => $taxRateOptions,
                'sourceOptions' => $sourceOptions,
                'defaultPeriodMonth' => (int) now()->month,
                'defaultPeriodYear' => (int) now()->year,
            ])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.tax-documents.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
