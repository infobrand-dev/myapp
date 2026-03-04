@extends('layouts.admin')

@section('content')
<style>
    .conv-dashboard {
        --conv-shell-bg: #f8fafc;
        --conv-surface: #ffffff;
        --conv-border: rgba(74, 96, 126, 0.16);
        --conv-border-soft: rgba(74, 96, 126, 0.1);
        --conv-muted: #66788a;
        --conv-text: #223446;
        --conv-primary: #206bc4;
        color: var(--conv-text);
    }
    .conv-dashboard .conv-surface {
        background: var(--conv-surface);
        border: 1px solid var(--conv-border-soft);
        border-radius: .9rem;
    }
    .conv-dashboard .conv-section-title {
        letter-spacing: .01em;
        font-size: .9rem;
        font-weight: 600;
        color: #41566d;
        margin: 0;
    }
    .conv-dashboard .conv-list {
        max-height: 65vh;
        overflow: auto;
        background: var(--conv-shell-bg);
        border-radius: .75rem;
        padding: .3rem;
    }
    .conv-dashboard .conv-item {
        margin: .2rem;
        border-radius: .65rem;
        border: 1px solid transparent;
        transition: all .16s ease-in-out;
        background: rgba(255, 255, 255, 0.85);
    }
    .conv-dashboard .conv-item:hover {
        background: #fff;
        border-color: var(--conv-border);
    }
    .conv-dashboard .conv-item.active {
        background: rgba(32, 107, 196, 0.08);
        border-color: rgba(32, 107, 196, 0.2);
        color: #274767;
    }
    .conv-dashboard .conv-item.active .text-muted {
        color: #4d6a86 !important;
    }
    .conv-dashboard .channel-icon {
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .conv-dashboard .channel-whatsapp { color: #146d38; background: #eaf7ef; }
    .conv-dashboard .channel-social { color: #2a5fa5; background: #edf3fb; }
    .conv-dashboard .channel-internal { color: #5f5aa2; background: #f1effb; }
    .conv-dashboard .channel-default { color: #4f6275; background: #eef2f6; }
    .conv-dashboard #chat-pane {
        height: 60vh;
        overflow: auto;
        background: var(--conv-shell-bg);
        border: 1px solid var(--conv-border-soft);
        border-radius: .75rem;
        padding: 1rem;
    }
    .conv-dashboard .chat-row {
        margin-bottom: .8rem;
    }
    .conv-dashboard .chat-bubble {
        max-width: 80%;
        border-radius: .82rem;
        padding: .68rem .85rem;
        border: 1px solid transparent;
    }
    .conv-dashboard .chat-bubble-in {
        background: #fff;
        border-color: var(--conv-border-soft);
    }
    .conv-dashboard .chat-bubble-out {
        background: var(--conv-primary);
        color: #fff;
        border-color: transparent;
    }
    .conv-dashboard .chat-meta {
        font-size: .75rem;
        color: var(--conv-muted);
        margin-top: .35rem;
    }
    .conv-dashboard .chat-bubble-out .chat-meta {
        color: rgba(255, 255, 255, 0.82);
    }
    .conv-dashboard .composer-shell {
        border: 1px solid var(--conv-border);
        border-radius: .78rem;
        padding: .35rem;
        background: #fff;
    }
    .conv-dashboard .composer-shell .form-control {
        border: 0;
        box-shadow: none;
        padding-left: .65rem;
        font-size: .95rem;
    }
    .conv-dashboard .composer-shell .btn {
        border-radius: .62rem !important;
        padding-left: .95rem;
        padding-right: .95rem;
    }
    .conv-dashboard .detail-list .detail-row {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .5rem 0;
        border-bottom: 1px solid rgba(74, 96, 126, 0.1);
    }
    .conv-dashboard .detail-list .detail-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .conv-dashboard .detail-key {
        font-size: .82rem;
        color: var(--conv-muted);
    }
    .conv-dashboard .detail-value {
        font-weight: 500;
        color: #2f455c;
        text-align: right;
    }
    .conv-dashboard .section-head {
        padding: .75rem .9rem .5rem;
    }
    .conv-dashboard .section-body {
        padding: 0 .9rem .9rem;
    }
    .conv-dashboard .section-body-tight {
        padding: 0 .75rem .75rem;
    }
    .conv-dashboard .section-divider {
        border-top: 1px solid rgba(74, 96, 126, 0.1);
        margin-top: .85rem;
        padding-top: .85rem;
    }
</style>
<div class="conv-dashboard">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0 fw-semibold">Percakapan</h2>
        <div class="text-muted small mt-1">Channel: {{ strtoupper($conversation->channel ?? 'INTERNAL') }} | Lock timeout {{ $lockMinutes }} menit.</div>
        @if($conversation->channel === 'wa_api')
            <div class="text-muted small mt-1">
                Instance aktif:
                <span class="badge bg-azure-lt text-azure">{{ ($waModuleReady ?? false) ? ($conversation->instance->name ?? 'Instance tidak ditemukan') : 'Module WA belum aktif' }}</span>
                <span class="ms-1">Instance percakapan ini terkunci dan tidak bisa diganti.</span>
            </div>
        @endif
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
            <span class="badge text-bg-secondary">Locked <span id="lock-remaining">{{ optional($conversation->locked_until)->format('H:i') }}</span></span>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="conv-surface">
            <div class="section-head"><h3 class="conv-section-title">Percakapan</h3></div>
            <div class="section-body section-body-tight">
                <div class="list-group list-group-flush conv-list">
                @forelse($conversationsList as $c)
                    @php
                        $channel = strtolower($c->channel ?? 'internal');
                        $channelIcon = match($channel) {
                            'wa_api', 'wa_bro', 'whatsapp' => 'ti ti-brand-whatsapp',
                            'social_dm', 'social' => 'ti ti-brand-messenger',
                            'internal' => 'ti ti-user',
                            default => 'ti ti-message',
                        };
                        $channelAccent = match($channel) {
                            'wa_api', 'wa_bro', 'whatsapp' => 'channel-whatsapp',
                            'social_dm', 'social' => 'channel-social',
                            'internal' => 'channel-internal',
                            default => 'channel-default',
                        };
                    @endphp
                    <a href="{{ route('conversations.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start conv-item {{ $c->id === $conversation->id ? 'active' : '' }}">
                        <div class="d-flex align-items-start gap-2 me-2">
                            <span class="channel-icon {{ $channelAccent }}"><i class="{{ $channelIcon }}" aria-hidden="true"></i></span>
                            <div>
                                <div class="fw-semibold">{{ $c->contact_name ?? $c->contact_external_id ?? 'Internal' }}</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ strtoupper($c->channel ?? 'internal') }}</div>
                            </div>
                        </div>
                        @if(($waModuleReady ?? false) && $c->instance)
                            <span class="badge {{ $c->instance->status === 'connected' ? 'text-bg-success' : ($c->instance->status === 'error' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $c->instance->status }}</span>
                        @endif
                    </a>
                @empty
                    <div class="text-muted small p-2">Belum ada percakapan.</div>
                @endforelse
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="conv-surface">
            <div class="section-head">
                <h3 class="conv-section-title">Chat</h3>
            </div>
            <div class="section-body pt-0" id="chat-pane">
                @forelse($conversation->messages as $msg)
                    <div class="chat-row d-flex {{ $msg->direction === 'out' ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="chat-bubble {{ $msg->direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in' }}">
                            <div class="fw-semibold mb-1" style="font-size:.82rem;">{{ $msg->user->name ?? ($msg->direction === 'out' ? 'You' : 'System') }}</div>
                            <div style="font-size:.9rem;">
                                @if($msg->type === 'template')
                                    <div class="badge bg-azure-lt text-azure mb-1">WA Template</div>
                                @endif
                                {{ $msg->body }}
                            </div>
                            <div class="chat-meta">{{ optional($msg->created_at)->format('d M H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada pesan.</div>
                @endforelse
            </div>
            <div class="section-body pt-2">
                <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mb-3" id="send-form">
                    @csrf
                    <div class="composer-shell d-flex align-items-center gap-2">
                        <input type="text" name="body" class="form-control" placeholder="Ketik pesan..." required autocomplete="off" id="message-input">
                        <button class="btn btn-primary" type="submit">Kirim</button>
                    </div>
                </form>
                @if($conversation->channel === 'wa_api' && $waTemplates->isNotEmpty())
                    <div class="section-divider">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">Kirim Template WA</div>
                            <span class="text-muted small">Sesuai 24h rules</span>
                        </div>
                        <form method="POST" action="{{ route('conversations.send', $conversation) }}" id="template-form">
                            @csrf
                            <input type="hidden" name="message_type" value="template">
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <select name="template_id" id="template_id" class="form-select" required>
                                        <option value="">Pilih template</option>
                                        @foreach($waTemplates as $tpl)
                                            <option value="{{ $tpl->id }}" data-body="{{ e($tpl->body) }}" data-header="{{ e(data_get(collect($tpl->components)->firstWhere('type','header'), 'parameters.0.text')) }}">
                                                {{ $tpl->name }} ({{ $tpl->language }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="tpl_lang" placeholder="Lang" disabled>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-success w-100" type="submit">Kirim</button>
                                </div>
                            </div>
                            <div id="tpl-vars" class="row g-2 mt-2"></div>
                            <div class="text-muted small mt-1" id="tpl-preview"></div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="conv-surface mb-3">
            <div class="section-head"><h3 class="conv-section-title">Detail</h3></div>
            <div class="section-body detail-list pt-0">
                <div class="detail-row"><span class="detail-key">Kontak</span><span class="detail-value">{{ $conversation->contact_name ?? $conversation->contact_external_id ?? 'Internal' }}</span></div>
                <div class="detail-row"><span class="detail-key">Owner</span><span class="detail-value">{{ $conversation->owner->name ?? 'Unassigned' }}</span></div>
                <div class="detail-row"><span class="detail-key">Status</span><span class="detail-value">{{ ucfirst($conversation->status) }}</span></div>
                <div class="detail-row"><span class="detail-key">Last message</span><span class="detail-value">{{ optional($conversation->last_message_at)->diffForHumans() ?? '-' }}</span></div>
                @if(($waModuleReady ?? false) && $conversation->instance)
                    <div class="detail-row"><span class="detail-key">Instance</span><span class="detail-value">{{ $conversation->instance->name }}</span></div>
                @endif
            </div>
        </div>
        <div class="conv-surface">
            <div class="section-head"><h3 class="conv-section-title">Invite</h3></div>
            <div class="section-body">
                @if($conversation->owner_id === auth()->id() || auth()->user()->hasRole('Super-admin'))
                    <form method="POST" action="{{ route('conversations.invite', $conversation) }}" class="d-flex gap-2" onsubmit="return confirm('Undang ' + document.getElementById('invite-query').value + '?')">
                        @csrf
                        <input type="text" name="query" id="invite-query" class="form-control" placeholder="Nama atau Email" required>
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
        <div class="conv-surface mt-3">
            <div class="section-head"><h3 class="conv-section-title">Aktivitas</h3></div>
            <div class="section-body pt-0" id="log-body" style="max-height: 240px; overflow:auto;">
                <div class="text-muted small">Memuat log...</div>
            </div>
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
        const lockSpan = document.getElementById('lock-remaining');
        const lockedUntil = "{{ optional($conversation->locked_until)->toIso8601String() }}";

        if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;

        if (lockSpan && lockedUntil) {
            const tick = () => {
                const diff = (new Date(lockedUntil) - new Date()) / 1000;
                if (diff <= 0) {
                    lockSpan.textContent = 'expired';
                    lockSpan.parentElement?.classList.replace('text-bg-secondary', 'text-bg-warning');
                    return;
                }
                const m = Math.floor(diff / 60);
                const s = Math.floor(diff % 60);
                lockSpan.textContent = `${m}m ${s.toString().padStart(2, '0')}s`;
                requestAnimationFrame(tick);
            };
            tick();
        }

        if (window.Echo) {
            window.Echo.private('conversations.' + convId)
                .listen('App\\Modules\\Conversations\\Events\\ConversationMessageCreated', (e) => {
                    const msg = e.message;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'chat-row d-flex ' + (msg.direction === 'out' ? 'justify-content-end' : 'justify-content-start');
                    wrapper.innerHTML = `
                        <div class="chat-bubble ${msg.direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in'}">
                            <div class="small fw-semibold mb-1">${msg.user?.name ?? (msg.direction === 'out' ? 'You' : 'System')}</div>
                            <div class="small">${msg.body}</div>
                            <div class="chat-meta">${msg.created_at ?? ''}</div>
                        </div>`;
                    chatPane?.appendChild(wrapper);
                    if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;
                });
        }

        // Template selector (WA API)
        const tplSelect = document.getElementById('template_id');
        const tplVars = document.getElementById('tpl-vars');
        const tplLang = document.getElementById('tpl_lang');
        const tplPreview = document.getElementById('tpl-preview');

        function extractPlaceholders(text) {
            if (!text) return [];
            const matches = [...text.matchAll(/\{\{(\d+)\}\}/g)];
            const nums = [...new Set(matches.map(m => parseInt(m[1], 10)))].sort((a,b)=>a-b);
            return nums;
        }

        function renderVars() {
            if (!tplSelect) return;
            const opt = tplSelect.selectedOptions[0];
            if (!opt) return;
            const body = opt.getAttribute('data-body') || '';
            const header = opt.getAttribute('data-header') || '';
            const placeholders = [...new Set([...extractPlaceholders(body), ...extractPlaceholders(header)])];
            tplVars.innerHTML = '';
            tplLang.value = opt.textContent.match(/\((.*?)\)/)?.[1] ?? '';
            tplPreview.textContent = body ? `Preview body: ${body}` : '';
            placeholders.forEach(idx => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="input-group">
                        <span class="input-group-text">&#123;&#123;${idx}&#125;&#125;</span>
                        <input type="text" class="form-control" name="template_params[${idx}]" placeholder="Isi untuk &#123;&#123;${idx}&#125;&#125;" required>
                    </div>`;
                tplVars.appendChild(col);
            });
            if (!placeholders.length) {
                tplVars.innerHTML = '<div class="text-muted small ms-1">Tidak ada placeholder.</div>';
            }
        }

        tplSelect?.addEventListener('change', renderVars);
        if (tplSelect) renderVars();

        // Load activity log
        fetch('{{ route('conversations.logs', $conversation) }}')
            .then(r => r.json())
            .then(list => {
                const body = document.getElementById('log-body');
                body.innerHTML = '';
                if (!list.length) {
                    body.innerHTML = '<div class=\"text-muted small\">Belum ada aktivitas.</div>';
                    return;
                }
                list.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'small mb-1';
                    const name = item.user?.name ?? 'System';
                    div.innerHTML = `<span class="text-secondary">${item.created_at}</span> - <strong>${name}</strong> ${item.action}${item.detail ? ': ' + item.detail : ''}`;
                    body.appendChild(div);
                });
            })
            .catch(() => {
                const body = document.getElementById('log-body');
                body.innerHTML = '<div class=\"text-danger small\">Gagal memuat log.</div>';
            });
    });
</script>
@endpush

