@extends('layouts.admin')

@section('title', 'Edit ' . $contact->name)

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM · Contacts</div>
            <h2 class="page-title">Edit Contact</h2>
            <p class="text-muted mb-0">{{ $contact->name }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('contacts.show', $contact) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible mb-3">
        <i class="ti ti-alert-circle me-2"></i>
        <strong>Periksa kembali isian form:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('contacts.update', $contact) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Contact</h3>
        </div>
        <div class="card-body">
            @include('contacts::_form', ['contact' => $contact, 'companies' => $companies])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('contacts.show', $contact) }}" class="btn btn-outline-secondary">Batal</a>
            <button class="btn btn-primary" type="submit">
                <i class="ti ti-device-floppy me-1"></i>Simpan Perubahan
            </button>
        </div>
    </div>
</form>
@endsection
