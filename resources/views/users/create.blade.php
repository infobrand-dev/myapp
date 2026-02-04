@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">Tambah User</h2>
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
