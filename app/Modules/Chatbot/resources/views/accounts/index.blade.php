@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Chatbot</h2>
        <div class="text-muted small">Akun AI untuk auto-reply percakapan.</div>
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
                    <th>Mode</th>
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
                            <span class="badge {{ $acc->operation_mode === 'ai_then_human' ? 'text-bg-warning' : 'text-bg-primary' }}">
                                {{ $acc->operation_mode === 'ai_then_human' ? 'AI->Human' : 'AI Only' }}
                            </span>
                        </td>
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
                                <form class="d-inline-block m-0" method="POST" action="{{ route('chatbot.accounts.destroy', $acc) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Hapus AI account?">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>
@endsection
