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
    <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Kelola Accounts</a>
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
                @else
                    <div class="text-muted mb-3">Mulai chat untuk membuat sesi baru.</div>
                @endif

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
                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" {{ $accounts->isEmpty() ? 'disabled' : '' }}>Kirim</button>
                    </div>
                    @if($accounts->isEmpty())
                        <div class="form-hint text-danger mt-2">Belum ada chatbot account aktif.</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
