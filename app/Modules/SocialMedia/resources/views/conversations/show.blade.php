@extends('layouts.admin')

@section('title', ($conversation->contact_name ?: 'Unknown Contact') . ' — Percakapan')

@section('content')

{{-- ══ PAGE HEADER ══════════════════════════════════════════ --}}
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Social Media Inbox</div>
            <h2 class="page-title">{{ $conversation->contact_name ?: 'Unknown Contact' }}</h2>
            <p class="text-muted mb-0">
                @php
                    $platformKey = strtolower((string) ($conversation->metadata['platform'] ?? ''));
                    $platformLabel = ucfirst($platformKey ?: 'Social DM');
                    $platformIconMap = [
                        'instagram' => 'ti-brand-instagram',
                        'facebook'  => 'ti-brand-facebook',
                        'messenger' => 'ti-brand-facebook',
                        'tiktok'    => 'ti-brand-tiktok',
                        'twitter'   => 'ti-brand-twitter',
                        'x'         => 'ti-brand-x',
                    ];
                    $platformIcon = $platformIconMap[$platformKey] ?? 'ti-messages';
                @endphp
                <i class="ti {{ $platformIcon }} me-1"></i>{{ $platformLabel }}
                &mdash; <span class="fw-normal">{{ $conversation->contact_external_id }}</span>
                &mdash;
                @if($conversation->status === 'open')
                    <span class="badge bg-green-lt text-green">Open</span>
                @else
                    <span class="badge bg-secondary-lt text-secondary">{{ ucfirst($conversation->status) }}</span>
                @endif
                @if($botPaused)
                    <span class="badge bg-orange-lt text-orange ms-1"><i class="ti ti-robot-off me-1"></i>Bot Paused</span>
                @endif
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @if($botPaused)
                <form method="POST" action="{{ route('social-media.conversations.resume-bot', $conversation) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="ti ti-player-play me-1"></i>Resume Bot
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('social-media.conversations.pause-bot', $conversation) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="ti ti-player-pause me-1"></i>Pause Bot
                    </button>
                </form>
            @endif
            <a href="{{ route('social-media.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

{{-- ══ CHAT THREAD ══════════════════════════════════════════ --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-messages me-1 text-muted"></i>Percakapan</h3>
        <div class="card-options">
            <span class="text-muted small">{{ $messages->count() }} pesan</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="chat-thread" id="message-thread">
            @forelse($messages as $message)
                @php
                    $isOut  = $message->direction === 'out';
                    $mime   = strtolower((string) ($message->media_mime ?? ''));
                    $isImg  = str_starts_with($mime, 'image/');
                @endphp
                <div class="chat-message {{ $isOut ? 'chat-message--out' : 'chat-message--in' }}">
                    <div class="chat-bubble {{ $isOut ? 'chat-bubble--out' : 'chat-bubble--in' }}">

                        {{-- Sender --}}
                        <div class="chat-sender">
                            @if($isOut && $message->user)
                                <i class="ti ti-user-circle"></i> {{ $message->user->name }}
                            @elseif(!$isOut)
                                <i class="ti ti-user"></i> {{ $conversation->contact_name ?: 'Contact' }}
                            @endif
                        </div>

                        {{-- Media --}}
                        @if($message->media_url)
                            @if($isImg)
                                <div class="chat-media mb-2">
                                    <img src="{{ $message->media_url }}" alt="Attachment">
                                </div>
                            @else
                                <div class="mb-2">
                                    <a href="{{ $message->media_url }}" target="_blank" rel="noopener" class="chat-file-link">
                                        <i class="ti ti-paperclip"></i> Lihat Lampiran
                                    </a>
                                </div>
                            @endif
                        @endif

                        {{-- Body --}}
                        @if($message->body)
                            <div class="chat-body">{{ $message->body }}</div>
                        @endif

                        {{-- Meta: time + status --}}
                        <div class="chat-meta">
                            {{ $message->created_at->format('d/m H:i') }}
                            @if($isOut)
                                &middot;
                                @if(in_array($message->status, ['delivered', 'read']))
                                    <i class="ti ti-checks {{ $message->status === 'read' ? 'text-azure' : '' }}" title="{{ ucfirst($message->status) }}"></i>
                                @elseif($message->status === 'sent')
                                    <i class="ti ti-check" title="Terkirim"></i>
                                @elseif($message->status === 'failed')
                                    <i class="ti ti-alert-circle text-red" title="Gagal"></i>
                                @else
                                    {{ $message->status }}
                                @endif
                            @endif
                        </div>

                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="ti ti-messages text-muted d-block mb-2" style="font-size:2.5rem;"></i>
                    <div class="text-muted">Belum ada pesan dalam percakapan ini.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ══ REPLY FORM ═══════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-send me-1 text-muted"></i>Balas Pesan</h3>
    </div>
    <form method="POST" action="{{ route('social-media.conversations.reply', $conversation) }}" enctype="multipart/form-data">
        @csrf
        @if($errors->any())
            <div class="alert alert-danger mb-0 border-0 rounded-0 border-bottom">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="card-body">
            <div class="mb-3">
                <textarea name="body" id="reply-textarea"
                    class="form-control @error('body') is-invalid @enderror"
                    rows="3" placeholder="Ketik pesan...">{{ old('body') }}</textarea>
                @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="form-label text-muted small mb-1">
                    <i class="ti ti-paperclip me-1"></i>Lampiran (opsional)
                </label>
                <input type="file" name="media_file" id="media-file-input"
                    class="form-control form-control-sm"
                    accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
                <div class="form-hint">Bisa kirim teks, file, atau keduanya. Maks. satu file per pesan.</div>
                <div id="file-preview" class="mt-2 d-none">
                    <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                        <i class="ti ti-file text-muted"></i>
                        <span id="file-preview-name" class="small text-muted flex-grow-1 text-truncate"></span>
                        <button type="button" id="file-clear-btn"
                            class="btn btn-sm btn-ghost-secondary p-0 lh-1" title="Hapus lampiran">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-send me-1"></i>Kirim
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // 1. Auto-scroll chat to bottom
    var thread = document.getElementById('message-thread');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }

    // 2. Animate only the last N messages (visible in view)
    var messages = thread ? thread.querySelectorAll('.chat-message') : [];
    var total    = messages.length;
    var animFrom = Math.max(0, total - 10);
    messages.forEach(function (el, i) {
        if (i >= animFrom) {
            el.style.animationDelay = ((i - animFrom) * 45) + 'ms';
            el.classList.add('chat-message--animate');
        } else {
            el.style.opacity = '1';
        }
    });

    // 3. Auto-resize textarea as user types
    var ta = document.getElementById('reply-textarea');
    if (ta) {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });
        // Focus reply field if there are validation errors
        if (ta.classList.contains('is-invalid')) {
            ta.focus();
        }
    }

    // 4. File attachment preview + clear button
    var fileInput   = document.getElementById('media-file-input');
    var filePreview = document.getElementById('file-preview');
    var fileName    = document.getElementById('file-preview-name');
    var fileClear   = document.getElementById('file-clear-btn');

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                filePreview.classList.remove('d-none');
            } else {
                filePreview.classList.add('d-none');
            }
        });
        fileClear.addEventListener('click', function () {
            fileInput.value = '';
            filePreview.classList.add('d-none');
            fileName.textContent = '';
        });
    }
})();
</script>
@endpush
