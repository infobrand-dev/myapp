@extends('layouts.tenant')

@section('title', $discount->internal_name)

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Penjualan · Discounts</div>
                <h2 class="page-title">{{ $discount->internal_name }}</h2>
                <p class="text-muted mb-0">
                    {{ $discount->public_label ?: 'Tanpa public label' }}
                    @if($discount->code)
                        <span class="badge bg-secondary-lt text-secondary ms-1">{{ $discount->code }}</span>
                    @endif
                </p>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                @can('discounts.update')
                    <a href="{{ route('discounts.edit', $discount) }}" class="btn btn-outline-primary">
                        <i class="ti ti-pencil me-1"></i>Edit
                    </a>
                @endcan
                @can('discounts.archive')
                    @if($discount->status_view !== 'archived')
                        <form method="POST" action="{{ route('discounts.archive', $discount) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning" data-confirm="Arsipkan discount '{{ $discount->internal_name }}'?">
                                <i class="ti ti-archive me-1"></i>Arsip
                            </button>
                        </form>
                    @endif
                @endcan
                @can('discounts.delete')
                    @if($discount->usages->isEmpty())
                        <form method="POST" action="{{ route('discounts.destroy', $discount) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger" data-confirm="Hapus discount '{{ $discount->internal_name }}'? Data yang sudah dihapus tidak bisa dikembalikan.">
                                <i class="ti ti-trash me-1"></i>Delete
                            </button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('discounts.usages.index', ['discount_id' => $discount->id]) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-history me-1"></i>Lihat Usage
                </a>
                <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan</h3>
                </div>
                <div class="card-body">
                    @php
                        $statusClass = match($discount->status_view) {
                            'active' => 'bg-green-lt text-green',
                            'scheduled' => 'bg-azure-lt text-azure',
                            'expired', 'archived' => 'bg-secondary-lt text-secondary',
                            default => 'bg-orange-lt text-orange',
                        };
                    @endphp

                    <dl class="row g-2 mb-0">
                        <dt class="col-5 text-muted small">Type</dt>
                        <dd class="col-7 small mb-0">{{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}</dd>

                        <dt class="col-5 text-muted small">Scope</dt>
                        <dd class="col-7 small mb-0">{{ ucfirst($discount->application_scope) }}</dd>

                        <dt class="col-5 text-muted small">Priority / Seq</dt>
                        <dd class="col-7 small mb-0">{{ $discount->priority }} / {{ $discount->sequence }}</dd>

                        <dt class="col-5 text-muted small">Status</dt>
                        <dd class="col-7 mb-0"><span class="badge {{ $statusClass }}">{{ ucfirst($discount->status_view) }}</span></dd>

                        <dt class="col-5 text-muted small">Stack mode</dt>
                        <dd class="col-7 small mb-0">{{ ucfirst($discount->stack_mode ?? '-') }}</dd>

                        <dt class="col-5 text-muted small">Combination</dt>
                        <dd class="col-7 small mb-0">{{ ucfirst($discount->combination_mode ?? '-') }}</dd>

                        <dt class="col-5 text-muted small">Voucher req.</dt>
                        <dd class="col-7 small mb-0">{{ $discount->is_voucher_required ? 'Ya' : 'Tidak' }}</dd>

                        <dt class="col-5 text-muted small">Manual only</dt>
                        <dd class="col-7 small mb-0">{{ $discount->is_manual_only ? 'Ya' : 'Tidak' }}</dd>

                        <dt class="col-5 text-muted small">Usage</dt>
                        <dd class="col-7 small mb-0">{{ $discount->usages->count() }} usage · {{ $discount->vouchers->count() }} voucher</dd>

                        @if($discount->usage_limit)
                            <dt class="col-5 text-muted small">Limit</dt>
                            <dd class="col-7 small mb-0">{{ $discount->usage_limit }} total / {{ $discount->usage_limit_per_customer ?: '∞' }} per pelanggan</dd>
                        @endif

                        @if($discount->starts_at || $discount->ends_at)
                            <dt class="col-5 text-muted small">Periode</dt>
                            <dd class="col-7 small mb-0">
                                {{ $discount->starts_at?->format('d/m/Y H:i') ?? '-' }}<br>
                                <span class="text-muted">s/d {{ $discount->ends_at?->format('d/m/Y H:i') ?? 'Tanpa batas' }}</span>
                            </dd>
                        @endif
                    </dl>

                    @if($discount->rule_payload)
                        <div class="mt-3 border-top pt-3">
                            <div class="text-muted small fw-bold mb-1">Rule Payload</div>
                            <pre class="small bg-body-secondary p-2 rounded mb-0" style="font-size:.75rem;">{{ json_encode($discount->rule_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Targets</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Operator</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($discount->targets as $target)
                                    <tr>
                                        <td class="small">{{ $target->target_type }}</td>
                                        <td class="small">{{ $target->target_id ?? $target->target_code ?? 'all' }}</td>
                                        <td><span class="badge {{ $target->operator === 'include' ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">{{ $target->operator }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Semua item / context eligible.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Conditions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Condition</th>
                                    <th>Operator</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($discount->conditions as $condition)
                                    <tr>
                                        <td class="small">{{ $condition->condition_type }}</td>
                                        <td class="small"><code>{{ $condition->operator ?? '-' }}</code></td>
                                        <td class="small">{{ $condition->value ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada condition tambahan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Vouchers & Usage</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small fw-bold mb-2">Voucher / Promo Code</div>
                            @forelse($discount->vouchers as $voucher)
                                <div class="border rounded p-2 mb-2">
                                    <div class="fw-semibold small">{{ $voucher->code }}</div>
                                    <div class="text-muted small">{{ $voucher->description ?: 'Tanpa deskripsi' }}</div>
                                </div>
                            @empty
                                <div class="text-muted small">Tidak ada voucher.</div>
                            @endforelse
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small fw-bold mb-2">Recent Usage</div>
                            @forelse($discount->usages->take(5) as $usage)
                                <div class="border rounded p-2 mb-2">
                                    <div class="fw-semibold small">{{ $usage->usage_reference_type ?: 'manual' }} #{{ $usage->usage_reference_id ?: '-' }}</div>
                                    <div class="text-muted small">{{ $usage->usage_status }} · {{ $usage->applied_at?->format('d/m/Y H:i') ?: '-' }}</div>
                                </div>
                            @empty
                                <div class="text-muted small">Belum ada usage.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
