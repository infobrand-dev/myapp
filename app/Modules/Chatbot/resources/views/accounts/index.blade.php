@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Chatbot</h2>
        <div class="text-muted small">Konfigurasi akun AI (OpenAI) untuk auto-reply Conversations, WA API, Social DM.</div>
    </div>
    <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary">Tambah Account</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Provider</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    <tr>
                        <td>{{ $acc->name }}</td>
                        <td>{{ strtoupper($acc->provider) }}</td>
                        <td>{{ $acc->model ?? '-' }}</td>
                        <td><span class="badge {{ $acc->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $acc->status }}</span></td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('chatbot.accounts.edit', $acc) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form class="d-inline-block m-0" method="POST" action="{{ route('chatbot.accounts.destroy', $acc) }}" onsubmit="return confirm('Hapus AI account?');">
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
