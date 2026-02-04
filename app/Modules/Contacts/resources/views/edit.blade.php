@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Edit Contact</h2>
        <div class="text-muted small">Perbarui detail contact.</div>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('contacts.update', $contact) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-body">
            @include('contacts::_form', ['contact' => $contact, 'companies' => $companies])
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </div>
    </div>
</form>
@endsection
