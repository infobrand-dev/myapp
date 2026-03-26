@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $account->name }}</h2>
        <div class="text-muted small">{{ $account->email_address }} • sync {{ strtoupper($account->sync_status) }}</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('email-inbox.compose', $account) }}" class="btn btn-primary">Compose</a>
        <form method="POST" action="{{ route('email-inbox.accounts.sync', $account) }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Sync Inbox</button>
        </form>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="row row-cards">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Folders</h3></div>
            <div class="list-group list-group-flush">
                <a href="{{ route('email-inbox.show', $account) }}" class="list-group-item list-group-item-action {{ !$folderId ? 'active' : '' }}">Semua pesan</a>
                @foreach($folders as $folder)
                    <a href="{{ route('email-inbox.show', [$account, 'folder_id' => $folder->id]) }}" class="list-group-item list-group-item-action {{ (int) $folderId === (int) $folder->id ? 'active' : '' }}">
                        {{ $folder->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Sync Log</h3></div>
            <div class="list-group list-group-flush">
                @forelse($syncRuns as $run)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ strtoupper($run->status) }}</strong>
                            <span class="text-muted small">{{ optional($run->started_at)->diffForHumans() }}</span>
                        </div>
                        <div class="text-muted small">{{ $run->sync_type }}</div>
                        @if($run->error_message)
                            <div class="text-danger small mt-1">{{ $run->error_message }}</div>
                        @endif
                    </div>
                @empty
                    <div class="list-group-item text-muted">Belum ada log sync.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th class="text-end">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td>
                                    <a href="{{ route('email-inbox.message', [$account, $message]) }}" class="fw-semibold">
                                        {{ $message->subject ?: '(Tanpa subject)' }}
                                    </a>
                                    <div class="text-muted small">{{ strtoupper($message->direction) }}</div>
                                </td>
                                <td>{{ $message->direction === 'inbound' ? ($message->from_email ?? '-') : collect($message->to_json)->pluck('email')->implode(', ') }}</td>
                                <td>
                                    <span class="badge bg-secondary-lt text-secondary">{{ strtoupper($message->status) }}</span>
                                    @if(!$message->is_read && $message->direction === 'inbound')
                                        <span class="badge bg-azure-lt text-azure">UNREAD</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted small">{{ optional($message->received_at ?? $message->sent_at)->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada pesan untuk mailbox ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $messages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
