@extends('layouts.admin')

@section('content')
@php
    $conversationMeta = is_array($conversation->metadata) ? $conversation->metadata : [];
    $isSocialConversation = strtolower((string) ($conversation->channel ?? '')) === 'social_dm';
    $isWhatsAppConversation = strtolower((string) ($conversation->channel ?? '')) === 'wa_api';
    $socialBotPaused = (bool) ($conversationMeta['auto_reply_paused'] ?? false);
    $needsHuman = (bool) ($conversationMeta['needs_human'] ?? false);
    $handoffAt = $conversationMeta['handoff_at'] ?? null;
    $isOwner = (int) ($conversation->owner_id ?? 0) === (int) auth()->id();
    $isParticipant = $conversation->participants->contains(fn ($participant) => (int) $participant->user_id === (int) auth()->id());
    $isSuperAdmin = auth()->user()->hasRole('Super-admin');
    $canReply = $isOwner || $isParticipant || $isSuperAdmin;
    $replyDisabledMessage = 'Claim conversation atau minta owner mengundang Anda sebagai participant untuk membalas.';
    $canResumeSocialBot = $isSocialConversation && $socialBotPaused
        && ($isOwner || $isSuperAdmin);
    $canPauseSocialBot = $isSocialConversation && !$socialBotPaused
        && ($isOwner || $isSuperAdmin);
@endphp
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
        width: 100%;
    }
    .conv-dashboard .chat-row-in {
        justify-content: flex-start;
    }
    .conv-dashboard .chat-row-out {
        justify-content: flex-end;
    }
    .conv-dashboard .chat-row-out .chat-avatar {
        order: 2;
    }
    .conv-dashboard .chat-row-out .chat-bubble {
        order: 1;
        text-align: right;
    }
    .conv-dashboard .chat-row-in .chat-bubble {
        text-align: left;
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
        border-top-left-radius: .35rem;
    }
    .conv-dashboard .chat-bubble-out {
        background: var(--conv-primary);
        color: #fff;
        border-color: transparent;
        border-top-right-radius: .35rem;
    }
    .conv-dashboard .chat-meta {
        font-size: .75rem;
        color: var(--conv-muted);
        margin-top: .35rem;
    }
    .conv-dashboard .chat-head {
        margin-bottom: .2rem;
    }
    .conv-dashboard .chat-row-out .chat-head {
        justify-content: flex-end !important;
    }
    .conv-dashboard .chat-row-in .chat-head {
        justify-content: flex-start !important;
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
        text-align: right;
    }
    .conv-dashboard .chat-bubble-out .chat-state {
        color: rgba(255, 255, 255, 0.82);
    }
    .conv-dashboard .chat-bubble-in .chat-meta {
        text-align: left;
    }
    .conv-dashboard .chat-bubble-in .chat-state {
        text-align: left;
    }
    .conv-dashboard .chat-message-body {
        font-size: .9rem;
    }
    .conv-dashboard .chat-media {
        margin-bottom: .55rem;
    }
    .conv-dashboard .chat-media img,
    .conv-dashboard .chat-media video {
        display: block;
        max-width: min(100%, 320px);
        max-height: 260px;
        border-radius: .7rem;
        background: rgba(15, 23, 42, 0.06);
    }
    .conv-dashboard .chat-media audio {
        display: block;
        width: min(100%, 320px);
    }
    .conv-dashboard .chat-file-card {
        display: inline-flex;
        align-items: center;
        gap: .7rem;
        padding: .7rem .8rem;
        border-radius: .7rem;
        border: 1px solid var(--conv-border-soft);
        background: rgba(255, 255, 255, 0.72);
        color: inherit;
        text-decoration: none;
        max-width: min(100%, 320px);
    }
    .conv-dashboard .chat-bubble-out .chat-file-card {
        background: rgba(255, 255, 255, 0.16);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }
    .conv-dashboard .chat-file-icon {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: .6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(32, 107, 196, 0.12);
        font-weight: 700;
        flex-shrink: 0;
    }
    .conv-dashboard .chat-bubble-out .chat-file-icon {
        background: rgba(255, 255, 255, 0.18);
    }
    .conv-dashboard .chat-file-name {
        font-weight: 600;
        line-height: 1.2;
        word-break: break-word;
    }
    .conv-dashboard .chat-file-meta {
        font-size: .75rem;
        opacity: .8;
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
    .conv-dashboard .inbox-add-btn {
        width: 1.9rem;
        height: 1.9rem;
        border-radius: .55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    @media (max-width: 991.98px) {
        .conv-dashboard .conv-page-head {
            align-items: flex-start !important;
            margin-bottom: .75rem !important;
            position: sticky;
            top: 3.25rem;
            z-index: 20;
            background: var(--tblr-bg-surface, #fff);
            padding: .35rem 0 .55rem;
            border-bottom: 1px solid rgba(74, 96, 126, 0.1);
        }
        .conv-dashboard .conv-page-subtitle {
            font-size: .8rem;
            margin-top: .1rem;
        }
        .conv-dashboard .conv-page-actions {
            display: none !important;
        }
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
        .conv-dashboard .conv-list {
            max-height: calc(100dvh - 16.5rem);
        }
        .conv-dashboard #chat-pane {
            height: calc(100dvh - 22rem);
            min-height: 42dvh;
            max-height: 62dvh;
        }
        .conv-dashboard .chat-bubble {
            max-width: calc(100% - 2.85rem);
        }
        .conv-dashboard .chat-head {
            flex-wrap: wrap;
            gap: .15rem .4rem !important;
        }
        .conv-dashboard .chat-contact-last {
            max-width: 200px;
        }
        .conv-dashboard .conv-item-snippet {
            max-width: 100%;
        }
        .conv-dashboard .detail-list .detail-row {
            flex-direction: column;
            align-items: flex-start;
            gap: .2rem;
        }
        .conv-dashboard .detail-value {
            text-align: left;
        }
        .conv-dashboard .participant-item {
            align-items: flex-start;
        }
        .conv-dashboard .section-head {
            padding: .2rem 0 .5rem;
        }
        .conv-dashboard .composer-shell {
            position: sticky;
            bottom: .25rem;
            background: #fff;
            z-index: 5;
        }
    }
    .conv-dashboard .user-search-wrap {
        position: relative;
    }
    .conv-dashboard .user-search-results {
        position: absolute;
        top: calc(100% + .3rem);
        left: 0;
        right: 0;
        z-index: 20;
        background: #fff;
        border: 1px solid var(--conv-border-soft);
        border-radius: .55rem;
        max-height: 230px;
        overflow: auto;
        display: none;
    }
    .conv-dashboard .user-search-results.show {
        display: block;
    }
    .conv-dashboard .user-search-item {
        width: 100%;
        text-align: left;
        border: 0;
        background: transparent;
        padding: .5rem .65rem;
        font-size: .86rem;
        color: #2e455d;
    }
    .conv-dashboard .user-search-item:hover {
        background: rgba(32, 107, 196, 0.08);
    }
    .conv-dashboard .user-search-note {
        padding: .55rem .65rem;
        font-size: .78rem;
        color: var(--conv-muted);
    }
</style>
<div class="conv-dashboard mobile-view-inbox" id="conv-dashboard-root">
<div class="conv-page-head d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0 fw-semibold">Conversations</h2>
        <div class="conv-page-subtitle">Unified inbox for handling customer and internal conversations across channels.</div>
    </div>
    <div class="btn-list conv-page-actions">
        <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary">Kembali</a>
        <button type="button" class="btn btn-outline-primary d-none" id="enable-web-notif-btn">Aktifkan Notifikasi</button>
        @if($canPauseSocialBot)
            <form method="POST" action="{{ route('social-media.conversations.pause-bot', $conversation) }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-warning" type="submit">Pause Bot</button>
            </form>
        @endif
        @if($canResumeSocialBot)
            <form method="POST" action="{{ route('social-media.conversations.resume-bot', $conversation) }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-success" type="submit">Resume Bot</button>
            </form>
        @endif
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
            <div class="section-head d-flex align-items-center justify-content-between">
                <h3 class="conv-section-title">Inbox</h3>
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm inbox-add-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#start-conversation-modal"
                    aria-label="Start conversation">
                    <i class="ti ti-user-plus" aria-hidden="true"></i>
                </button>
            </div>
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
                       data-assignment="{{ $isUnsigned ? 'unsigned' : 'assigned' }}"
                       data-unread-count="{{ (int) ($c->unread_count ?? 0) }}">
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
                        @if((int) ($c->unread_count ?? 0) > 0)
                            <span class="badge text-bg-danger">{{ (int) $c->unread_count }}</span>
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
                    <div class="chat-row chat-row-{{ $msg->direction === 'out' ? 'out' : 'in' }} d-flex align-items-end gap-2" data-message-id="{{ $msg->id }}">
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
                            @php
                                $messageMediaUrl = $msg->media_url;
                                $messageFilename = data_get($msg->payload, 'filename') ?: $msg->body;
                                $isPreviewableMediaUrl = is_string($messageMediaUrl) && \Illuminate\Support\Str::startsWith($messageMediaUrl, ['http://', 'https://', '/']);
                            @endphp
                            <div class="chat-message-body">
                                @if($msg->type === 'template')
                                    <div class="badge bg-azure-lt text-azure mb-1">WA Template</div>
                                @endif
                                @if($msg->type === 'image' && $isPreviewableMediaUrl)
                                    <div class="chat-media">
                                        <a href="{{ $messageMediaUrl }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ $messageMediaUrl }}" alt="{{ $msg->body ?: 'Image' }}">
                                        </a>
                                    </div>
                                @elseif($msg->type === 'video' && $isPreviewableMediaUrl)
                                    <div class="chat-media">
                                        <video controls preload="metadata">
                                            <source src="{{ $messageMediaUrl }}" type="{{ $msg->media_mime ?: 'video/mp4' }}">
                                        </video>
                                    </div>
                                @elseif($msg->type === 'audio' && $isPreviewableMediaUrl)
                                    <div class="chat-media">
                                        <audio controls preload="none">
                                            <source src="{{ $messageMediaUrl }}" type="{{ $msg->media_mime ?: 'audio/mpeg' }}">
                                        </audio>
                                    </div>
                                @elseif(in_array($msg->type, ['document', 'file'], true))
                                    <div class="chat-media">
                                        @if($isPreviewableMediaUrl)
                                            <a href="{{ $messageMediaUrl }}" target="_blank" rel="noopener noreferrer" class="chat-file-card">
                                                <span class="chat-file-icon">FILE</span>
                                                <span>
                                                    <span class="chat-file-name d-block">{{ $messageFilename ?: 'Document' }}</span>
                                                    <span class="chat-file-meta d-block">{{ $msg->media_mime ?: strtoupper($msg->type) }}</span>
                                                </span>
                                            </a>
                                        @else
                                            <div class="chat-file-card">
                                                <span class="chat-file-icon">FILE</span>
                                                <span>
                                                    <span class="chat-file-name d-block">{{ $messageFilename ?: 'Document' }}</span>
                                                    <span class="chat-file-meta d-block">{{ $msg->media_mime ?: strtoupper($msg->type) }}</span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if($msg->body)
                                    <div>{{ $msg->body }}</div>
                                @endif
                            </div>
                            <div class="chat-meta">{{ optional($msg->created_at)->format('d M H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No messages yet.</div>
                @endforelse
            </div>
            <div class="section-body pt-2">
                @unless($canReply)
                    <div class="alert alert-warning py-2 px-3 mb-3">
                        {{ $replyDisabledMessage }}
                    </div>
                @endunless
                <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mb-3" id="send-form">
                    @csrf
                    <div class="composer-shell d-flex align-items-center gap-2">
                        <input type="text" name="body" class="form-control" placeholder="Type a message..." required autocomplete="off" id="message-input" {{ $canReply ? '' : 'disabled' }}>
                        <button class="btn btn-primary" type="submit" {{ $canReply ? '' : 'disabled' }}>Send</button>
                    </div>
                    <div id="send-feedback" class="small mt-2 text-danger d-none"></div>
                </form>
                @if($conversation->channel === 'wa_api')
                    <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mb-3" id="media-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="message_type" value="media">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="file" name="media_file" class="form-control" required {{ $canReply ? '' : 'disabled' }}>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="body" class="form-control" placeholder="Caption (opsional)" {{ $canReply ? '' : 'disabled' }}>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button class="btn btn-outline-primary" type="submit" {{ $canReply ? '' : 'disabled' }}>Send File</button>
                            </div>
                        </div>
                    </form>
                @endif
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
                                    <select name="template_id" id="template_id" class="form-select" required {{ $canReply ? '' : 'disabled' }}>
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
                                    <button class="btn btn-success w-100" type="submit" {{ $canReply ? '' : 'disabled' }}>Kirim</button>
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
                @if($isWhatsAppConversation)
                    <div class="detail-row">
                        <span class="detail-key">Bot Mode</span>
                        <span class="detail-value">
                            @if($needsHuman)
                                <span class="badge text-bg-warning">Paused (Need Human)</span>
                                @if($handoffAt)
                                    <div class="text-muted small mt-1">{{ \Illuminate\Support\Carbon::parse($handoffAt)->diffForHumans() }}</div>
                                @endif
                            @else
                                <span class="badge text-bg-success">Active</span>
                            @endif
                        </span>
                    </div>
                @endif
                @if($isSocialConversation)
                    <div class="detail-row">
                        <span class="detail-key">Bot Mode</span>
                        <span class="detail-value">
                            @if($socialBotPaused)
                                <span class="badge text-bg-warning">Paused (Need Human)</span>
                            @else
                                <span class="badge text-bg-success">Active</span>
                            @endif
                        </span>
                    </div>
                @endif
                <div class="detail-row"><span class="detail-key">Last message</span><span class="detail-value" id="detail-last-message-time">{{ optional($conversation->last_message_at)->diffForHumans() ?? '-' }}</span></div>
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
    </div>
</div>
</div>

<div class="modal fade" id="start-conversation-modal" tabindex="-1" aria-labelledby="start-conversation-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('conversations.start') }}">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title fs-5 mb-0" id="start-conversation-modal-label">Start Conversation</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="start-user-picker" class="form-label">Select user</label>
                    <div class="user-search-wrap">
                        <input
                            type="text"
                            id="start-user-picker"
                            class="form-control"
                            placeholder="Search name or email..."
                            autocomplete="off"
                            required>
                        <div id="start-user-results" class="user-search-results"></div>
                    </div>
                    <input type="hidden" name="query" id="start-user-id" required>
                    <div id="start-user-invalid" class="text-danger small mt-2 d-none">Please select a user from the dropdown list.</div>
                    <div class="text-muted small mt-2">Type at least 2 characters to search users.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start</button>
                </div>
            </form>
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
        const detailLastMessageTime = document.getElementById('detail-last-message-time');
        const activeInboxPreview = document.querySelector('.conv-item.active .conv-item-preview');
        const activeConversationBadge = document.querySelector('.conv-item.active .badge');
        const sendForm = document.getElementById('send-form');
        const mediaForm = document.getElementById('media-form');
        const templateForm = document.getElementById('template-form');
        const messageInput = document.getElementById('message-input');
        const sendFeedback = document.getElementById('send-feedback');
        const mobileBackInbox = document.getElementById('mobile-back-inbox');
        const mobileOpenDetail = document.getElementById('mobile-open-detail');
        const mobileBackChat = document.getElementById('mobile-back-chat');
        const lockSpan = document.getElementById('lock-remaining');
        const lockedUntil = "{{ optional($conversation->locked_until)->toIso8601String() }}";
        const chatLoader = document.getElementById('chat-loader');
        const messagesEndpoint = "{{ route('conversations.messages', $conversation) }}";
        const messagesSinceEndpoint = "{{ route('conversations.messages.since', $conversation) }}";
        const markReadEndpoint = "{{ route('conversations.read', $conversation) }}";
        const conversationUrl = "{{ route('conversations.show', $conversation) }}";
        const csrfToken = @json(csrf_token());
        let oldestMessageId = @json($oldestMessageId);
        let latestMessageId = @json($latestMessageId);
        let hasMoreMessages = @json($hasMoreMessages);
        let loadingOlder = false;
        let pollingInFlight = false;
        const filterTabs = document.querySelectorAll('#conversation-filter-tabs [data-filter]');
        const conversationSearch = document.getElementById('conversation-search');
        const startUserForm = document.querySelector('#start-conversation-modal form');
        const startUserPicker = document.getElementById('start-user-picker');
        const startUserId = document.getElementById('start-user-id');
        const startUserResults = document.getElementById('start-user-results');
        const startUserInvalid = document.getElementById('start-user-invalid');
        const enableWebNotifBtn = document.getElementById('enable-web-notif-btn');
        const startUserSearchEndpoint = "{{ route('conversations.users.search') }}";
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
        let sendInFlight = false;
        let userSearchTimer = null;
        let userSearchController = null;

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
            const activeConversationItem = document.querySelector('.conv-item.active');
            if (activeConversationItem) {
                activeConversationItem.dataset.unreadCount = '0';
            }
            if (activeConversationBadge) {
                activeConversationBadge.classList.add('d-none');
                activeConversationBadge.textContent = '0';
            }
            refreshUnreadUi();
            syncReadToServer();
        };
        const setSendFeedback = (message = '', variant = 'danger') => {
            if (!sendFeedback) return;
            if (!message) {
                sendFeedback.classList.add('d-none');
                sendFeedback.textContent = '';
                sendFeedback.classList.remove('text-danger', 'text-success');
                return;
            }
            sendFeedback.textContent = message;
            sendFeedback.classList.remove('d-none', 'text-danger', 'text-success');
            sendFeedback.classList.add(variant === 'success' ? 'text-success' : 'text-danger');
        };
        const updateMessageRelatedUi = (msg) => {
            if (chatLastMessageTime) chatLastMessageTime.textContent = 'Last Message: just now';
            if (detailLastMessageTime) detailLastMessageTime.textContent = 'just now';
            if (activeInboxPreview) activeInboxPreview.textContent = (msg?.body || 'New message').toString();
        };
        const notifyIncoming = (name, body) => {
            const notifier = window.MyAppNotifier;
            if (!notifier || typeof notifier.show !== 'function') return;
            notifier.show(`New message from ${name}`, body, conversationUrl, `conv-${convId}`);
        };
        const refreshWebNotifButton = () => {
            const notifier = window.MyAppNotifier;
            if (!enableWebNotifBtn || !notifier || !notifier.supportsNotifications?.()) return;
            const currentPermission = notifier.permission?.() || 'denied';
            enableWebNotifBtn.classList.toggle('d-none', currentPermission !== 'default');
        };
        const initWebNotifButton = () => {
            refreshWebNotifButton();
            enableWebNotifBtn?.addEventListener('click', async () => {
                const notifier = window.MyAppNotifier;
                if (!notifier || typeof notifier.ensurePermission !== 'function') return;
                const granted = await notifier.ensurePermission(true);
                if (granted) {
                    enableWebNotifBtn.classList.add('d-none');
                } else {
                    refreshWebNotifButton();
                }
            });
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
        initWebNotifButton();
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

        const hideUserResults = () => {
            if (!startUserResults) return;
            startUserResults.classList.remove('show');
            startUserResults.innerHTML = '';
        };
        const renderUserResults = (items, query) => {
            if (!startUserResults) return;
            startUserResults.innerHTML = '';
            if (!query || query.length < 2) {
                startUserResults.innerHTML = '<div class="user-search-note">Type at least 2 characters.</div>';
                startUserResults.classList.add('show');
                return;
            }
            if (!items.length) {
                startUserResults.innerHTML = '<div class="user-search-note">No users found.</div>';
                startUserResults.classList.add('show');
                return;
            }
            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'user-search-item';
                btn.textContent = item.text;
                btn.dataset.userId = item.id;
                btn.addEventListener('click', () => {
                    if (startUserPicker) startUserPicker.value = item.text;
                    if (startUserId) startUserId.value = String(item.id);
                    if (startUserInvalid) startUserInvalid.classList.add('d-none');
                    hideUserResults();
                });
                startUserResults.appendChild(btn);
            });
            startUserResults.classList.add('show');
        };
        const searchUsersRemote = async (query) => {
            if (!startUserPicker || !startUserId || !startUserResults) return;
            startUserId.value = '';
            if (query.length < 2) {
                renderUserResults([], query);
                return;
            }
            if (userSearchController) userSearchController.abort();
            userSearchController = new AbortController();
            startUserResults.innerHTML = '<div class="user-search-note">Searching...</div>';
            startUserResults.classList.add('show');
            try {
                const url = `${startUserSearchEndpoint}?q=${encodeURIComponent(query)}&limit=15`;
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: userSearchController.signal,
                });
                if (!response.ok) throw new Error('search failed');
                const payload = await response.json();
                renderUserResults(Array.isArray(payload.items) ? payload.items : [], query);
            } catch (_) {
                startUserResults.innerHTML = '<div class="user-search-note">Failed to search users.</div>';
                startUserResults.classList.add('show');
            }
        };
        startUserPicker?.addEventListener('input', () => {
            if (startUserInvalid) startUserInvalid.classList.add('d-none');
            const query = (startUserPicker.value || '').trim();
            clearTimeout(userSearchTimer);
            userSearchTimer = setTimeout(() => searchUsersRemote(query), 250);
        });
        startUserPicker?.addEventListener('focus', () => {
            const query = (startUserPicker.value || '').trim();
            searchUsersRemote(query);
        });
        document.addEventListener('click', (e) => {
            if (!startUserResults || !startUserPicker) return;
            if (startUserResults.contains(e.target) || startUserPicker.contains(e.target)) return;
            hideUserResults();
        });
        startUserForm?.addEventListener('submit', (e) => {
            if (!startUserId?.value) {
                e.preventDefault();
                startUserPicker?.focus();
                if (startUserInvalid) startUserInvalid.classList.remove('d-none');
            }
        });

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

        const isPreviewableMediaUrl = (raw) => {
            const value = (raw || '').toString().trim();
            return value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/');
        };

        const buildMediaHtml = (msg) => {
            const type = (msg.type || '').toString().toLowerCase();
            const mediaUrl = (msg.media_url || '').toString();
            const mediaMime = (msg.media_mime || '').toString();
            const filename = (msg.filename || msg.body || `${type || 'file'}`).toString();

            if (!['image', 'video', 'audio', 'document', 'file'].includes(type)) {
                return '';
            }

            if (type === 'image' && isPreviewableMediaUrl(mediaUrl)) {
                return `<div class="chat-media"><a href="${escapeHtml(mediaUrl)}" target="_blank" rel="noopener noreferrer"><img src="${escapeHtml(mediaUrl)}" alt="${escapeHtml(msg.body || 'Image')}"></a></div>`;
            }

            if (type === 'video' && isPreviewableMediaUrl(mediaUrl)) {
                return `<div class="chat-media"><video controls preload="metadata"><source src="${escapeHtml(mediaUrl)}" type="${escapeHtml(mediaMime || 'video/mp4')}"></video></div>`;
            }

            if (type === 'audio' && isPreviewableMediaUrl(mediaUrl)) {
                return `<div class="chat-media"><audio controls preload="none"><source src="${escapeHtml(mediaUrl)}" type="${escapeHtml(mediaMime || 'audio/mpeg')}"></audio></div>`;
            }

            const fileInner = `
                <span class="chat-file-icon">FILE</span>
                <span>
                    <span class="chat-file-name d-block">${escapeHtml(filename || 'Document')}</span>
                    <span class="chat-file-meta d-block">${escapeHtml(mediaMime || type.toUpperCase())}</span>
                </span>`;

            if (isPreviewableMediaUrl(mediaUrl)) {
                return `<div class="chat-media"><a href="${escapeHtml(mediaUrl)}" target="_blank" rel="noopener noreferrer" class="chat-file-card">${fileInner}</a></div>`;
            }

            return `<div class="chat-media"><div class="chat-file-card">${fileInner}</div></div>`;
        };

        const buildMessageNode = (msg) => {
            const name = msg.user?.name ?? (msg.direction === 'out' ? 'You' : 'System');
            const state = `${msg.direction === 'out' ? 'Outgoing' : 'Incoming'}${msg.status ? ' | ' + msg.status : ''}`;
            const avatar = avatarUrl(msg.user?.avatar ?? '');
            const avatarHtml = avatar
                ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(name)}">`
                : `<span class="chat-avatar-fallback ${avatarTone(name)}">${escapeHtml(initials(name))}</span>`;
            const mediaHtml = buildMediaHtml(msg);
            const bodyHtml = msg.body ? `<div>${escapeHtml(msg.body)}</div>` : '';

            const wrapper = document.createElement('div');
            wrapper.className = 'chat-row chat-row-' + (msg.direction === 'out' ? 'out' : 'in') + ' d-flex align-items-end gap-2';
            wrapper.dataset.messageId = msg.id ?? '';
            wrapper.innerHTML = `
                <div class="chat-avatar">${avatarHtml}</div>
                <div class="chat-bubble ${msg.direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in'}">
                    <div class="chat-head d-flex align-items-center justify-content-between gap-2">
                        <span class="chat-sender">${escapeHtml(name)}</span>
                        <span class="chat-state">${escapeHtml(state)}</span>
                    </div>
                    <div class="chat-message-body">${mediaHtml}${bodyHtml}</div>
                    <div class="chat-meta">${msg.created_at ?? ''}</div>
                </div>`;
            return wrapper;
        };

        const appendIfNew = (msg, shouldScrollToBottom = false) => {
            const id = Number(msg.id);
            if (Number.isFinite(id) && renderedMessageIds.has(id)) return false;
            if (Number.isFinite(id) && id > 0) {
                renderedMessageIds.add(id);
                latestMessageId = Math.max(Number(latestMessageId || 0), id);
            }
            const wrapper = buildMessageNode(msg);
            chatPane?.appendChild(wrapper);
            if (shouldScrollToBottom && chatPane) {
                chatPane.scrollTop = chatPane.scrollHeight;
            }
            updateMessageRelatedUi(msg);
            return true;
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

        const pollLatestMessages = async () => {
            if (pollingInFlight) return;
            pollingInFlight = true;
            try {
                const url = `${messagesSinceEndpoint}?after_id=${encodeURIComponent(latestMessageId || 0)}&limit=20`;
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('poll failed');
                const payload = await response.json();
                const list = Array.isArray(payload.messages) ? payload.messages : [];
                if (list.length) {
                    const shouldStickBottom = chatPane ? ((chatPane.scrollHeight - chatPane.scrollTop - chatPane.clientHeight) < 80) : false;
                    list.forEach((msg) => {
                        const inserted = appendIfNew(msg, shouldStickBottom);
                        if (!inserted) return;
                        const incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
                        if (incomingOutOfView) {
                            unseenIncomingCount += 1;
                            sidebarUnreadCount += 1;
                            refreshUnreadUi();
                            const senderName = msg.user?.name ?? 'Contact';
                            notifyIncoming(senderName, msg.body ?? '');
                        } else if (msg.direction === 'in') {
                            clearUnread();
                        }
                    });
                }
                if (payload.latest_id) {
                    latestMessageId = Math.max(Number(latestMessageId || 0), Number(payload.latest_id || 0));
                }
            } catch (_) {
                // keep silent; polling is best-effort fallback
            } finally {
                pollingInFlight = false;
            }
        };
        setInterval(pollLatestMessages, 4000);

        const sendMessageForm = async (formEl) => {
            if (!formEl || sendInFlight) return;
            sendInFlight = true;
            setSendFeedback('');
            const submitBtn = formEl.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(formEl.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(formEl),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const message = payload?.message || payload?.errors?.body?.[0] || payload?.errors?.template_id?.[0] || payload?.errors?.media_file?.[0] || 'Failed to send message.';
                    setSendFeedback(message, 'danger');
                    return;
                }

                const msg = payload?.message;
                if (msg) {
                    appendIfNew(msg, true);
                }

                if (formEl === sendForm && messageInput) {
                    messageInput.value = '';
                    messageInput.focus();
                }
                if (formEl === templateForm) {
                    formEl.reset();
                }
                if (formEl === mediaForm) {
                    formEl.reset();
                }
            } catch (_) {
                setSendFeedback('Network error while sending message.', 'danger');
            } finally {
                sendInFlight = false;
                if (submitBtn) submitBtn.disabled = false;
            }
        };

        sendForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessageForm(sendForm);
        });
        templateForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessageForm(templateForm);
        });
        mediaForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessageForm(mediaForm);
        });

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
                    const shouldStickBottom = chatPane ? ((chatPane.scrollHeight - chatPane.scrollTop - chatPane.clientHeight) < 80) : true;
                    const inserted = appendIfNew(msg, shouldStickBottom);
                    if (!inserted) return;

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

    });
</script>
@endpush

