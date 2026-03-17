@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $stock->product?->name }}</h2>
        <div class="text-muted small">{{ $stock->variant?->name ?? 'Base product' }} | {{ $stock->location?->name }}</div>
    </div>
    <a href="{{ route('inventory.stocks.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Stock Position</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Current</div><div class="h3 mb-0">{{ number_format((float) $stock->current_quantity, 2, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Reserved</div><div>{{ number_format((float) $stock->reserved_quantity, 2, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Available</div><div>{{ number_format($stock->availableQuantity(), 2, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Minimum</div><div>{{ number_format((float) $stock->minimum_quantity, 2, ',', '.') }}</div></div>
                <div><div class="text-muted small">Reorder</div><div>{{ number_format((float) $stock->reorder_quantity, 2, ',', '.') }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Stock Card</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Waktu</th><th>Tipe</th><th>Ref</th><th>Before</th><th>Qty</th><th>After</th><th>User</th></tr></thead>
                    <tbody>
                        @forelse($stock->movements()->with('performer')->orderByDesc('occurred_at')->orderByDesc('id')->get() as $movement)
                            <tr>
                                <td>{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->movement_type }}</td>
                                <td>{{ $movement->reference_type ? class_basename($movement->reference_type) . '#' . $movement->reference_id : '-' }}</td>
                                <td>{{ number_format((float) $movement->before_quantity, 2, ',', '.') }}</td>
                                <td>{{ $movement->direction === 'out' ? '-' : '+' }}{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $movement->after_quantity, 2, ',', '.') }}</td>
                                <td>{{ $movement->performer?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada mutasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
