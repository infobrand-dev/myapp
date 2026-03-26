@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Social Media Inbox</h2>
        <div class="text-muted small">Instagram / Facebook DM conversations.</div>
    </div>
    <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">Manage Accounts</a>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Terakhir Pesan</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conversation)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $conversation->contact_name ?: 'Unknown' }}</div>
                            <div class="text-muted small">{{ $conversation->contact_external_id }}</div>
                        </td>
                        <td>{{ $conversation->metadata['platform'] ?? '-' }}</td>
                        <td>
                            @if($conversation->status === 'open')
                                <span class="badge bg-success-lt">Open</span>
                            @else
                                <span class="badge bg-secondary-lt">{{ ucfirst($conversation->status) }}</span>
                            @endif
                            @if(!empty($conversation->metadata['auto_reply_paused']))
                                <span class="badge bg-warning-lt ms-1">Bot Paused</span>
                            @endif
                        </td>
                        <td>{{ $conversation->owner?->name ?? '-' }}</td>
                        <td>{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('social-media.conversations.show', $conversation) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada percakapan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $conversations->links() }}</div>
</div>
@endsection
