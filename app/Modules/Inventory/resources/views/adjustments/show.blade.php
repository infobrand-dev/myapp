@extends('layouts.admin')

@section('title', 'Detail Adjustment')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock Adjustment</div>
            <h2 class="page-title">{{ $adjustment->code }}</h2>
            <p class="text-muted mb-0">{{ $adjustment->location?->name }} | {{ $adjustment->adjustment_date?->format('d/m/Y') }}</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
            @if($adjustment->isDraft() && auth()->user()?->can('inventory.finalize-stock-adjustment'))
                <form method="POST" action="{{ route('inventory.adjustments.finalize', $adjustment) }}">
                    @csrf
                    <button class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Finalize
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if($errors->has('adjustment'))
    <div class="alert alert-danger">{{ $errors->first('adjustment') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Status</div><div><span class="badge {{ $adjustment->isFinalized() ? 'bg-green-lt text-green' : 'bg-orange-lt text-orange' }}">{{ $adjustment->status }}</span></div></div>
                <div class="mb-2"><div class="text-muted small">Lokasi</div><div>{{ $adjustment->location?->name }}</div></div>
                <div class="mb-2"><div class="text-muted small">Reason Code</div><div>{{ $adjustment->reason_code }}</div></div>
                <div class="mb-2"><div class="text-muted small">Reason</div><div>{{ $adjustment->reason_text }}</div></div>
                <div class="mb-2"><div class="text-muted small">Notes</div><div>{{ $adjustment->notes ?: '-' }}</div></div>
                <div class="mb-2">
                    <div class="text-muted small">Journal</div>
                    <div>
                        @if(!empty($journal))
                            <a href="{{ route('finance.journals.show', $journal->id) }}">{{ $journal->journal_number ?: ('Journal #' . $journal->id) }}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="mb-2"><div class="text-muted small">Dibuat Oleh</div><div>{{ $adjustment->creator?->name ?: '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">Dibuat Pada</div><div>{{ $adjustment->created_at?->format('d/m/Y H:i') }}</div></div>
                <div><div class="text-muted small">Finalized</div><div>{{ $adjustment->finalized_at ? ($adjustment->finalizer?->name . ' | ' . $adjustment->finalized_at->format('d/m/Y H:i')) : '-' }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Produk</th><th>Arah</th><th>Qty</th><th>Notes</th><th>Movement</th></tr></thead>
                        <tbody>
                            @foreach($adjustment->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name }} @if($item->variant)<div class="text-muted small">{{ $item->variant->name }}</div>@endif</td>
                                    <td>
                                        <span class="badge {{ $item->direction === 'in' ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                                            {{ $item->direction === 'in' ? 'Increase' : 'Decrease' }}
                                        </span>
                                    </td>
                                    <td>{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                                    <td>{{ $item->notes ?: '-' }}</td>
                                    <td>
                                        @if($item->movement)
                                            <div>{{ $item->movement->movement_type }}</div>
                                            <div class="text-muted small">#{{ $item->movement->id }}</div>
                                        @else
                                            <span class="text-muted small">Belum diposting</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
