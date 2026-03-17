@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Roles</h2>
        <div class="text-muted small">Lihat hak akses tiap role dan user yang saat ini memegang role tersebut.</div>
    </div>
    @can('roles.create')
        <a href="{{ route('roles.create') }}" class="btn btn-primary">Tambah Role</a>
    @endcan
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Hak Akses</th>
                        <th>User Yang Bisa Akses</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                <div class="text-muted small">{{ $role->users_count }} user</div>
                            </td>
                            <td>
                                @if($role->permissions->isNotEmpty())
                                    <div class="text-muted small mb-1">{{ $role->permissions->count() }} permission terpasang.</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($role->permissions->take(8) as $permission)
                                            <span class="badge bg-azure-lt text-azure">{{ $permission->name }}</span>
                                        @endforeach
                                        @if($role->permissions->count() > 8)
                                            <span class="badge bg-light text-muted">+{{ $role->permissions->count() - 8 }} lagi</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">Belum ada permission.</span>
                                @endif
                            </td>
                            <td>
                                @if($role->users->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($role->users->take(4) as $user)
                                            @can('users.update')
                                                <a href="{{ route('users.edit', $user) }}" class="badge bg-secondary-lt text-secondary text-decoration-none">{{ $user->name }}</a>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary">{{ $user->name }}</span>
                                            @endcan
                                        @endforeach
                                        @if($role->users_count > 4)
                                            <span class="badge bg-light text-muted">+{{ $role->users_count - 4 }} lagi</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">Belum ada user.</span>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    @can('roles.update')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                    @can('roles.delete')
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Hapus role ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $roles->links() }}
    </div>
</div>
@endsection
