@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Conversations</h2>
        <div class="text-muted small">Inbox gabungan (internal, WhatsApp API/Bro, sosial). Claim eksklusif, auto-timeout {{ $lockMinutes }} menit.</div>
    </div>
    <a href="{{ route('whatsapp-api.inbox') ?? '#' }}" class="btn btn-outline-primary">Pengaturan Channel</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Kontak/Channel</th>
                    <th>Status</th>
                    <th>Owner</th>
                    <th>Last Msg</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                    <tr>
                        <td>
                            <a href="{{ route('conversations.show', $conv) }}" class="fw-bold text-decoration-none">{{ $conv->contact_name ?? $conv->contact_wa_id ?? 'Internal Chat' }}</a>
                            <div class="text-muted small">{{ strtoupper($conv->channel ?? 'internal') }}</div>
                        </td>
                        <td><span class="badge bg-{{ $conv->status === 'closed' ? 'secondary' : 'primary' }}">{{ ucfirst($conv->status) }}</span></td>
                        <td>{{ $conv->owner->name ?? 'Unassigned' }}</td>
                        <td>{{ optional($conv->last_message_at)->diffForHumans() ?? '-' }}</td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                @if($conv->owner_id === auth()->id())
                                    <form method="POST" action="{{ route('conversations.release', $conv) }}">
                                        @csrf
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Release</button>
                                    </form>
                                @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                    <form method="POST" action="{{ route('conversations.claim', $conv) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm" type="submit">Claim</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Locked sampai {{ optional($conv->locked_until)->format('H:i') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Belum ada percakapan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $conversations->links() }}</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Mulai Percakapan Internal</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('conversations.start') }}" class="d-flex gap-2">
            @csrf
            <input type="number" name="user_id" class="form-control" placeholder="User ID lain" required>
            <button class="btn btn-primary" type="submit">Start</button>
        </form>
        <div class="text-muted small mt-2">Masukkan user ID rekan untuk membuat percakapan baru.</div>
    </div>
</div>
@endsection
