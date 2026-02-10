@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Percakapan</h2>
        <div class="text-muted small">Channel: {{ strtoupper($conversation->channel ?? 'INTERNAL') }} | Lock timeout {{ $lockMinutes }} menit.</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary">Kembali</a>
        @if($conversation->owner_id === auth()->id())
            <form method="POST" action="{{ route('conversations.release', $conversation) }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-secondary" type="submit">Release</button>
            </form>
        @elseif(!$conversation->owner_id || optional($conversation->locked_until)->isPast())
            <form method="POST" action="{{ route('conversations.claim', $conversation) }}" class="d-inline">
                @csrf
                <button class="btn btn-primary" type="submit">Claim</button>
            </form>
        @else
            <span class="badge bg-secondary">Locked sampai {{ optional($conversation->locked_until)->format('H:i') }}</span>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Chat</h3>
            </div>
            <div class="card-body" style="height:60vh; overflow:auto; background:#f8f9fa;" id="chat-pane">
                @forelse($conversation->messages as $msg)
                    <div class="mb-3 d-flex {{ $msg->direction === 'out' ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="px-3 py-2 rounded {{ $msg->direction === 'out' ? 'bg-primary text-white' : 'bg-white border' }}" style="max-width: 80%;">
                            <div class="small fw-bold mb-1">{{ $msg->user->name ?? ($msg->direction === 'out' ? 'You' : 'System') }}</div>
                            <div class="small">{{ $msg->body }}</div>
                            <div class="text-muted small mt-1">{{ optional($msg->created_at)->format('d M H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada pesan.</div>
                @endforelse
            </div>
            <div class="card-footer">
                <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="d-flex gap-2" id="send-form">
                    @csrf
                    <input type="text" name="body" class="form-control" placeholder="Ketik pesan..." required autocomplete="off" id="message-input">
                    <button class="btn btn-primary" type="submit">Kirim</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Detail</h3></div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted">Kontak:</span> {{ $conversation->contact_name ?? $conversation->contact_wa_id ?? 'Internal' }}</div>
                <div class="mb-2"><span class="text-muted">Owner:</span> {{ $conversation->owner->name ?? 'Unassigned' }}</div>
                <div class="mb-2"><span class="text-muted">Status:</span> {{ ucfirst($conversation->status) }}</div>
                <div class="mb-2"><span class="text-muted">Last message:</span> {{ optional($conversation->last_message_at)->diffForHumans() ?? '-' }}</div>
                @if($conversation->instance)
                    <div class="mb-2"><span class="text-muted">Instance:</span> {{ $conversation->instance->name }}</div>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Invite</h3></div>
            <div class="card-body">
                @if($conversation->owner_id === auth()->id() || auth()->user()->hasRole('Super-admin'))
                    <form method="POST" action="{{ route('conversations.invite', $conversation) }}" class="d-flex gap-2">
                        @csrf
                        <input type="number" name="user_id" class="form-control" placeholder="User ID" required>
                        <button class="btn btn-outline-primary" type="submit">Invite</button>
                    </form>
                @else
                    <div class="text-muted small">Hanya owner atau super-admin yang bisa mengundang.</div>
                @endif
                <hr>
                <div class="text-muted small mb-2">Peserta</div>
                @forelse($conversation->participants as $p)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>{{ $p->user->name ?? 'User '.$p->user_id }}</span>
                        <span class="badge bg-azure-lt text-azure">{{ $p->role }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada peserta.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const chatPane = document.getElementById('chat-pane');
        const input = document.getElementById('message-input');
        const convId = {{ $conversation->id }};

        if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;

        if (window.Echo) {
            window.Echo.private('conversations.' + convId)
                .listen('App\\Modules\\Conversations\\Events\\ConversationMessageCreated', (e) => {
                    const msg = e.message;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-3 d-flex ' + (msg.direction === 'out' ? 'justify-content-end' : 'justify-content-start');
                    wrapper.innerHTML = `
                        <div class="px-3 py-2 rounded ${msg.direction === 'out' ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 80%;">
                            <div class="small fw-bold mb-1">${msg.user?.name ?? (msg.direction === 'out' ? 'You' : 'System')}</div>
                            <div class="small">${msg.body}</div>
                            <div class="text-muted small mt-1">${msg.created_at ?? ''}</div>
                        </div>`;
                    chatPane?.appendChild(wrapper);
                    if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;
                });
        }
    });
</script>
@endpush
