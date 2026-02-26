@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp API Message Logs</h2>
        <div class="text-muted small">Audit trail kirim/terima pesan untuk troubleshooting.</div>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('whatsapp-api.logs.retry-failed') }}" onsubmit="return confirm('Retry semua pesan gagal sesuai filter aktif? (maks 200 pesan per aksi)');">
            @csrf
            <input type="hidden" name="instance_id" value="{{ $filters['instance_id'] ?? '' }}">
            <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <button type="submit" class="btn btn-outline-primary">Retry Failed</button>
        </form>
        <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">Kembali ke Inbox</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Instance</label>
                <select name="instance_id" class="form-select">
                    <option value="">Semua instance</option>
                    @foreach($instances as $inst)
                        <option value="{{ $inst->id }}" {{ (string) ($filters['instance_id'] ?? '') === (string) $inst->id ? 'selected' : '' }}>
                            {{ $inst->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['queued','sent','delivered','read','error'] as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Direction</label>
                <select name="direction" class="form-select">
                    <option value="">Semua</option>
                    <option value="in" {{ ($filters['direction'] ?? '') === 'in' ? 'selected' : '' }}>Incoming</option>
                    <option value="out" {{ ($filters['direction'] ?? '') === 'out' ? 'selected' : '' }}>Outgoing</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-lg-1 col-md-6 d-grid">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Instance</th>
                    <th>Arah</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>WA Msg ID</th>
                    <th>Isi</th>
                    <th>Error</th>
                    <th class="w-1">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                    @php
                        $statusClass = match($msg->status) {
                            'sent' => 'text-bg-blue',
                            'delivered' => 'text-bg-green',
                            'read' => 'text-bg-teal',
                            'error' => 'text-bg-red',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ optional($msg->created_at)->format('d M Y H:i:s') }}</td>
                        <td>{{ $msg->conversation?->instance?->name ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $msg->direction === 'in' ? 'bg-azure-lt text-azure' : 'bg-indigo-lt text-indigo' }}">
                                {{ $msg->direction === 'in' ? 'Incoming' : 'Outgoing' }}
                            </span>
                        </td>
                        <td>{{ strtoupper((string) $msg->type) }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ ucfirst((string) $msg->status) }}</span></td>
                        <td class="text-muted small">{{ $msg->wa_message_id ?: '-' }}</td>
                        <td class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $msg->body, 90) }}</td>
                        <td class="text-danger small">{{ \Illuminate\Support\Str::limit((string) $msg->error_message, 90) }}</td>
                        <td class="text-end align-middle">
                            @if($msg->direction === 'out' && (string) $msg->status === 'error')
                                <div class="table-actions">
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.logs.requeue', $msg) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary btn-icon" title="Requeue" aria-label="Requeue">
                                            <i class="ti ti-refresh icon"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-muted">Belum ada log pesan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $messages->links() }}</div>
@endsection
