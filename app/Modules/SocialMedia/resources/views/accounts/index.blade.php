@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Social Accounts</h2>
        <div class="text-muted small">Kelola akun Instagram/Facebook untuk DM.</div>
    </div>
    <a href="{{ route('social-media.accounts.create') }}" class="btn btn-primary">Tambah Akun</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Nama</th>
                    <th>ID</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    <tr>
                        <td>{{ strtoupper($acc->platform) }}</td>
                        <td>{{ $acc->name ?? '—' }}</td>
                        <td>{{ $acc->platform === 'instagram' ? $acc->ig_business_id : $acc->page_id }}</td>
                        <td><span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'secondary' }}">{{ $acc->status }}</span></td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                <a href="{{ route('social-media.accounts.edit', $acc) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('social-media.accounts.destroy', $acc) }}" onsubmit="return confirm('Hapus akun?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>
@endsection
