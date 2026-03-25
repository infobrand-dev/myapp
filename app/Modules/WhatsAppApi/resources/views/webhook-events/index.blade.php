@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp Webhook Events</h2>
        <div class="text-muted small">Pantau webhook gagal, signature issue, dan reprocess event yang aman.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('whatsapp-api.logs.index') }}" class="btn btn-outline-secondary">Message Logs</a>
        <a href="{{ route('whatsapp-api.inbox') }}" class="btn btn-outline-secondary">Kembali ke Inbox</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Failed</div><div class="h2 mb-0">{{ $summary['failed'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Ignored</div><div class="h2 mb-0">{{ $summary['ignored'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Pending</div><div class="h2 mb-0">{{ $summary['pending'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Invalid Signature</div><div class="h2 mb-0">{{ $summary['invalid_signature'] }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Retryable Queue</div><div class="h2 mb-0 text-warning">{{ $summary['retryable'] }}</div></div></div>
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
                        <option value="{{ $inst->id }}" {{ (string) ($filters['instance_id'] ?? '') === (string) $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['cloud', 'gateway'] as $provider)
                        <option value="{{ $provider }}" {{ ($filters['provider'] ?? '') === $provider ? 'selected' : '' }}>{{ strtoupper($provider) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="process_status" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['pending', 'processed', 'ignored', 'failed'] as $status)
                        <option value="{{ $status }}" {{ ($filters['process_status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Signature</label>
                <select name="signature_valid" class="form-select">
                    <option value="">Semua</option>
                    <option value="1" {{ ($filters['signature_valid'] ?? '') === '1' ? 'selected' : '' }}>Valid</option>
                    <option value="0" {{ ($filters['signature_valid'] ?? '') === '0' ? 'selected' : '' }}>Invalid</option>
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
            <div class="col-lg-1 col-md-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
            <div class="col-lg-12 d-flex gap-2">
                <a href="{{ route('whatsapp-api.webhook-events.index') }}" class="btn btn-outline-secondary">Reset</a>
                <a href="{{ route('whatsapp-api.webhook-events.index', ['process_status' => 'failed']) }}" class="btn btn-outline-danger">Failed Only</a>
                <a href="{{ route('whatsapp-api.webhook-events.index', ['signature_valid' => '0']) }}" class="btn btn-outline-warning">Invalid Signature</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Received</th>
                    <th>Instance</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Signature</th>
                    <th>Retry</th>
                    <th>Error</th>
                    <th class="w-1">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    @php
                        $statusClass = match($event->process_status) {
                            'processed' => 'text-bg-green',
                            'ignored' => 'text-bg-yellow',
                            'failed' => 'text-bg-red',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            <div>{{ optional($event->received_at)->format('d M Y H:i:s') ?: '-' }}</div>
                            <div class="text-muted small">ID #{{ $event->id }}</div>
                        </td>
                        <td>{{ $event->instance?->name ?? '-' }}</td>
                        <td><span class="badge bg-azure-lt text-azure">{{ strtoupper((string) $event->provider) }}</span></td>
                        <td><span class="badge {{ $statusClass }}">{{ ucfirst((string) $event->process_status) }}</span></td>
                        <td>
                            @if($event->signature_valid === true)
                                <span class="badge bg-green-lt text-green">Valid</span>
                            @elseif($event->signature_valid === false)
                                <span class="badge bg-red-lt text-red">Invalid</span>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ (int) $event->retry_count }}</td>
                        <td class="text-danger small">{{ \Illuminate\Support\Str::limit((string) $event->error_message, 120) ?: '-' }}</td>
                        <td class="text-end align-middle">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="modal" data-bs-target="#eventDetail{{ $event->id }}" title="Detail" aria-label="Detail">
                                <i class="ti ti-eye icon"></i>
                            </button>
                            @if($event->canReprocess())
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.webhook-events.reprocess', $event) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary btn-icon" title="Reprocess" aria-label="Reprocess" data-confirm="Reprocess webhook event ini?">
                                        <i class="ti ti-refresh icon"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Belum ada webhook event.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $events->links() }}</div>

@foreach($events as $event)
    <div class="modal fade" id="eventDetail{{ $event->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content text-start">
                <div class="modal-header">
                    <h3 class="modal-title">Webhook Event #{{ $event->id }}</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="text-muted small">Provider</div>
                            <div class="fw-bold">{{ strtoupper((string) $event->provider) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Status</div>
                            <div class="fw-bold">{{ ucfirst((string) $event->process_status) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Signature</div>
                            <div class="fw-bold">{{ $event->signature_valid === null ? 'N/A' : ($event->signature_valid ? 'Valid' : 'Invalid') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Received</div>
                            <div class="fw-bold">{{ optional($event->received_at)->format('d M Y H:i:s') ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Error Message</label>
                        <textarea class="form-control" rows="3" readonly>{{ (string) $event->error_message }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Headers</label>
                        <textarea class="form-control font-monospace" rows="8" readonly>{{ json_encode($event->headers ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Payload</label>
                        <textarea class="form-control font-monospace" rows="18" readonly>{{ json_encode($event->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
