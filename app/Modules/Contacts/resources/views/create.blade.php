@extends('layouts.admin')

@section('title', 'Tambah Contact')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">CRM · Contacts</div>
            <h2 class="page-title">Tambah Contact</h2>
            <p class="text-muted mb-0">Lengkapi detail company atau individual.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">
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

@include('shared.plan-limit-alert', [
    'state' => $contactLimitState,
    'title' => 'Limit Contacts',
    'showBadge' => false,
    'message' => in_array(($contactLimitState['status'] ?? 'ok'), ['at_limit', 'over_limit'], true)
        ? 'Tenant ini sudah tidak punya slot contact baru.'
        : (($contactLimitState['status'] ?? 'ok') === 'near_limit'
            ? 'Slot contact tenant ini tinggal sedikit.'
            : 'Penambahan contact baru akan mengikuti limit aktif dari plan tenant.'),
])

<form method="POST" action="{{ route('contacts.store') }}">
    @csrf
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Contact</h3>
        </div>
        <div class="card-body">
            @include('contacts::_form', ['contact' => $contact])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button class="btn btn-primary" type="submit">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>
@endsection
