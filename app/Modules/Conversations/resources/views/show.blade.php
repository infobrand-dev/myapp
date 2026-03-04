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
        background: transparent;
        border: 0;
        border-radius: 0;
    }
    .conv-dashboard .conv-section-title {
        letter-spacing: .01em;
        font-size: .9rem;
        font-weight: 600;
        color: #41566d;
        margin: 0;
    }
    .conv-dashboard .conv-page-subtitle {
        color: var(--conv-muted);
        font-size: .88rem;
        margin-top: .2rem;
    }
    .conv-dashboard .conv-tabs {
        gap: .35rem;
    }
    .conv-dashboard .conv-tabs .nav-link {
        border-radius: .6rem;
        border: 1px solid var(--conv-border-soft);
        color: #4b5f75;
        padding: .32rem .65rem;
        font-size: .8rem;
        font-weight: 600;
        background: #fff;
    }
    .conv-dashboard .conv-tabs .nav-link.active {
        background: rgba(32, 107, 196, 0.12);
        border-color: rgba(32, 107, 196, 0.2);
        color: #1f4f80;
    }
    .conv-dashboard .conv-list {
        max-height: 65vh;
        overflow: auto;
        background: transparent;
        border-radius: 0;
        padding: 0;
    }
    .conv-dashboard .conv-item {
        margin: 0;
        border-radius: 0;
        border: 0;
        border-bottom: 1px solid var(--conv-border-soft);
        transition: all .16s ease-in-out;
        background: transparent;
        text-decoration: none;
    }
    .conv-dashboard .conv-item:hover {
        background: rgba(32, 107, 196, 0.05);
        text-decoration: none;
    }
    .conv-dashboard .conv-item.active {
        background: rgba(32, 107, 196, 0.1);
        color: #274767;
        text-decoration: none;
    }
    .conv-dashboard .conv-item:focus,
    .conv-dashboard .conv-item:active,
    .conv-dashboard .conv-item:visited {
        text-decoration: none;
    }
    .conv-dashboard .conv-item.active .text-muted {
        color: #4d6a86 !important;
    }
    .conv-dashboard .conv-item-preview {
        font-size: .78rem;
        color: var(--conv-muted);
        max-width: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        vertical-align: middle;
        flex: 1;
        min-width: 0;
    }
    .conv-dashboard .inbox-avatar {
        width: 1.95rem;
        height: 1.95rem;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid var(--conv-border-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fff;
    }
    .conv-dashboard .inbox-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .conv-dashboard .conv-item-channel {
        font-size: .88rem;
        margin-right: .35rem;
        vertical-align: middle;
        flex-shrink: 0;
    }
    .conv-dashboard .conv-item-snippet {
        display: flex;
        align-items: center;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
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
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: .25rem 0;
    }
    .conv-dashboard .chat-row {
        margin-bottom: .8rem;
    }
    .conv-dashboard .chat-loader {
        text-align: center;
        font-size: .76rem;
        color: var(--conv-muted);
        padding: .3rem 0 .55rem;
    }
    .conv-dashboard .chat-contact-avatar {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid var(--conv-border-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .conv-dashboard .chat-contact-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .conv-dashboard .chat-contact-name {
        font-size: .95rem;
        font-weight: 700;
        color: #2b4258;
        line-height: 1.1;
    }
    .conv-dashboard .chat-contact-last {
        font-size: .8rem;
        color: var(--conv-muted);
        margin-top: .18rem;
        max-width: 420px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .conv-dashboard .chat-avatar {
        width: 2rem;
        height: 2rem;
        flex-shrink: 0;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid var(--conv-border-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .conv-dashboard .chat-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .conv-dashboard .chat-avatar-fallback {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
    }
    .conv-dashboard .avatar-tone-1 { background: #eaf2ff; color: #2a5fa5; }
    .conv-dashboard .avatar-tone-2 { background: #ecf8f1; color: #1f7a47; }
    .conv-dashboard .avatar-tone-3 { background: #f4efff; color: #6655b3; }
    .conv-dashboard .avatar-tone-4 { background: #fff3e8; color: #b96a2b; }
    .conv-dashboard .avatar-tone-5 { background: #f1f5f9; color: #516579; }
    .conv-dashboard .chat-bubble {
        max-width: 78%;
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
    .conv-dashboard .chat-head {
        margin-bottom: .2rem;
    }
    .conv-dashboard .chat-sender {
        font-size: .82rem;
        font-weight: 600;
    }
    .conv-dashboard .chat-state {
        font-size: .72rem;
        color: var(--conv-muted);
        white-space: nowrap;
    }
    .conv-dashboard .chat-bubble-out .chat-meta {
        color: rgba(255, 255, 255, 0.82);
    }
    .conv-dashboard .chat-bubble-out .chat-state {
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
        padding: .15rem 0 .6rem;
    }
    .conv-dashboard .section-body {
        padding: 0;
    }
    .conv-dashboard .section-body-tight {
        padding: 0;
    }
    .conv-dashboard .section-divider {
        border-top: 1px solid rgba(74, 96, 126, 0.1);
        margin-top: .85rem;
        padding-top: .85rem;
    }
    .conv-dashboard .invite-trigger {
        border-radius: .6rem;
        font-weight: 600;
    }
    .conv-dashboard .invite-form-shell {
        border: 1px solid var(--conv-border-soft);
        border-radius: .7rem;
        padding: .55rem;
        background: #fff;
    }
    .conv-dashboard .participants-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--conv-muted);
        text-transform: uppercase;
    }
    .conv-dashboard .participant-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        padding: .5rem 0;
        border-bottom: 1px solid rgba(74, 96, 126, 0.08);
    }
    .conv-dashboard .participant-item:last-child {
        border-bottom: 0;
    }
    .conv-dashboard .participant-left {
        display: flex;
        align-items: center;
        gap: .55rem;
        min-width: 0;
    }
    .conv-dashboard .participant-avatar {
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid var(--conv-border-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #fff;
    }
    .conv-dashboard .participant-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .conv-dashboard .participant-name {
        font-size: .86rem;
        font-weight: 600;
        color: #2e455d;
        line-height: 1.15;
    }
    .conv-dashboard .participant-meta {
        font-size: .74rem;
        color: var(--conv-muted);
        line-height: 1.1;
    }
    .conv-dashboard .mobile-nav {
        display: none;
    }
    .conv-dashboard .mobile-info-btn {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    @media (max-width: 991.98px) {
        .conv-dashboard .mobile-nav {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }
        .conv-dashboard .conv-col {
            display: none;
        }
        .conv-dashboard.mobile-view-inbox .conv-col-inbox {
            display: block;
        }
        .conv-dashboard.mobile-view-chat .conv-col-chat {
            display: block;
        }
        .conv-dashboard.mobile-view-detail .conv-col-detail {
            display: block;
        }
    }
</style>
<div class="conv-dashboard" id="conv-dashboard-root">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0 fw-semibold">Conversations</h2>
        <div class="conv-page-subtitle">Unified inbox for handling customer and internal conversations across channels.</div>
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
    <div class="col-md-3 conv-col conv-col-inbox">
        <div class="conv-surface">
            <div class="section-head"><h3 class="conv-section-title">Inbox</h3></div>
            <div class="section-body section-body-tight">
                <div class="mb-2">
                    <div class="nav nav-pills conv-tabs" id="conversation-filter-tabs" role="tablist" aria-label="Conversation filters">
                        <button type="button" class="nav-link active" data-filter="all">All</button>
                        <button type="button" class="nav-link" data-filter="unsigned">Unsigned</button>
                        <button type="button" class="nav-link" data-filter="assigned">Assigned</button>
                    </div>
                    <input type="text" class="form-control mt-2" id="conversation-search" placeholder="Filter by contact name">
                </div>
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
                        $listAvatar = data_get($c->metadata, 'avatar')
                            ?? data_get($c->metadata, 'photo_url')
                            ?? data_get($c->metadata, 'profile_pic')
                            ?? null;
                        if ($listAvatar && !\Illuminate\Support\Str::startsWith($listAvatar, ['http://', 'https://', '/'])) {
                            $listAvatar = asset('storage/' . ltrim($listAvatar, '/'));
                        }
                        $listName = $c->contact_name ?? $c->contact_external_id ?? 'Internal';
                        $listNameParts = preg_split('/\s+/', trim($listName));
                        $listInitials = strtoupper(substr($listNameParts[0] ?? '?', 0, 1) . substr($listNameParts[1] ?? '', 0, 1));
                        $listAvatarTone = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'][abs(crc32($listName)) % 5];
                    @endphp
                    @php
                        $isUnsigned = !$c->owner_id || optional($c->locked_until)->isPast();
                    @endphp
                    <a href="{{ route('conversations.show', $c) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-start conv-item {{ $c->id === $conversation->id ? 'active' : '' }}"
                       data-name="{{ mb_strtolower($c->contact_name ?? $c->contact_external_id ?? 'internal') }}"
                       data-assignment="{{ $isUnsigned ? 'unsigned' : 'assigned' }}">
                        <div class="d-flex align-items-start gap-2 me-2">
                            <span class="inbox-avatar">
                                @if($listAvatar)
                                    <img src="{{ $listAvatar }}" alt="{{ $listName }}">
                                @else
                                    <span class="chat-avatar-fallback {{ $listAvatarTone }}">{{ $listInitials ?: '?' }}</span>
                                @endif
                            </span>
                            <div>
                                <div class="fw-semibold">{{ $listName }}</div>
                                <div class="conv-item-snippet">
                                    <i class="{{ $channelIcon }} conv-item-channel {{ $channelAccent }}" aria-hidden="true"></i>
                                    <span class="conv-item-preview">{{ $c->latestMessage->body ?? 'No messages yet.' }}</span>
                                </div>
                            </div>
                        </div>
                        @if(($waModuleReady ?? false) && $c->instance)
                            <span class="badge {{ $c->instance->status === 'connected' ? 'text-bg-success' : ($c->instance->status === 'error' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $c->instance->status }}</span>
                        @endif
                    </a>
                @empty
                    <div class="text-muted small p-2">No conversations yet.</div>
                @endforelse
                    <div id="conversation-empty-state" class="text-muted small p-2 d-none">No conversations match this filter.</div>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 conv-col conv-col-chat">
        <div class="conv-surface">
            @php
                $activeContactName = $conversation->contact_name ?? $conversation->contact_external_id ?? 'Internal';
                $activeLastMessageTime = optional($conversation->last_message_at)->diffForHumans() ?? '-';
                $activeAvatar = data_get($conversation->metadata, 'avatar')
                    ?? data_get($conversation->metadata, 'photo_url')
                    ?? data_get($conversation->metadata, 'profile_pic')
                    ?? null;

                if (!$activeAvatar && strtolower($conversation->channel ?? 'internal') === 'internal') {
                    $otherParticipant = $conversation->participants->firstWhere('user_id', '!=', auth()->id());
                    $activeAvatar = $otherParticipant?->user?->avatar;
                }

                if ($activeAvatar && !\Illuminate\Support\Str::startsWith($activeAvatar, ['http://', 'https://', '/'])) {
                    $activeAvatar = asset('storage/' . ltrim($activeAvatar, '/'));
                }

                $activeNameParts = preg_split('/\s+/', trim($activeContactName));
                $activeInitials = strtoupper(substr($activeNameParts[0] ?? '?', 0, 1) . substr($activeNameParts[1] ?? '', 0, 1));
                $activeAvatarTone = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'][abs(crc32($activeContactName)) % 5];
            @endphp
            <div class="section-head">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="mobile-nav">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="mobile-back-inbox">
                            <i class="ti ti-chevron-left" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <div class="chat-contact-avatar">
                        @if($activeAvatar)
                            <img src="{{ $activeAvatar }}" alt="{{ $activeContactName }}">
                        @else
                            <span class="chat-avatar-fallback {{ $activeAvatarTone }}">{{ $activeInitials ?: '?' }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="chat-contact-name">{{ $activeContactName }}</div>
                        <div class="chat-contact-last" id="chat-last-message-time">Last Message: {{ $activeLastMessageTime }}</div>
                    </div>
                    </div>
                    <div class="mobile-nav">
                        <button type="button" class="btn btn-outline-secondary mobile-info-btn" id="mobile-open-detail" aria-label="Open details">
                            <i class="ti ti-info-circle" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="section-body pt-0" id="chat-pane">
                <div id="chat-loader" class="chat-loader {{ $hasMoreMessages ? '' : 'd-none' }}">Scroll up to load older messages...</div>
                @forelse($conversation->messages as $msg)
                    @php
                        $senderName = $msg->user->name ?? ($msg->direction === 'out' ? 'You' : 'System');
                        $senderAvatar = $msg->user->avatar ?? null;
                        if ($senderAvatar && !\Illuminate\Support\Str::startsWith($senderAvatar, ['http://', 'https://', '/'])) {
                            $senderAvatar = asset('storage/' . ltrim($senderAvatar, '/'));
                        }
                        $nameParts = preg_split('/\s+/', trim($senderName));
                        $initials = strtoupper(substr($nameParts[0] ?? '?', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                        $tones = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'];
                        $avatarTone = $tones[abs(crc32($senderName)) % count($tones)];
                    @endphp
                    <div class="chat-row d-flex align-items-end gap-2 {{ $msg->direction === 'out' ? 'justify-content-end flex-row-reverse' : 'justify-content-start' }}" data-message-id="{{ $msg->id }}">
                        <div class="chat-avatar">
                            @if($senderAvatar)
                                <img src="{{ $senderAvatar }}" alt="{{ $senderName }}">
                            @else
                                <span class="chat-avatar-fallback {{ $avatarTone }}">{{ $initials ?: '?' }}</span>
                            @endif
                        </div>
                        <div class="chat-bubble {{ $msg->direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in' }}">
                            <div class="chat-head d-flex align-items-center justify-content-between gap-2">
                                <span class="chat-sender">{{ $senderName }}</span>
                                <span class="chat-state">{{ $msg->direction === 'out' ? 'Outgoing' : 'Incoming' }}{{ $msg->status ? ' | ' . ucfirst($msg->status) : '' }}</span>
                            </div>
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
                    <div class="text-muted">No messages yet.</div>
                @endforelse
            </div>
            <div class="section-body pt-2">
                <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mb-3" id="send-form">
                    @csrf
                    <div class="composer-shell d-flex align-items-center gap-2">
                        <input type="text" name="body" class="form-control" placeholder="Type a message..." required autocomplete="off" id="message-input">
                        <button class="btn btn-primary" type="submit">Send</button>
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
    <div class="col-md-3 conv-col conv-col-detail">
        <div class="conv-surface mb-3">
            <div class="section-head d-flex align-items-center justify-content-between">
                <h3 class="conv-section-title">Details</h3>
                <div class="mobile-nav">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="mobile-back-chat">
                        <i class="ti ti-chevron-left" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
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
            <div class="section-head"><h3 class="conv-section-title">Team</h3></div>
            <div class="section-body">
                @if($conversation->owner_id === auth()->id() || auth()->user()->hasRole('Super-admin'))
                    <button class="btn btn-outline-primary w-100 invite-trigger" type="button" data-bs-toggle="collapse" data-bs-target="#invite-panel" aria-expanded="false" aria-controls="invite-panel">
                        Invite Member
                    </button>
                    <div class="collapse mt-2" id="invite-panel">
                        <div class="invite-form-shell">
                            <form method="POST" action="{{ route('conversations.invite', $conversation) }}" class="d-flex gap-2" onsubmit="return confirm('Undang ' + document.getElementById('invite-query').value + '?')">
                                @csrf
                                <input type="text" name="query" id="invite-query" class="form-control" placeholder="Name or email" required>
                                <button class="btn btn-primary" type="submit">Send</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="text-muted small">Hanya owner atau super-admin yang bisa mengundang.</div>
                @endif
                <div class="section-divider">
                    <div class="participants-title mb-1">Participants</div>
                @forelse($conversation->participants as $p)
                    @php
                        $participantName = $p->user->name ?? ('User '.$p->user_id);
                        $participantAvatar = $p->user->avatar ?? null;
                        if ($participantAvatar && !\Illuminate\Support\Str::startsWith($participantAvatar, ['http://', 'https://', '/'])) {
                            $participantAvatar = asset('storage/' . ltrim($participantAvatar, '/'));
                        }
                        $participantParts = preg_split('/\s+/', trim($participantName));
                        $participantInitials = strtoupper(substr($participantParts[0] ?? '?', 0, 1) . substr($participantParts[1] ?? '', 0, 1));
                        $participantTone = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'][abs(crc32($participantName)) % 5];
                        $invitedAt = optional($p->invited_at)->diffForHumans() ?? 'No invite timestamp';
                    @endphp
                    <div class="participant-item">
                        <div class="participant-left">
                            <span class="participant-avatar">
                                @if($participantAvatar)
                                    <img src="{{ $participantAvatar }}" alt="{{ $participantName }}">
                                @else
                                    <span class="chat-avatar-fallback {{ $participantTone }}">{{ $participantInitials ?: '?' }}</span>
                                @endif
                            </span>
                            <div>
                                <div class="participant-name">{{ $participantName }}</div>
                                <div class="participant-meta">Invited {{ $invitedAt }}</div>
                            </div>
                        </div>
                        <span class="badge bg-azure-lt text-azure">{{ ucfirst($p->role) }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada peserta.</div>
                @endforelse
                </div>
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
        const dashboardRoot = document.getElementById('conv-dashboard-root');
        const chatPane = document.getElementById('chat-pane');
        const convId = {{ $conversation->id }};
        const sidebarUnreadBadge = document.getElementById('sidebar-conv-unread-badge');
        const chatLastMessageTime = document.getElementById('chat-last-message-time');
        const mobileBackInbox = document.getElementById('mobile-back-inbox');
        const mobileOpenDetail = document.getElementById('mobile-open-detail');
        const mobileBackChat = document.getElementById('mobile-back-chat');
        const lockSpan = document.getElementById('lock-remaining');
        const lockedUntil = "{{ optional($conversation->locked_until)->toIso8601String() }}";
        const chatLoader = document.getElementById('chat-loader');
        const messagesEndpoint = "{{ route('conversations.messages', $conversation) }}";
        const markReadEndpoint = "{{ route('conversations.read', $conversation) }}";
        const csrfToken = @json(csrf_token());
        let oldestMessageId = @json($oldestMessageId);
        let hasMoreMessages = @json($hasMoreMessages);
        let loadingOlder = false;
        const filterTabs = document.querySelectorAll('#conversation-filter-tabs [data-filter]');
        const conversationSearch = document.getElementById('conversation-search');
        const conversationItems = Array.from(document.querySelectorAll('.conv-list .conv-item'));
        const conversationEmpty = document.getElementById('conversation-empty-state');
        const renderedMessageIds = new Set(
            Array.from(document.querySelectorAll('.chat-row[data-message-id]'))
                .map((el) => Number(el.dataset.messageId))
                .filter((id) => Number.isFinite(id) && id > 0)
        );
        let activeFilter = 'all';
        let unseenIncomingCount = 0;
        const basePageTitle = document.title;
        let sidebarUnreadCount = Number(sidebarUnreadBadge?.dataset.count ?? 0) || 0;
        let readSyncInFlight = false;

        const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;
        const isChatVisible = () => !isMobile() || dashboardRoot?.classList.contains('mobile-view-chat');
        const refreshUnreadUi = () => {
            if (sidebarUnreadBadge) {
                sidebarUnreadBadge.dataset.count = String(Math.max(0, sidebarUnreadCount));
                sidebarUnreadBadge.textContent = String(Math.max(0, sidebarUnreadCount));
                sidebarUnreadBadge.classList.toggle('d-none', sidebarUnreadCount <= 0);
            }
            document.title = sidebarUnreadCount > 0 ? `(${sidebarUnreadCount}) ${basePageTitle}` : basePageTitle;
        };
        const syncReadToServer = () => {
            if (readSyncInFlight) return;
            readSyncInFlight = true;
            fetch(markReadEndpoint, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).finally(() => {
                readSyncInFlight = false;
            });
        };
        const clearUnread = () => {
            if (unseenIncomingCount > 0) {
                sidebarUnreadCount = Math.max(0, sidebarUnreadCount - unseenIncomingCount);
            }
            unseenIncomingCount = 0;
            refreshUnreadUi();
            syncReadToServer();
        };
        const notifyIncoming = (name, body) => {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'granted') {
                try {
                    new Notification(`New message from ${name}`, {
                        body: (body || '').toString().slice(0, 140),
                        tag: `conv-${convId}`,
                    });
                } catch (_) {}
                return;
            }
            if (Notification.permission === 'default' && !document.hidden) {
                Notification.requestPermission().catch(() => {});
            }
        };
        const setMobileView = (view) => {
            if (!dashboardRoot) return;
            dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
            dashboardRoot.classList.add(`mobile-view-${view}`);
            if (view === 'chat' && document.hasFocus()) {
                clearUnread();
            }
        };
        const initMobileView = () => {
            if (!isMobile()) return;
            const openChat = sessionStorage.getItem('conv-open-chat') === '1';
            sessionStorage.removeItem('conv-open-chat');
            setMobileView(openChat ? 'chat' : 'inbox');
        };

        if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;
        initMobileView();
        refreshUnreadUi();

        conversationItems.forEach((item) => {
            item.addEventListener('click', () => {
                if (!isMobile()) return;
                sessionStorage.setItem('conv-open-chat', '1');
            });
        });
        mobileBackInbox?.addEventListener('click', () => setMobileView('inbox'));
        mobileOpenDetail?.addEventListener('click', () => setMobileView('detail'));
        mobileBackChat?.addEventListener('click', () => setMobileView('chat'));
        window.addEventListener('focus', () => {
            if (isChatVisible()) clearUnread();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && isChatVisible()) {
                clearUnread();
            }
        });
        window.addEventListener('resize', () => {
            if (!isMobile() && dashboardRoot) {
                dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
                if (document.hasFocus()) clearUnread();
            } else if (isMobile() && dashboardRoot && !dashboardRoot.classList.contains('mobile-view-inbox') && !dashboardRoot.classList.contains('mobile-view-chat') && !dashboardRoot.classList.contains('mobile-view-detail')) {
                setMobileView('inbox');
            }
        });

        const normalize = (text) => (text || '').toString().toLowerCase().trim();

        const applyConversationFilters = () => {
            const query = normalize(conversationSearch?.value);
            let visibleCount = 0;
            conversationItems.forEach((item) => {
                const name = normalize(item.dataset.name);
                const assignment = item.dataset.assignment || 'assigned';
                const matchFilter = activeFilter === 'all' || assignment === activeFilter;
                const matchQuery = !query || name.includes(query);
                const visible = matchFilter && matchQuery;
                item.classList.toggle('d-none', !visible);
                if (visible) visibleCount++;
            });
            if (conversationEmpty) {
                conversationEmpty.classList.toggle('d-none', visibleCount > 0 || !conversationItems.length);
            }
        };

        filterTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activeFilter = tab.dataset.filter || 'all';
                filterTabs.forEach((t) => t.classList.toggle('active', t === tab));
                applyConversationFilters();
            });
        });

        let searchTimer = null;
        conversationSearch?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyConversationFilters, 120);
        });

        applyConversationFilters();

        const escapeHtml = (value) => (value || '')
            .toString()
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const initials = (name) => {
            const parts = (name || '').trim().split(/\s+/).filter(Boolean);
            const first = parts[0]?.[0] || '?';
            const second = parts[1]?.[0] || '';
            return (first + second).toUpperCase();
        };

        const avatarTone = (name) => {
            const tones = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'];
            let hash = 0;
            const source = (name || '').toString();
            for (let i = 0; i < source.length; i++) {
                hash = ((hash << 5) - hash) + source.charCodeAt(i);
                hash |= 0;
            }
            return tones[Math.abs(hash) % tones.length];
        };

        const avatarUrl = (raw) => {
            if (!raw) return '';
            const value = raw.toString();
            if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) return value;
            return '/storage/' + value.replace(/^\/+/, '');
        };

        const buildMessageNode = (msg) => {
            const name = msg.user?.name ?? (msg.direction === 'out' ? 'You' : 'System');
            const state = `${msg.direction === 'out' ? 'Outgoing' : 'Incoming'}${msg.status ? ' | ' + msg.status : ''}`;
            const avatar = avatarUrl(msg.user?.avatar ?? '');
            const avatarHtml = avatar
                ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(name)}">`
                : `<span class="chat-avatar-fallback ${avatarTone(name)}">${escapeHtml(initials(name))}</span>`;

            const wrapper = document.createElement('div');
            wrapper.className = 'chat-row d-flex align-items-end gap-2 ' + (msg.direction === 'out' ? 'justify-content-end flex-row-reverse' : 'justify-content-start');
            wrapper.dataset.messageId = msg.id ?? '';
            wrapper.innerHTML = `
                <div class="chat-avatar">${avatarHtml}</div>
                <div class="chat-bubble ${msg.direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in'}">
                    <div class="chat-head d-flex align-items-center justify-content-between gap-2">
                        <span class="chat-sender">${escapeHtml(name)}</span>
                        <span class="chat-state">${escapeHtml(state)}</span>
                    </div>
                    <div style="font-size:.9rem;">${escapeHtml(msg.body)}</div>
                    <div class="chat-meta">${msg.created_at ?? ''}</div>
                </div>`;
            return wrapper;
        };

        const loadOlderMessages = async () => {
            if (!chatPane || loadingOlder || !hasMoreMessages || !oldestMessageId) return;
            loadingOlder = true;
            if (chatLoader) chatLoader.textContent = 'Loading older messages...';

            try {
                const prevHeight = chatPane.scrollHeight;
                const prevTop = chatPane.scrollTop;
                const url = `${messagesEndpoint}?before_id=${encodeURIComponent(oldestMessageId)}&limit=30`;
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('load failed');

                const payload = await response.json();
                const list = Array.isArray(payload.messages) ? payload.messages : [];
                if (list.length) {
                    const fragment = document.createDocumentFragment();
                    list.forEach((msg) => {
                        const id = Number(msg.id);
                        if (Number.isFinite(id) && renderedMessageIds.has(id)) return;
                        if (Number.isFinite(id) && id > 0) renderedMessageIds.add(id);
                        fragment.appendChild(buildMessageNode(msg));
                    });
                    chatPane.insertBefore(fragment, chatPane.firstChild);
                    const newHeight = chatPane.scrollHeight;
                    chatPane.scrollTop = newHeight - prevHeight + prevTop;
                }

                oldestMessageId = payload.oldest_id ?? oldestMessageId;
                hasMoreMessages = !!payload.has_more;
                if (chatLoader) {
                    if (!hasMoreMessages) {
                        chatLoader.classList.add('d-none');
                    } else {
                        chatLoader.textContent = 'Scroll up to load older messages...';
                    }
                }
            } catch (_) {
                if (chatLoader) chatLoader.textContent = 'Failed to load older messages.';
            } finally {
                loadingOlder = false;
            }
        };

        if (chatPane) {
            chatPane.addEventListener('scroll', () => {
                if (chatPane.scrollTop <= 48) {
                    loadOlderMessages();
                }
            });
        }

        if (lockSpan && lockedUntil) {
            let lockTimer = null;
            const tick = () => {
                const diff = (new Date(lockedUntil) - new Date()) / 1000;
                if (diff <= 0) {
                    lockSpan.textContent = 'expired';
                    lockSpan.parentElement?.classList.replace('text-bg-secondary', 'text-bg-warning');
                    if (lockTimer) clearInterval(lockTimer);
                    return;
                }
                const m = Math.floor(diff / 60);
                const s = Math.floor(diff % 60);
                lockSpan.textContent = `${m}m ${s.toString().padStart(2, '0')}s`;
            };
            tick();
            lockTimer = setInterval(tick, 1000);
        }

        if (window.Echo) {
            window.Echo.private('conversations.' + convId)
                .listen('App\\Modules\\Conversations\\Events\\ConversationMessageCreated', (e) => {
                    const msg = e.message;
                    const id = Number(msg.id);
                    if (Number.isFinite(id) && renderedMessageIds.has(id)) return;
                    if (Number.isFinite(id) && id > 0) renderedMessageIds.add(id);
                    const wrapper = buildMessageNode(msg);
                    chatPane?.appendChild(wrapper);
                    if (chatPane) chatPane.scrollTop = chatPane.scrollHeight;
                    if (chatLastMessageTime) chatLastMessageTime.textContent = 'Last Message: just now';

                    const incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
                    if (incomingOutOfView) {
                        unseenIncomingCount += 1;
                        sidebarUnreadCount += 1;
                        refreshUnreadUi();
                        const senderName = msg.user?.name ?? 'Contact';
                        notifyIncoming(senderName, msg.body ?? '');
                    } else if (msg.direction === 'in') {
                        sidebarUnreadCount = Math.max(0, sidebarUnreadCount);
                        clearUnread();
                    }
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

