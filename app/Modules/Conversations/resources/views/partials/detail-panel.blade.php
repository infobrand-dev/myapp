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
        @if($conversation->channel === 'live_chat')
            <div class="detail-row">
                <span class="detail-key">Live Chat</span>
                <span class="detail-value">
                    <span class="badge {{ $conversation->status === 'closed' ? 'text-bg-secondary' : 'text-bg-success' }}">
                        {{ $conversation->status === 'closed' ? 'Closed' : 'Open' }}
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Actions</span>
                <span class="detail-value">
                    @if($conversation->status === 'closed')
                        <form method="POST" action="{{ route('conversations.reopen', $conversation) }}" class="d-inline-block m-0">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">Reopen</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('conversations.close', $conversation) }}" class="d-inline-block m-0">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Close</button>
                        </form>
                    @endif
                </span>
            </div>
        @endif
        <div class="detail-row"><span class="detail-key">Owner</span><span class="detail-value" id="detail-owner-name">{{ $conversation->owner->name ?? 'Unassigned' }}</span></div>
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
        @foreach($hooks->render('conversations.show.detail_rows', ['conversation' => $conversation, 'canReply' => $canReply]) as $hookedDetailRow)
            {!! $hookedDetailRow !!}
        @endforeach
    </div>
</div>

@if($conversation->channel === 'live_chat')
    <div class="conv-surface mb-3">
        <div class="section-head"><h3 class="conv-section-title">Assignment</h3></div>
        <div class="section-body">
            <div class="detail-list pt-0">
                <div class="detail-row detail-row-stack">
                    <span class="detail-key">Workflow</span>
                    <div class="detail-value detail-value-detail">
                        <div id="livechat-assignment-status" class="fw-semibold">
                            @if($isOwner)
                                Conversation ini sedang Anda tangani.
                            @elseif($lockExpired)
                                Conversation ini belum di-assign.
                            @else
                                Conversation ini sedang dipegang {{ $conversation->owner->name ?? 'agent lain' }}.
                            @endif
                        </div>
                        <div class="assignment-note mt-1" id="livechat-assignment-note">
                            @if($isOwner)
                                Anda bisa invite anggota lain bila perlu kolaborasi atau release jika ingin melepaskan ownership.
                            @elseif($lockExpired)
                                Claim conversation dulu agar ownership dan respon tetap rapi sebelum membalas visitor.
                            @else
                                Tunggu lock berakhir, minta owner release, atau kolaborasi sebagai participant jika sudah diundang.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Lock</span>
                    <span class="detail-value" id="livechat-assignment-lock">
                        @if($lockExpired)
                            Available to claim
                        @else
                            {{ optional($conversation->locked_until)->diffForHumans() ?? 'Locked' }}
                        @endif
                    </span>
                </div>
            </div>
            <div class="d-grid gap-2 mt-3">
                @if($isOwner)
                    <form method="POST" action="{{ route('conversations.release', $conversation) }}" class="m-0">
                        @csrf
                        <button class="btn btn-outline-secondary w-100" type="submit">Release Conversation</button>
                    </form>
                @elseif($lockExpired)
                    <form method="POST" action="{{ route('conversations.claim', $conversation) }}" class="m-0">
                        @csrf
                        <button class="btn btn-primary w-100" type="submit">Claim Conversation</button>
                    </form>
                @else
                    <div class="text-muted small">Conversation sedang terkunci pada owner aktif.</div>
                @endif
            </div>
        </div>
    </div>
@endif
