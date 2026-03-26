@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Email Inbox</h2>
        <div class="text-muted small">Mailbox operasional untuk baca email masuk dan kirim email keluar.</div>
    </div>
    <div class="btn-list">
        <a href="{{ route('email-inbox.accounts.index') }}" class="btn btn-outline-secondary">Kelola Account</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Mailbox</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($accounts as $account)
                    <a href="{{ route('email-inbox.show', $account) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $account->name }}</div>
                            <div class="text-muted small">{{ $account->email_address }}</div>
                        </div>
                        <span class="badge bg-azure-lt text-azure">{{ $account->unread_count }}</span>
                    </a>
                @empty
                    <div class="list-group-item text-muted">Belum ada mailbox account.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pesan Terbaru</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Mailbox</th>
                            <th>Subject</th>
                            <th>Pengirim / Penerima</th>
                            <th>Status</th>
                            <th class="text-end">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMessages as $message)
                            <tr>
                                <td>{{ $message->account?->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('email-inbox.message', [$message->account_id, $message]) }}" class="fw-semibold">
                                        {{ $message->subject ?: '(Tanpa subject)' }}
                                    </a>
                                </td>
                                <td>{{ $message->direction === 'inbound' ? ($message->from_email ?? '-') : collect($message->to_json)->pluck('email')->implode(', ') }}</td>
                                <td><span class="badge bg-secondary-lt text-secondary">{{ strtoupper($message->status) }}</span></td>
                                <td class="text-end text-muted small">{{ optional($message->received_at ?? $message->sent_at)->diffForHumans() ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada pesan email.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
