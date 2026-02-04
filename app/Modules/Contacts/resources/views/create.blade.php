@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Tambah Contact</h2>
        <div class="text-muted small">Lengkapi detail company atau individual.</div>
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

<form method="POST" action="{{ route('contacts.store') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            @include('contacts::_form', ['contact' => null, 'companies' => $companies])
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </div>
</form>
@endsection
