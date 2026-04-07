@extends('layouts.admin')

@section('title', 'Detail Stok')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock List</div>
            <h2 class="page-title">{{ $stock->product?->name }}</h2>
            <p class="text-muted mb-0">{{ $stock->variant?->name ?? '-' }} | {{ $stock->location?->name }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.stocks.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
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
            <div class="card-body p-0">
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
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="ti ti-history text-muted d-block mb-2" style="font-size:2rem;"></i>
                                        <div class="text-muted">Belum ada mutasi.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
