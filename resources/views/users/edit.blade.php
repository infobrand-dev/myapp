@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title mb-0">Edit User</h2>
            <div class="text-muted small mt-1">Perubahan role dan access scope tetap disimpan dari form user yang sama.</div>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form', ['user' => $user, 'currentRole' => $currentRole ?? null])
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
