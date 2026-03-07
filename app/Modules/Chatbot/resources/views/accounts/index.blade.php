@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Chatbot</h2>
        <div class="text-muted small">Konfigurasi akun AI (OpenAI) untuk auto-reply Conversations, WA API, Social DM.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('chatbot.playground.index') }}" class="btn btn-outline-secondary">Playground</a>
        <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary">Tambah Account</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Provider</th>
                    <th>Model</th>
                    <th>RAG</th>
                    <th>Mirror</th>
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
                        <td>
                            <span class="badge {{ $acc->rag_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $acc->rag_enabled ? 'ON' : 'OFF' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $acc->mirror_to_conversations ? 'text-bg-info' : 'text-bg-secondary' }}">
                                {{ $acc->mirror_to_conversations ? 'ON' : 'OFF' }}
                            </span>
                        </td>
                        <td><span class="badge {{ $acc->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $acc->status }}</span></td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('chatbot.knowledge.index', $acc) }}" class="btn btn-outline-primary btn-sm">Knowledge</a>
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
                    <tr><td colspan="7" class="text-muted">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>
@endsection
