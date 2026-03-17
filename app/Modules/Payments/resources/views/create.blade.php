@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Create Payment</h2>
        <div class="text-muted small">Catat pembayaran dan alokasikan ke transaksi.</div>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ route('payments.store') }}" class="row g-3">
    @csrf
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment Detail</h3></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method_id" class="form-select" required>
                        <option value="">Pilih method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paid At</label>
                    <input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-select">
                        @foreach($paymentSourceOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('source', 'backoffice') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Received By</label>
                    <select name="received_by" class="form-select">
                        <option value="">Auto current user</option>
                        @foreach($receivers as $receiver)
                            <option value="{{ $receiver->id }}" @selected((string) old('received_by') === (string) $receiver->id)>{{ $receiver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">External Reference</label>
                    <input type="text" name="external_reference" class="form-control" value="{{ old('external_reference') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Allocation</h3></div>
            <div class="card-body">
                <div class="row g-3 payment-allocation-list">
                    @php
                        $oldAllocations = old('allocations', [[
                            'payable_type' => 'sale',
                            'payable_id' => $prefillSaleId,
                            'amount' => old('amount'),
                        ]]);
                    @endphp
                    @foreach($oldAllocations as $index => $allocation)
                        <div class="col-12 payment-allocation-item border rounded p-3">
                            <div class="mb-2">
                                <label class="form-label">Transaction Type</label>
                                <select name="allocations[{{ $index }}][payable_type]" class="form-select">
                                    @foreach($payableTypeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($allocation['payable_type'] ?? 'sale') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Sale</label>
                                <select name="allocations[{{ $index }}][payable_id]" class="form-select">
                                    <option value="">Pilih sale</option>
                                    @foreach($saleOptions as $sale)
                                        <option value="{{ $sale->id }}" @selected((string) ($allocation['payable_id'] ?? '') === (string) $sale->id)>
                                            {{ $sale->sale_number }} | {{ $sale->customer_name_snapshot ?: 'Guest' }} | Due Rp {{ number_format((float) $sale->balance_due, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Allocation Amount</label>
                                <input type="number" min="0.01" step="0.01" name="allocations[{{ $index }}][amount]" class="form-control" value="{{ $allocation['amount'] ?? '' }}">
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-secondary w-100 mt-3" id="add-allocation">Add Allocation</button>
            </div>
        </div>

        <div class="d-grid mt-3">
            <button type="submit" class="btn btn-primary">Save Payment</button>
        </div>
    </div>
</form>

<template id="payment-allocation-template">
    <div class="col-12 payment-allocation-item border rounded p-3">
        <div class="mb-2">
            <label class="form-label">Transaction Type</label>
            <select class="form-select" data-name="payable_type">
                @foreach($payableTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Sale</label>
            <select class="form-select" data-name="payable_id">
                <option value="">Pilih sale</option>
                @foreach($saleOptions as $sale)
                    <option value="{{ $sale->id }}">{{ $sale->sale_number }} | {{ $sale->customer_name_snapshot ?: 'Guest' }} | Due Rp {{ number_format((float) $sale->balance_due, 0, ',', '.') }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Allocation Amount</label>
            <input type="number" min="0.01" step="0.01" class="form-control" data-name="amount">
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">Remove</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.querySelector('.payment-allocation-list');
    const template = document.getElementById('payment-allocation-template');
    const button = document.getElementById('add-allocation');

    if (!list || !template || !button) {
        return;
    }

    button.addEventListener('click', function () {
        const index = list.querySelectorAll('.payment-allocation-item').length;
        const fragment = template.content.cloneNode(true);

        fragment.querySelectorAll('[data-name]').forEach(function (field) {
            field.name = 'allocations[' + index + '][' + field.dataset.name + ']';
        });

        list.appendChild(fragment);
    });

    list.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-allocation')) {
            return;
        }

        event.target.closest('.payment-allocation-item')?.remove();
    });
});
</script>
@endsection
