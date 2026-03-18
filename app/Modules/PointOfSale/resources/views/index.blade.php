@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Point Of Sale</h2>
        <div class="text-muted small">Cashier interface ringan untuk checkout flow dengan boundary ketat ke module domain lain.</div>
    </div>
    <a href="{{ route('pos.architecture') }}" class="btn btn-outline-primary">Architecture</a>
</div>

<div class="row g-3 mb-3">
    @foreach($featureBlocks as $block)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $block['title'] }}</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        @foreach($block['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Module Boundary</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($boundaries as $boundary)
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-white">
                        <div class="text-muted small">{{ $boundary }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="fw-semibold mb-2">Target UI layout</div>
        <div class="text-muted small mb-3">Kiri untuk product discovery dan barcode input, kanan untuk cart panel sticky. Gunakan Blade + Alpine.js + fetch JSON agar interaksi cepat tanpa SPA berat.</div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="border rounded p-3 h-100">
                    <div class="fw-semibold mb-2">Left Workspace</div>
                    <div class="text-muted small">Header kasir, barcode input, search, filter kategori, quick product grid, recent items.</div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="border rounded p-3 h-100">
                    <div class="fw-semibold mb-2">Right Cart Panel</div>
                    <div class="text-muted small">Customer selector, line items, notes, discount section, totals summary, payment launcher, print actions.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
