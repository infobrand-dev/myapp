@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $discount->internal_name }}</h2>
        <div class="text-muted small">{{ $discount->public_label ?: 'Tanpa public label' }} @if($discount->code) | Code: {{ $discount->code }} @endif</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('discounts.edit', $discount) }}" class="btn btn-outline-secondary">Edit</a>
        <a href="{{ route('discounts.usages.index', ['discount_id' => $discount->id]) }}" class="btn btn-primary">Lihat Usage</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Summary</h3></div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted">Type:</span> {{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}</div>
                <div class="mb-2"><span class="text-muted">Scope:</span> {{ ucfirst($discount->application_scope) }}</div>
                <div class="mb-2"><span class="text-muted">Priority / Sequence:</span> {{ $discount->priority }} / {{ $discount->sequence }}</div>
                <div class="mb-2"><span class="text-muted">Status:</span> {{ ucfirst($discount->status_view) }}</div>
                <div class="mb-2"><span class="text-muted">Voucher Required:</span> {{ $discount->is_voucher_required ? 'Yes' : 'No' }}</div>
                <div class="mb-2"><span class="text-muted">Manual Only:</span> {{ $discount->is_manual_only ? 'Yes' : 'No' }}</div>
                <div><span class="text-muted">Rule Payload:</span><pre class="small bg-light p-2 rounded mt-1 mb-0">{{ json_encode($discount->rule_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Targets</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead><tr><th>Type</th><th>Reference</th><th>Operator</th></tr></thead>
                    <tbody>
                        @forelse($discount->targets as $target)
                            <tr>
                                <td>{{ $target->target_type }}</td>
                                <td>{{ $target->target_id ?? $target->target_code ?? 'all' }}</td>
                                <td>{{ $target->operator }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Semua item / context eligible.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Conditions</h3></div>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead><tr><th>Condition</th><th>Value</th><th>Payload</th></tr></thead>
                    <tbody>
                        @forelse($discount->conditions as $condition)
                            <tr>
                                <td>{{ $condition->condition_type }}</td>
                                <td>{{ $condition->value ?? '-' }}</td>
                                <td><code>{{ json_encode($condition->payload) }}</code></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Tidak ada condition tambahan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Vouchers & Usage</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small mb-2">Voucher / Promo Code</div>
                        @forelse($discount->vouchers as $voucher)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $voucher->code }}</div>
                                <div class="text-muted small">{{ $voucher->description ?: 'Tanpa deskripsi' }}</div>
                            </div>
                        @empty
                            <div class="text-muted">Tidak ada voucher.</div>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-2">Recent Usage</div>
                        @forelse($discount->usages->take(5) as $usage)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $usage->usage_reference_type ?: 'manual' }} #{{ $usage->usage_reference_id ?: '-' }}</div>
                                <div class="text-muted small">{{ $usage->usage_status }} | {{ $usage->applied_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                        @empty
                            <div class="text-muted">Belum ada usage.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
