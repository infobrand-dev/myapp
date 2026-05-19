@extends('layouts.admin')

@section('title', 'Live Chat Inbox')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Live Chat</div>
            <h2 class="page-title">Inbox</h2>
            <p class="text-muted mb-0">Percakapan live chat yang masuk dari website.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('live-chat.widgets.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>Kelola Widgets
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="inbox-filter-form" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" data-auto-submit>
                    <option value="all"    @selected($status === 'all')>Semua</option>
                    <option value="open"   @selected($status === 'open')>Open</option>
                    <option value="closed" @selected($status === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Widget</label>
                <select name="widget_id" class="form-select" data-auto-submit>
                    <option value="">Semua Widget</option>
                    @foreach($widgets as $widget)
                        <option value="{{ $widget->id }}" @selected((string) $widgetId === (string) $widget->id)>{{ $widget->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('live-chat.inbox.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
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
                            <td>
                                <span class="badge bg-azure-lt text-azure">{{ $conversation->instance_id }}</span>
                            </td>
                            <td>
                                @if($conversation->status === 'open')
                                    <span class="badge bg-green-lt text-green">Open</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">{{ ucfirst($conversation->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $conversation->owner?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $conversation->last_message_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('live-chat.inbox.show', $conversation) }}"
                                       class="btn btn-icon btn-sm btn-outline-secondary"
                                       title="Buka Percakapan">
                                        <i class="ti ti-message-circle"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-message-chatbot text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada percakapan live chat.</div>
                                @if(!$widgets->isEmpty())
                                    <div class="text-muted small">Pasang widget di website Anda untuk mulai menerima pesan.</div>
                                @else
                                    <a href="{{ route('live-chat.widgets.create') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus me-1"></i>Buat Widget Dulu
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $conversations->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
        el.addEventListener('change', function () {
            document.getElementById('inbox-filter-form').submit();
        });
    });
});
</script>
@endpush
