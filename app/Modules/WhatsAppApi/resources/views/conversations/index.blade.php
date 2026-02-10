@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Inbox WhatsApp API</h2>
        <div class="text-muted small">Claim percakapan eksklusif (auto-timeout {{ $lockMinutes }} menit). Hanya Super-admin yang dapat atur instance.</div>
    </div>
    <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-primary">Kelola Instance</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-secondary text-uppercase fw-bold small">Filter Instance</div>
                <div class="text-muted small">Menampilkan percakapan dari instance yang Anda punya akses.</div>
            </div>
            <div class="col-md-9">
                <div class="d-flex flex-wrap gap-2">
                    @forelse($instances as $inst)
                        <span class="badge bg-azure-lt text-azure">{{ $inst->name }}</span>
                    @empty
                        <span class="text-muted">Belum ada akses instance.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Kontak</th>
                    <th>Instance</th>
                    <th>Status</th>
                    <th>Owner</th>
                    <th>Last Message</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $conv->contact_name ?? $conv->contact_wa_id }}</div>
                            <div class="text-muted small">{{ $conv->contact_wa_id }}</div>
                        </td>
                        <td>{{ $conv->instance->name }}</td>
                        <td><span class="badge bg-{{ $conv->status === 'closed' ? 'secondary' : 'primary' }}">{{ ucfirst($conv->status) }}</span></td>
                        <td>{{ $conv->owner?->name ?? 'Unassigned' }}</td>
                        <td>{{ optional($conv->last_message_at)->diffForHumans() ?? '—' }}</td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                @if($conv->owner_id === auth()->id())
                                    <form method="POST" action="{{ route('whatsapp-api.conversations.release', $conv) }}">
                                        @csrf
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Release</button>
                                    </form>
                                @elseif(!$conv->owner_id || optional($conv->locked_until)->isPast())
                                    <form method="POST" action="{{ route('whatsapp-api.conversations.claim', $conv) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm" type="submit">Claim</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Locked sampai {{ optional($conv->locked_until)->format('H:i') }}</span>
                                @endif

                                @if($conv->owner_id === auth()->id())
                                    <form method="POST" action="{{ route('whatsapp-api.conversations.invite', $conv) }}" class="d-flex align-items-center gap-1">
                                        @csrf
                                        <input type="number" name="user_id" class="form-control form-control-sm" placeholder="User ID" style="width:100px;">
                                        <button class="btn btn-outline-primary btn-sm" type="submit">Invite</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada percakapan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $conversations->links() }}</div>
@endsection
