@extends('layouts.admin')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
        <div class="page-pretitle">Administrasi</div>
        <h2 class="page-title">Users</h2>
        </div>
        <div class="col-auto">
    @can('users.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="ti ti-user-plus me-1"></i>Tambah User
        </a>
    @endcan
        </div>
    </div>
</div>

<div class="card">
    @can('users.create')
        <div class="card-header">
            <div>
                <div class="card-title mb-1">Undang User</div>
                <div class="text-muted small">Registrasi self-signup tenant ditutup. Tambah anggota tim lewat undangan owner/admin.</div>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('users.invitations.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nama user">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="nama@contoh.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">Pilih role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Access</label>
                        <select name="company_ids[]" class="form-select" multiple>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch Access</label>
                        <select name="branch_ids[]" class="form-select" multiple>
                            @foreach($branchesByCompany as $companyBranches)
                                @foreach($companyBranches as $branch)
                                    <option value="{{ $branch->id }}">{{ optional($branch->company)->name ? optional($branch->company)->name . ' - ' : '' }}{{ $branch->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Kirim Undangan</button>
                </div>
            </form>
        </div>
    @endcan
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

@if($invitations->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header">
            <div>
                <div class="card-title mb-1">Undangan Pending</div>
                <div class="text-muted small">Undangan berlaku 7 hari dan wajib verifikasi email setelah aktivasi akun.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Kedaluwarsa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitations as $invitation)
                        <tr>
                            <td>{{ $invitation->email }}</td>
                            <td>{{ $invitation->role_name }}</td>
                            <td>{{ optional($invitation->created_at)->format('d M Y H:i') ?: '-' }}</td>
                            <td>{{ optional($invitation->expires_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
