@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $conversation->contact_name ?: 'Unknown Contact' }}</h2>
        <div class="text-muted small">
            {{ $conversation->metadata['platform'] ?? 'Social DM' }}
            &mdash; {{ $conversation->contact_external_id }}
            &mdash;
            @if($conversation->status === 'open')
                <span class="badge bg-success-lt">Open</span>
            @else
                <span class="badge bg-secondary-lt">{{ ucfirst($conversation->status) }}</span>
            @endif
            @if($botPaused)
                <span class="badge bg-warning-lt ms-1">Bot Paused</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @if($botPaused)
            <form method="POST" action="{{ route('social-media.conversations.resume-bot', $conversation) }}">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm">Resume Bot</button>
            </form>
        @else
            <form method="POST" action="{{ route('social-media.conversations.pause-bot', $conversation) }}">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">Pause Bot</button>
            </form>
        @endif
        <a href="{{ route('social-media.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body" style="max-height: 500px; overflow-y: auto;" id="message-thread">
        @forelse($messages as $message)
            <div class="mb-3 d-flex {{ $message->direction === 'out' ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-2 rounded {{ $message->direction === 'out' ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                    @if($message->direction === 'out' && $message->user)
                        <div class="small fw-semibold mb-1 text-white-50">{{ $message->user->name }}</div>
                    @elseif($message->direction === 'in')
                        <div class="small fw-semibold mb-1 text-muted">{{ $conversation->contact_name ?: 'Contact' }}</div>
                    @endif
                    <div>{{ $message->body }}</div>
                    <div class="small mt-1 {{ $message->direction === 'out' ? 'text-white-50' : 'text-muted' }}">
                        {{ $message->created_at->format('d/m H:i') }}
                        @if($message->direction === 'out')
                            &middot; {{ $message->status }}
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">Belum ada pesan.</div>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Balas</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('social-media.conversations.reply', $conversation) }}">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger mb-2">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="mb-2">
                <textarea name="body" class="form-control" rows="3" placeholder="Ketik pesan..." required>{{ old('body') }}</textarea>
            </div>
            <button class="btn btn-primary">Kirim</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('message-thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
</script>
@endpush
