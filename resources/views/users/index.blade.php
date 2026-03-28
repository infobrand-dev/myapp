@extends('layouts.admin')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Administrasi</div>
        <h2 class="page-title">Users</h2>
    </div>
    @can('users.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="ti ti-user-plus me-1"></i>Tambah User
        </a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Access Scope</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>
                        <div class="small">
                            <div><span class="text-muted">Companies:</span> {{ $user->companies->pluck('name')->join(', ') ?: 'All active companies' }}</div>
                            <div><span class="text-muted">Branches:</span> {{ $user->branches->pluck('name')->join(', ') ?: 'Company-level access' }}</div>
                        </div>
                    </td>
                    <td class="text-end align-middle">
                        <div class="table-actions">
                            @can('users.update')
                                <a class="btn btn-icon btn-outline-secondary" href="{{ route('users.edit', $user) }}" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4z" />
                                    </svg>
                                </a>
                            @endcan
                            @can('users.delete')
                                <form class="d-inline-block m-0" method="POST" action="{{ route('users.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-icon btn-outline-danger" title="Delete" data-confirm="Hapus user {{ $user->name }}?">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 7h16" />
                                            <path d="M10 11v6" />
                                            <path d="M14 11v6" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3h6v3" />
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="ti ti-users text-muted d-block mb-2" style="font-size:2rem;"></i>
                        <div class="text-muted mb-2">Belum ada user.</div>
                        @can('users.create')
                            <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">Tambah User Pertama</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $users->links() }}
    </div>
</div>
@endsection
