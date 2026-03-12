@extends('layouts.admin')

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">Edit Role</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ringkasan Akses</label>
                        <div class="form-control-plaintext text-muted">{{ $roleAccess['summary'] ?? '-' }}</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Fitur yang Umumnya Bisa Diakses</label>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(($roleAccess['items'] ?? []) as $accessItem)
                                <span class="badge bg-azure-lt text-azure">{{ $accessItem }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">User Dengan Role Ini</h3>
            </div>
            <div class="card-body">
                @if($role->users->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($role->users as $user)
                            <a href="{{ route('users.edit', $user) }}" class="list-group-item list-group-item-action px-0">
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </a>
                        @endforeach
                    </div>
                    <div class="text-muted small mt-3">Role user bisa diedit dari halaman Users.</div>
                @else
                    <div class="text-muted">Belum ada user yang memakai role ini.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
