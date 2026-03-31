@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Chatbot</h2>
        <div class="text-muted small">Auto-reply AI untuk WhatsApp dan social inbox, dengan handoff aman ke tim saat perlu.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('chatbot.playground.index') }}" class="btn btn-outline-secondary">Playground</a>
        <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary">Tambah Chatbot</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Balasan bot</div><div class="h2 mb-0">{{ $decisionStats['reply_sent'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Diteruskan ke tim</div><div class="h2 mb-0">{{ $decisionStats['handoff'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">No context</div><div class="h2 mb-0">{{ $decisionStats['no_context'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Error AI</div><div class="h2 mb-0">{{ $decisionStats['error'] ?? 0 }}</div></div></div></div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Automation</th>
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
                        <td>
                            <span class="badge text-bg-dark">
                                {{ match($acc->automation_mode ?? 'ai_first') {
                                    'rule_only' => 'Rule Only',
                                    'ai_assisted' => 'AI Assisted',
                                    default => 'AI First',
                                } }}
                            </span>
                        </td>
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
                    <tr><td colspan="9" class="text-muted">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links() }}</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Keputusan Bot Terbaru</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Chatbot</th>
                            <th>Channel</th>
                            <th>Aksi</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($decisionLogs as $log)
                            <tr>
                                <td>{{ optional($log->created_at)->format('d M H:i') }}</td>
                                <td>{{ optional($log->chatbotAccount)->name ?? '-' }}</td>
                                <td>{{ strtoupper((string) ($log->channel ?? '-')) }}</td>
                                <td><span class="badge text-bg-light">{{ $log->action }}</span></td>
                                <td>{{ $log->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Belum ada log keputusan bot.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Antrian Butuh Tim</h3></div>
            <div class="list-group list-group-flush">
                @forelse($escalationQueue as $log)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ optional($log->chatbotAccount)->name ?? 'Chatbot' }}</div>
                        <div class="text-muted small">{{ strtoupper((string) ($log->channel ?? '-')) }} · {{ $log->reason ?? '-' }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada percakapan yang perlu eskalasi.</div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Knowledge Paling Sering Dipakai</h3></div>
            <div class="list-group list-group-flush">
                @forelse($topKnowledgeDocuments as $document)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $document->title }}</div>
                        <div class="text-muted small">{{ data_get($document->metadata, 'category', 'General') }} · {{ strtoupper((string) data_get($document->metadata, 'language', 'id')) }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada dokumen yang sering dipakai bot.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
