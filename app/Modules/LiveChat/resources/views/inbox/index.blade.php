@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Live Chat Inbox</h2>
        <div class="text-muted small">Percakapan live chat yang masuk dari website.</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all" @selected($status === 'all')>Semua</option>
                    <option value="open" @selected($status === 'open')>Open</option>
                    <option value="closed" @selected($status === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Widget</label>
                <select name="widget_id" class="form-select">
                    <option value="">Semua Widget</option>
                    @foreach($widgets as $widget)
                        <option value="{{ $widget->id }}" @selected((string) $widgetId === (string) $widget->id)>{{ $widget->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-outline-primary me-2">Filter</button>
                <a href="{{ route('live-chat.inbox.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Widget</th>
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
                            <div class="fw-semibold">{{ $conversation->contact_name ?: 'Website Visitor' }}</div>
                            <div class="text-muted small">{{ $conversation->contact_external_id }}</div>
                        </td>
                        <td>{{ $conversation->instance_id }}</td>
                        <td>
                            @if($conversation->status === 'open')
                                <span class="badge bg-success-lt">Open</span>
                            @else
                                <span class="badge bg-secondary-lt">{{ ucfirst($conversation->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $conversation->owner?->name ?? '-' }}</td>
                        <td>{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('live-chat.inbox.show', $conversation) }}" class="btn btn-sm btn-outline-primary">Buka</a>
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
