@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title mb-0">Tambah User</h2>
            <div class="text-muted small mt-1">Akun, role, company access, dan branch access diatur dalam satu form.</div>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users._form')
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
