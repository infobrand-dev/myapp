@extends('layouts.admin')

@section('title', 'Chatbot')

@section('content')

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Modul</div>
        <h2 class="page-title">Chatbot</h2>
        <div class="text-muted small mt-1">Auto-reply AI untuk WhatsApp dan social inbox, dengan handoff aman ke tim saat perlu.</div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('chatbot.playground.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-player-play me-1"></i>Playground
        </a>
        <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>Tambah Chatbot
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Balasan Bot</div>
                    <span class="text-green"><i class="ti ti-message-check" style="font-size:1.3rem;"></i></span>
                </div>
                <div class="fs-1 fw-bold">{{ $decisionStats['reply_sent'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Bot menjawab otomatis</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Diteruskan ke Tim</div>
                    <span class="text-blue"><i class="ti ti-users" style="font-size:1.3rem;"></i></span>
                </div>
                <div class="fs-1 fw-bold">{{ $decisionStats['handoff'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Handoff ke manusia</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Tanpa Konteks</div>
                    <span class="text-orange"><i class="ti ti-help" style="font-size:1.3rem;"></i></span>
                </div>
                <div class="fs-1 fw-bold">{{ $decisionStats['no_context'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Tidak ada referensi ditemukan</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-secondary text-uppercase small fw-bold">Error AI</div>
                    <span class="text-red"><i class="ti ti-alert-triangle" style="font-size:1.3rem;"></i></span>
                </div>
                <div class="fs-1 fw-bold">{{ $decisionStats['error'] ?? 0 }}</div>
                <div class="text-muted small mt-1">Gagal diproses AI</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Akun Chatbot --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Mode Bot</th>
                        <th>Label</th>
                        <th>Provider</th>
                        <th>Model</th>
                        <th>Batas Bot</th>
                        <th>Dokumen AI</th>
                        <th>Rekam Inbox</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        <tr>
                            <td class="fw-semibold">{{ $acc->name }}</td>
                            <td>
                                @php
                                    $mode = method_exists($acc, 'behaviorMode') ? $acc->behaviorMode() : ($acc->automation_mode ?? 'ai_first');
                                @endphp
                                @if($mode === 'rule_only')
                                    <span class="badge bg-secondary-lt text-secondary">Rule Only</span>
                                @elseif($mode === 'ai_then_human')
                                    <span class="badge bg-indigo-lt text-indigo">AI Then Human</span>
                                @else
                                    <span class="badge bg-azure-lt text-azure">AI Only</span>
                                @endif
                            </td>
                            <td>
                                @if(method_exists($acc, 'isPrivate') && $acc->isPrivate())
                                    <span class="badge bg-dark-lt text-dark">Private</span>
                                @else
                                    <span class="badge bg-green-lt text-green">Public</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ match($acc->provider ?? 'openai') {
                                    'openai' => 'OpenAI',
                                    'anthropic' => 'Anthropic',
                                    'groq' => 'Groq',
                                    default => strtoupper($acc->provider ?? '-')
                                } }}
                            </td>
                            <td class="text-muted small">{{ $acc->model ?? '-' }}</td>
                            <td>
                                @php $maxReplies = method_exists($acc, 'maxBotRepliesPerConversation') ? $acc->maxBotRepliesPerConversation() : 0; @endphp
                                @if($maxReplies > 0)
                                    <span class="badge bg-orange-lt text-orange">Maks {{ $maxReplies }}x</span>
                                @else
                                    <span class="badge bg-azure-lt text-azure">Tanpa batas</span>
                                @endif
                            </td>
                            <td>
                                @if($acc->rag_enabled)
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($acc->mirror_to_conversations)
                                    <span class="badge bg-cyan-lt text-cyan">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($acc->status === 'active')
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('chatbot.knowledge.index', $acc) }}"
                                       class="btn btn-icon btn-sm btn-outline-primary"
                                       title="Kelola Dokumen Knowledge">
                                        <i class="ti ti-books"></i>
                                    </a>
                                    <a href="{{ route('chatbot.accounts.edit', $acc) }}"
                                       class="btn btn-icon btn-sm btn-outline-secondary"
                                       title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('chatbot.accounts.destroy', $acc) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-icon btn-sm btn-outline-danger"
                                                title="Hapus"
                                                data-confirm="Hapus chatbot {{ $acc->name }}?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="ti ti-robot text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada chatbot yang dibuat.</div>
                                <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-sm btn-primary">Tambah Chatbot Pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $accounts->links() }}
    </div>
</div>

{{-- Panel bawah --}}
<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Keputusan Bot Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
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
                                    <td class="text-muted small text-nowrap">{{ optional($log->created_at)->format('d M H:i') }}</td>
                                    <td>{{ optional($log->chatbotAccount)->name ?? '-' }}</td>
                                    <td><span class="badge bg-secondary-lt text-secondary">{{ strtoupper((string) ($log->channel ?? '-')) }}</span></td>
                                    <td><span class="badge bg-azure-lt text-azure">{{ $log->action }}</span></td>
                                    <td class="text-muted small">{{ $log->reason ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="ti ti-list-check d-block mb-1" style="font-size:1.5rem;"></i>
                                        Belum ada log keputusan bot.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Antrian Butuh Tim</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($escalationQueue as $log)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ optional($log->chatbotAccount)->name ?? 'Chatbot' }}</div>
                        <div class="text-muted small">{{ strtoupper((string) ($log->channel ?? '-')) }} · {{ $log->reason ?? '-' }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-center py-3">
                        <i class="ti ti-checks text-muted d-block mb-1" style="font-size:1.3rem;"></i>
                        <span class="text-muted small">Tidak ada percakapan yang perlu eskalasi.</span>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Knowledge Paling Sering Dipakai</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($topKnowledgeDocuments as $document)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $document->title }}</div>
                        <div class="text-muted small">
                            {{ data_get($document->metadata, 'category', 'General') }}
                            · {{ strtoupper((string) data_get($document->metadata, 'language', 'id')) }}
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center py-3">
                        <i class="ti ti-books text-muted d-block mb-1" style="font-size:1.3rem;"></i>
                        <span class="text-muted small">Belum ada dokumen yang sering dipakai bot.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
