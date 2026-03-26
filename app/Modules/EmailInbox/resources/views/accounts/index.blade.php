@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Mailbox Accounts</h2>
        <div class="text-muted small">Pisahkan mailbox operasional dari email marketing blast.</div>
    </div>
    <a href="{{ route('email-inbox.accounts.create') }}" class="btn btn-primary">Tambah Account</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Mode</th>
                    <th>Sync</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td>{{ $account->name }}</td>
                        <td>{{ $account->email_address }}</td>
                        <td>{{ strtoupper($account->direction_mode) }}</td>
                        <td>{{ $account->sync_enabled ? 'Aktif' : 'Off' }}</td>
                        <td>
                            <span class="badge bg-secondary-lt text-secondary">{{ strtoupper($account->sync_status) }}</span>
                            @if($account->last_error_message)
                                <div class="text-danger small mt-1">{{ $account->last_error_message }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-list justify-content-end">
                                <a href="{{ route('email-inbox.show', $account) }}" class="btn btn-outline-secondary btn-sm">Buka</a>
                                <a href="{{ route('email-inbox.accounts.edit', $account) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada account mailbox.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $accounts->links() }}
    </div>
</div>
@endsection
