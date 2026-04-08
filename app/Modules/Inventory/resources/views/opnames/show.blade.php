@extends('layouts.admin')

@section('title', 'Detail Stock Opname')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori · Stock Opname</div>
            <h2 class="page-title">{{ $opname->code }}</h2>
            <p class="text-muted mb-0">{{ $opname->location ? $opname->location->name : '-' }} | {{ $opname->opname_date ? $opname->opname_date->format('d/m/Y') : '-' }}</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('inventory.opnames.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
            @if($opname->isDraft() && auth()->user() && auth()->user()->can('inventory.finalize-stock-opname'))
                <form method="POST" action="{{ route('inventory.opnames.finalize', $opname) }}">
                    @csrf
                    <button class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Finalize
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('inventory.opnames.update', $opname) }}">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Header</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Tanggal Opname</label>
                        <input type="date" name="opname_date" class="form-control" value="{{ old('opname_date', $opname->opname_date ? $opname->opname_date->toDateString() : now()->toDateString()) }}" {{ $opname->isFinalized() ? 'disabled' : '' }}>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Status</label>
                        <div><span class="badge {{ $opname->isFinalized() ? 'bg-success-lt text-success' : 'bg-yellow-lt text-yellow' }}">{{ $opname->status }}</span></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="4" {{ $opname->isFinalized() ? 'disabled' : '' }}>{{ old('notes', $opname->notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Adjustment</div>
                        <div>
                            @if($opname->adjustment)
                                <a href="{{ route('inventory.adjustments.show', $opname->adjustment) }}">{{ $opname->adjustment->code }}</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Finalized</div>
                        <div>{{ $opname->finalized_at ? (($opname->finalizer ? $opname->finalizer->name : '-') . ' | ' . $opname->finalized_at->format('d/m/Y H:i')) : '-' }}</div>
                    </div>
                </div>
            </div>

            @if($opname->isDraft())
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan Draft
                    </button>
                </div>
            @endif
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Items</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter" id="opname-items-table">
                        <thead><tr><th>Produk</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th><th>Final System</th><th>Applied Adj</th></tr></thead>
                        <tbody>
                            @foreach($opname->items as $index => $item)
                                @php
                                    $physicalOld = old('items.' . $index . '.physical_quantity', $item->physical_quantity);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product ? $item->product->name : '-' }}</div>
                                        @if($item->variant)<div class="text-muted small">{{ $item->variant->name }}</div>@endif
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="system-qty" data-value="{{ (float) $item->system_quantity }}">{{ number_format((float) $item->system_quantity, 4, ',', '.') }}</td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" name="items[{{ $index }}][physical_quantity]" class="form-control physical-qty" value="{{ $physicalOld }}" {{ $opname->isFinalized() ? 'disabled' : '' }}>
                                        <input type="text" name="items[{{ $index }}][notes]" class="form-control mt-2" placeholder="Catatan item" value="{{ old('items.' . $index . '.notes', $item->notes) }}" {{ $opname->isFinalized() ? 'disabled' : '' }}>
                                    </td>
                                    <td class="difference-qty">{{ $item->difference_quantity === null ? '-' : number_format((float) $item->difference_quantity, 4, ',', '.') }}</td>
                                    <td>{{ $item->final_system_quantity === null ? '-' : number_format((float) $item->final_system_quantity, 4, ',', '.') }}</td>
                                    <td>{{ $item->adjustment_quantity === null ? '-' : number_format((float) $item->adjustment_quantity, 4, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const table = document.getElementById('opname-items-table');

    const formatNumber = (value) => {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4
        }).format(value);
    };

    const syncDifference = (row) => {
        const systemCell = row.querySelector('.system-qty');
        const physicalInput = row.querySelector('.physical-qty');
        const differenceCell = row.querySelector('.difference-qty');

        if (!physicalInput || physicalInput.value === '') {
            differenceCell.textContent = '-';
            return;
        }

        const system = parseFloat(systemCell.dataset.value || '0');
        const physical = parseFloat(physicalInput.value || '0');
        const diff = physical - system;

        differenceCell.textContent = formatNumber(diff);
    };

    table?.querySelectorAll('tbody tr').forEach(syncDifference);

    table?.addEventListener('input', (event) => {
        if (!event.target.classList.contains('physical-qty')) {
            return;
        }

        syncDifference(event.target.closest('tr'));
    });
})();
</script>
@endpush
