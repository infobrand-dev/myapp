@extends('layouts.admin')

@section('content')
@php
    $selectedAccountId = old(
        'chatbot_account_id',
        data_get($activeSession, 'chatbot_account_id', optional($accounts->first())->id)
    );
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Chatbot Playground</h2>
        <div class="text-muted small">Uji langsung akun chatbot tanpa integrasi channel lain.</div>
    </div>
    <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Kelola Chatbot</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Sesi Saya</h3></div>
            <div class="list-group list-group-flush">
                @forelse($sessions as $session)
                    <a href="{{ route('chatbot.playground.show', $session) }}"
                       class="list-group-item list-group-item-action {{ (string) data_get($activeSession, 'id') === (string) $session->id ? 'active' : '' }}">
                        <div class="d-flex justify-content-between">
                            <span>{{ $session->title ?: 'Sesi #' . $session->id }}</span>
                            <small>{{ optional($session->last_message_at)->format('d M H:i') }}</small>
                        </div>
                        <div class="small opacity-75">{{ data_get($session, 'chatbotAccount.name', '-') }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">Belum ada sesi.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                @if($activeSession && $activeSession->messages->count())
                    <div class="mb-3" style="max-height: 420px; overflow-y: auto;">
                        @foreach($activeSession->messages as $msg)
                            <div class="mb-2 {{ $msg->role === 'assistant' ? '' : 'text-end' }}">
                                <div class="badge {{ $msg->role === 'assistant' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                    {{ strtoupper($msg->role) }}
                                </div>
                                <div class="border rounded p-2 mt-1" style="white-space: pre-wrap; line-height: 1.5;">{{ $msg->content }}</div>
                            </div>
                        @endforeach
                    </div>
                @elseif($accounts->isNotEmpty())
                    <div class="text-muted mb-3 small">Pilih chatbot dan ketik pesan untuk memulai sesi baru.</div>
                @endif

                @if($accounts->isNotEmpty())
                <form method="POST" action="{{ route('chatbot.playground.send') }}">
                    @csrf
                    @if($activeSession)
                        <input type="hidden" name="session_id" value="{{ $activeSession->id }}">
                    @endif
                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="chatbot_account_id" class="form-select" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ (string) $selectedAccountId === (string) $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ $acc->model ?: 'default' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="message" class="form-control" placeholder="Ketik pesan..." required>
                        </div>
                    </div>
                    @if($accounts->isEmpty())
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2">
                                <path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/>
                            </svg>
                            <div class="fw-semibold mb-1">Belum ada chatbot aktif</div>
                            <div class="text-muted small mb-3">Tambahkan dan aktifkan setidaknya satu chatbot sebelum bisa mencoba Playground.</div>
                            <a href="{{ route('chatbot.accounts.create') }}" class="btn btn-primary btn-sm">+ Tambah Chatbot</a>
                        </div>
                    @else
                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </div>
                    @endif
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
