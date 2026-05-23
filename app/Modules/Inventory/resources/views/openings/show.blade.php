@extends('layouts.admin')

@section('title', 'Detail Opening Stock')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">{{ $opening->code }}</h2>
            <p class="text-muted mb-0">
                {{ $opening->opening_date?->format('d M Y') ?? '-' }} |
                Lokasi: {{ $opening->location?->name ?: '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            @if($journal)
                <a href="{{ route('finance.journals.show', $journal) }}" class="btn btn-outline-primary">
                    <i class="ti ti-scale me-1"></i>Lihat Journal
                </a>
            @endif
            <a href="{{ route('inventory.openings.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Opening Items</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Min</th>
                                <th>Reorder</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opening->items as $item)
                                @php
                                    $movement = $item->movement;
                                    $unitCost = (float) ($movement->unit_cost ?? 0);
                                    $movementValue = (float) ($movement->movement_value ?? ((float) $item->quantity * $unitCost));
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product?->name ?: '-' }}</div>
                                        <div class="text-muted small">{{ $item->variant?->name ?: '-' }}</div>
                                    </td>
                                    <td>{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $item->minimum_quantity, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $item->reorder_quantity, 2, ',', '.') }}</td>
                                    <td>{{ $money->format($unitCost, $currency) }}</td>
                                    <td>{{ $money->format($movementValue, $currency) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada item opening stock.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Opening Summary</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div>{{ ucfirst((string) $opening->status) }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Created By</div>
                    <div>{{ $opening->creator?->name ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Posted At</div>
                    <div>{{ $opening->posted_at?->format('d M Y H:i') ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Notes</div>
                    <div>{{ $opening->notes ?: '-' }}</div>
                </div>
                @if($journal)
                    <div>
                        <div class="text-muted small">Journal</div>
                        <div>
                            <a href="{{ route('finance.journals.show', $journal) }}">
                                {{ $journal->journal_number ?: ('Journal #' . $journal->id) }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
