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
