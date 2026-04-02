@extends('layouts.admin')

@section('title', 'Chatbot Playground')

@section('content')
@php
    $selectedAccountId = old(
        'chatbot_account_id',
        data_get($activeSession, 'chatbot_account_id', optional($accounts->first())->id)
    );
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Chatbot</div>
            <h2 class="page-title">Playground</h2>
            <p class="text-muted mb-0">Uji langsung respons chatbot tanpa integrasi channel lain.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>Kelola Chatbot
            </a>
        </div>
    </div>
</div>

@if($accounts->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="ti ti-robot text-muted d-block mb-2" style="font-size:2.5rem;"></i>
            <div class="fw-semibold mb-1">Belum ada chatbot aktif</div>
            <div class="text-muted small mb-3">Tambahkan dan aktifkan setidaknya satu chatbot sebelum bisa mencoba Playground.</div>
            <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Tambah Chatbot
            </a>
        </div>
    </div>
@else
<div class="row g-3" style="min-height: 600px;">

    {{-- Kiri: Daftar Sesi --}}
    <div class="col-lg-4 d-flex flex-column">
        <div class="card flex-grow-1">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Sesi Saya</h3>
                <a href="{{ route('chatbot.playground.index') }}" class="btn btn-sm btn-ghost-secondary" title="Mulai sesi baru">
                    <i class="ti ti-plus"></i>
                </a>
            </div>
            <div class="list-group list-group-flush overflow-auto" style="max-height: 520px;">
                @forelse($sessions as $session)
                    <a href="{{ route('chatbot.playground.show', $session) }}"
                       class="list-group-item list-group-item-action {{ (string) data_get($activeSession, 'id') === (string) $session->id ? 'active' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="fw-semibold text-truncate">{{ $session->title ?: 'Sesi #' . $session->id }}</span>
                            <small class="text-nowrap flex-shrink-0 opacity-75">{{ optional($session->last_message_at)->format('d M H:i') }}</small>
                        </div>
                        <div class="small opacity-75 mt-1">{{ data_get($session, 'chatbotAccount.name', '-') }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-center py-4">
                        <i class="ti ti-messages text-muted d-block mb-1" style="font-size:1.5rem;"></i>
                        <span class="text-muted small">Belum ada sesi. Mulai dengan mengirim pesan pertama.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Kanan: Area Chat --}}
    <div class="col-lg-8 d-flex flex-column">
        <div class="card flex-grow-1 d-flex flex-column" style="min-height: 560px;">

            {{-- Header: Pilih Chatbot --}}
            <div class="card-header">
                <form method="POST" action="{{ route('chatbot.playground.send') }}" id="chatbot-selector-form">
                    @csrf
                    @if($activeSession)
                        <input type="hidden" name="session_id" value="{{ $activeSession->id }}">
                    @endif
                    <div class="d-flex align-items-center gap-3 w-100">
                        <i class="ti ti-robot text-muted flex-shrink-0" style="font-size:1.2rem;"></i>
                        <select name="chatbot_account_id" id="chatbot_account_id" class="form-select form-select-sm" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string) $selectedAccountId === (string) $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }}
                                    @if($acc->model) · {{ $acc->model }} @endif
                                </option>
                            @endforeach
                        </select>
                        @if($activeSession)
                            <span class="badge bg-green-lt text-green flex-shrink-0">Sesi aktif</span>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Body: Percakapan --}}
            <div class="card-body flex-grow-1 overflow-auto playground-chat-messages" id="chat-messages">
                @if($activeSession && $activeSession->messages->count())
                    @foreach($activeSession->messages as $msg)
                        <div class="d-flex {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            @if($msg->role !== 'user')
                                <div class="avatar avatar-sm me-2 flex-shrink-0"
                                     style="background:var(--tblr-azure-lt,#dbe9f7); color:var(--tblr-azure,#4299e1);">
                                    <i class="ti ti-robot"></i>
                                </div>
                            @endif
                            <div class="playground-bubble playground-bubble--{{ $msg->role === 'user' ? 'user' : 'bot' }}">
                                {{ $msg->content }}
                            </div>
                            @if($msg->role === 'user')
                                <div class="avatar avatar-sm ms-2 flex-shrink-0"
                                     style="background:var(--tblr-primary-lt,#e8f0fe); color:var(--tblr-primary);">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5 text-muted" id="chat-empty-state">
                        <i class="ti ti-message-chatbot d-block mb-2" style="font-size:2rem;"></i>
                        <div class="small">Pilih chatbot di atas dan ketik pesan untuk memulai percakapan.</div>
                    </div>
                @endif
            </div>

            {{-- Footer: Input Pesan --}}
            <div class="card-footer">
                <form method="POST" action="{{ route('chatbot.playground.send') }}" id="playground-send-form">
                    @csrf
                    @if($activeSession)
                        <input type="hidden" name="session_id" value="{{ $activeSession->id }}">
                    @endif
                    <input type="hidden" name="chatbot_account_id" id="hidden_account_id" value="{{ $selectedAccountId }}">
                    <div class="d-flex gap-2">
                        <input type="text" name="message"
                               class="form-control"
                               placeholder="Ketik pesan..."
                               autocomplete="off"
                               required>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ti ti-send me-1"></i>Kirim
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>
@endif

@push('scripts')
<script>
(function () {
    // Scroll chat to bottom on load
    var chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Sync chatbot selector → hidden input in send form
    var selectorEl = document.getElementById('chatbot_account_id');
    var hiddenInput = document.getElementById('hidden_account_id');
    if (selectorEl && hiddenInput) {
        selectorEl.addEventListener('change', function () {
            hiddenInput.value = selectorEl.value;
        });
    }
})();
</script>
@endpush

@endsection
