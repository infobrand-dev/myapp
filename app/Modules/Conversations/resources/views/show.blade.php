@extends('layouts.admin')

@section('content')
@php
    $conversationMeta = is_array($conversation->metadata) ? $conversation->metadata : [];
    $botPaused = (bool) ($conversationMeta['auto_reply_paused'] ?? false);
    $needsHuman = (bool) ($conversationMeta['needs_human'] ?? false);
    $handoffAt = $conversationMeta['handoff_at'] ?? null;
    $isOwner = (int) ($conversation->owner_id ?? 0) === (int) auth()->id();
    $isParticipant = $conversation->participants->contains(fn ($participant) => (int) $participant->user_id === (int) auth()->id());
    $isSuperAdmin = auth()->user()->hasRole('Super-admin');
    $canReply = $isOwner || $isParticipant || $isSuperAdmin;
    $replyDisabledMessage = 'Claim conversation atau minta owner mengundang Anda sebagai participant untuk membalas.';
    $hooks = app(\App\Support\HookManager::class);
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
    .conv-dashboard .chat-message-text {
        white-space: normal;
        word-break: break-word;
        line-height: 1.45;
    }
    .conv-dashboard .chat-message-text strong {
        font-weight: 700;
    }
    .conv-dashboard .chat-message-text em {
        font-style: italic;
    }
    .conv-dashboard .chat-message-text s {
        text-decoration: line-through;
    }
    .conv-dashboard .chat-message-text code {
        font-family: var(--tblr-font-monospace, monospace);
        background: rgba(15, 23, 42, 0.06);
        padding: 0 .18rem;
        border-radius: .2rem;
        color: inherit;
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
    .conv-dashboard .composer-plus-btn {
        width: 2.65rem;
        height: 2.65rem;
        border-radius: .7rem !important;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .conv-dashboard .composer-action-menu {
        min-width: 14rem;
        padding: .45rem;
        border-radius: .85rem;
        border-color: var(--conv-border-soft);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    }
    .conv-dashboard .composer-action-menu .dropdown-item {
        border-radius: .65rem;
        padding: .6rem .72rem;
        font-weight: 600;
        color: #334a62;
    }
    .conv-dashboard .composer-action-menu .dropdown-item i {
        font-size: 1rem;
        margin-right: .55rem;
        color: #58728f;
    }
    .conv-dashboard .media-upload-panel {
        border: 1px solid var(--conv-border-soft);
        border-radius: .82rem;
        padding: .8rem;
        background: linear-gradient(180deg, rgba(32, 107, 196, 0.04), rgba(255, 255, 255, 0.98));
    }
    .conv-dashboard .media-upload-title {
        font-size: .84rem;
        font-weight: 700;
        color: #2d4f77;
        margin-bottom: .12rem;
    }
    .conv-dashboard .media-upload-file {
        font-size: .8rem;
        color: var(--conv-muted);
        word-break: break-word;
    }
    .conv-dashboard .media-upload-actions {
        display: flex;
        justify-content: flex-end;
        gap: .55rem;
        margin-top: .7rem;
    }
    .conv-dashboard .media-upload-trigger {
        border-radius: .62rem;
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
    .conv-dashboard .detail-list .detail-row-stack {
        display: block;
    }
    .conv-dashboard .detail-list .detail-row-stack .detail-key {
        display: block;
        margin-bottom: .35rem;
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
    .conv-dashboard .detail-value-detail {
        width: 100%;
        text-align: left;
    }
    .conv-dashboard .detail-inline-form textarea {
        min-height: 7rem;
        resize: vertical;
    }
    .conv-dashboard .detail-action-group {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .conv-dashboard .detail-action-btn {
        width: 2.45rem;
        height: 2.45rem;
        border-radius: .7rem;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .conv-dashboard .detail-action-btn i {
        font-size: 1.1rem;
        line-height: 1;
    }
    .conv-dashboard .detail-collapse-panel {
        margin-top: .55rem;
        padding-top: .65rem;
        border-top: 1px solid rgba(74, 96, 126, 0.08);
    }
    .conv-dashboard .detail-inline-form {
        width: 100%;
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
        .conv-dashboard .composer-action-menu {
            min-width: 12.25rem;
        }
        .conv-dashboard .media-upload-panel {
            padding: .72rem;
        }
        .conv-dashboard .media-upload-actions {
            flex-direction: column-reverse;
        }
        .conv-dashboard .media-upload-actions .btn {
            width: 100%;
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
        @foreach($hooks->render('conversations.show.actions', ['conversation' => $conversation, 'user' => auth()->user()]) as $hookedAction)
            {!! $hookedAction !!}
        @endforeach
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
                            'wa_api', 'wa_web', 'wa_bro', 'whatsapp' => 'ti ti-brand-whatsapp',
                            'social_dm', 'social' => 'ti ti-brand-messenger',
                            'internal' => 'ti ti-user',
                            default => 'ti ti-message',
                        };
                        $channelAccent = match($channel) {
                            'wa_api', 'wa_web', 'wa_bro', 'whatsapp' => 'channel-whatsapp',
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
                <div id="chat-history-sentinel" class="h-0"></div>
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
                        @if($channelUi['show_media_composer'])
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary composer-plus-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" {{ $canReply ? '' : 'disabled' }}>
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu composer-action-menu">
                                    <button type="button" class="dropdown-item" data-media-picker="file" {{ $canReply ? '' : 'disabled' }}>
                                        <i class="ti ti-file-text" aria-hidden="true"></i>Kirim file
                                    </button>
                                    <button type="button" class="dropdown-item" data-media-picker="image" {{ $canReply ? '' : 'disabled' }}>
                                        <i class="ti ti-photo" aria-hidden="true"></i>Kirim image
                                    </button>
                                    <button type="button" class="dropdown-item" data-media-picker="video" {{ $canReply ? '' : 'disabled' }}>
                                        <i class="ti ti-video" aria-hidden="true"></i>Kirim video
                                    </button>
                                </div>
                            </div>
                        @endif
                        <input type="text" name="body" class="form-control" placeholder="Type a message..." required autocomplete="off" id="message-input" {{ $canReply ? '' : 'disabled' }}>
                        <button class="btn btn-primary" type="submit" {{ $canReply ? '' : 'disabled' }}>Send</button>
                    </div>
                    <div id="send-feedback" class="small mt-2 text-danger d-none"></div>
                </form>
                @if($channelUi['show_media_composer'])
                    <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mb-3 d-none" id="media-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="message_type" value="media">
                        <input type="file" name="media_file" id="media-file-input" class="d-none" required {{ $canReply ? '' : 'disabled' }}>
                        <div class="media-upload-panel" id="media-upload-panel">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="min-w-0">
                                    <div class="media-upload-title" id="media-upload-title">Pilih media</div>
                                    <div class="media-upload-file" id="media-upload-file">Belum ada file dipilih.</div>
                                </div>
                                <button type="button" class="btn-close" id="media-upload-cancel" aria-label="Cancel"></button>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="text" name="body" id="media-caption-input" class="form-control" placeholder="Caption (opsional)" {{ $canReply ? '' : 'disabled' }}>
                                </div>
                            </div>
                            <div class="media-upload-actions">
                                <button type="button" class="btn btn-outline-secondary media-upload-trigger" id="media-upload-change" {{ $canReply ? '' : 'disabled' }}>Pilih ulang</button>
                                <button class="btn btn-outline-primary" type="submit" id="media-upload-submit" {{ $canReply ? '' : 'disabled' }}>Kirim media</button>
                            </div>
                        </div>
                    </form>
                @endif
                @if($channelUi['show_template_composer'] && $waTemplates->isNotEmpty())
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
                @if($channelUi['show_contact_crm'] && !empty($conversation->contact_external_id))
                    <div class="detail-row">
                        <span class="detail-key">Contact CRM</span>
                        <span class="detail-value">
                            <span class="detail-action-group">
                            @if(!empty($relatedContact))
                                <a href="{{ route('contacts.edit', $relatedContact) }}" class="btn btn-sm btn-outline-primary detail-action-btn" title="Open Contact" aria-label="Open Contact">
                                    <i class="ti ti-address-book" aria-hidden="true"></i>
                                </a>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary detail-action-btn"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#contact-note-panel"
                                    aria-expanded="false"
                                    aria-controls="contact-note-panel"
                                    title="Open Note"
                                    aria-label="Open Note"
                                >
                                    <i class="ti ti-notebook" aria-hidden="true"></i>
                                </button>
                            @elseif(Route::has('contacts.create'))
                                <a
                                    href="{{ route('contacts.create', [
                                        'type' => 'individual',
                                        'name' => $conversation->contact_name,
                                        'mobile' => $conversation->contact_external_id,
                                        'phone' => $conversation->contact_external_id,
                                        'notes' => 'Created from conversation #' . $conversation->id,
                                    ]) }}"
                                    class="btn btn-sm btn-outline-success detail-action-btn"
                                    title="Add Contact"
                                    aria-label="Add Contact"
                                >
                                    <i class="ti ti-user-plus" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="text-muted">Contacts module not available.</span>
                            @endif
                            </span>
                        </span>
                    </div>
                @endif
                @if(!empty($relatedContact))
                    <div class="detail-row detail-row-stack">
                        <span class="detail-key">Contact Notes</span>
                        <div class="detail-value detail-value-detail">
                            <div class="collapse" id="contact-note-panel">
                                <div class="detail-collapse-panel">
                                    <form method="POST" action="{{ route('conversations.contact-note.update', $conversation) }}" class="detail-inline-form">
                                        @csrf
                                        <textarea name="notes" class="form-control form-control-sm mb-2" {{ $canReply ? '' : 'disabled' }}>{{ old('notes', $relatedContact->notes ?? '') }}</textarea>
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $canReply ? '' : 'disabled' }}>Save Note</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="detail-row"><span class="detail-key">Owner</span><span class="detail-value">{{ $conversation->owner->name ?? 'Unassigned' }}</span></div>
                <div class="detail-row"><span class="detail-key">Status</span><span class="detail-value">{{ ucfirst($conversation->status) }}</span></div>
                @if($channelUi['show_ai_bot'])
                    <div class="detail-row">
                        <span class="detail-key">AI Bot</span>
                        <span class="detail-value">
                            @if($needsHuman)
                                <span class="badge text-bg-warning">Paused (Need Human)</span>
                                @if($handoffAt)
                                    <div class="text-muted small mt-1">{{ \Illuminate\Support\Carbon::parse($handoffAt)->diffForHumans() }}</div>
                                @endif
                            @elseif($botPaused)
                                <span class="badge text-bg-warning">Paused</span>
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

@php
    $conversationShowConfig = [
        'convId' => $conversation->id,
        'lockedUntil' => optional($conversation->locked_until)->toIso8601String(),
        'messagesEndpoint' => route('conversations.messages', $conversation),
        'messagesSinceEndpoint' => route('conversations.messages.since', $conversation),
        'markReadEndpoint' => route('conversations.read', $conversation),
        'conversationUrl' => route('conversations.show', $conversation),
        'csrfToken' => csrf_token(),
        'oldestMessageId' => $oldestMessageId,
        'latestMessageId' => $latestMessageId,
        'hasMoreMessages' => $hasMoreMessages,
        'startUserSearchEndpoint' => route('conversations.users.search'),
    ];
@endphp

@push('scripts')
<script id="conversation-show-config" type="application/json">{{ \Illuminate\Support\Js::from($conversationShowConfig) }}</script>
<script src="{{ mix('js/modules/conversations/show.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatPane = document.getElementById('chat-pane');

    if (!chatPane) {
        return;
    }

    const forceConversationBottom = () => {
        const rows = chatPane.querySelectorAll('.chat-row[data-message-id]');
        const lastRow = rows.length ? rows[rows.length - 1] : null;

        if (lastRow) {
            lastRow.scrollIntoView({
                block: 'end',
                behavior: 'auto',
            });
        }

        chatPane.scrollTop = chatPane.scrollHeight;
    };

    const scheduleForceBottom = () => {
        requestAnimationFrame(() => {
            forceConversationBottom();
            requestAnimationFrame(forceConversationBottom);
        });
    };

    scheduleForceBottom();
    setTimeout(scheduleForceBottom, 60);
    setTimeout(scheduleForceBottom, 180);
    setTimeout(scheduleForceBottom, 360);

    window.addEventListener('load', scheduleForceBottom);
    window.addEventListener('pageshow', scheduleForceBottom);
    window.addEventListener('resize', scheduleForceBottom);

    chatPane.querySelectorAll('img, video').forEach((media) => {
        media.addEventListener('load', scheduleForceBottom, { once: true });
        media.addEventListener('loadedmetadata', scheduleForceBottom, { once: true });
    });
});
</script>
@endpush


