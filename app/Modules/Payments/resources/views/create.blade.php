@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
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
                    <input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount') }}" required data-payment-total>
                    <div class="form-hint">Nominal payment harus sama dengan total allocation.</div>
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
                <div class="alert alert-secondary mb-3">
                    <div class="fw-semibold mb-1">Ringkasan Payment</div>
                    <div class="small">Total payment: <span data-summary-payment>{{ $money->format(0, $defaultCurrency) }}</span></div>
                    <div class="small">Total allocation: <span data-summary-allocation>{{ $money->format(0, $defaultCurrency) }}</span></div>
                    <div class="small">Selisih: <span data-summary-difference>{{ $money->format(0, $defaultCurrency) }}</span></div>
                </div>
                <div class="row g-3 payment-allocation-list">
                    @php
                        $oldAllocations = old('allocations', [[
                            'payable_type' => $prefillSaleReturnId ? 'sale_return' : ($prefillPurchaseId ? 'purchase' : 'sale'),
                            'payable_id' => $prefillSaleReturnId ?: ($prefillPurchaseId ?: $prefillSaleId),
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
                                <label class="form-label">Transaction</label>
                                <select name="allocations[{{ $index }}][payable_id]" class="form-select">
                                    <option value="">Pilih transaksi</option>
                                    @foreach($saleOptions as $sale)
                                        <option value="{{ $sale->id }}" data-kind="sale" data-balance="{{ (float) $sale->balance_due }}" data-label="{{ $sale->sale_number }}" @selected(($allocation['payable_type'] ?? 'sale') === 'sale' && (string) ($allocation['payable_id'] ?? '') === (string) $sale->id)>
                                            Sale: {{ $sale->sale_number }} | {{ $sale->customer_name_snapshot ?: 'Guest' }} | Due {{ $money->format((float) $sale->balance_due, $sale->currency_code) }}
                                        </option>
                                    @endforeach
                                    @foreach($saleReturnOptions as $saleReturn)
                                        <option value="{{ $saleReturn->id }}" data-kind="sale_return" data-balance="{{ (float) $saleReturn->refund_balance }}" data-label="{{ $saleReturn->return_number }}" @selected(($allocation['payable_type'] ?? '') === 'sale_return' && (string) ($allocation['payable_id'] ?? '') === (string) $saleReturn->id)>
                                            Return: {{ $saleReturn->return_number }} | {{ $saleReturn->customer_name_snapshot ?: 'Guest' }} | Refund {{ $money->format((float) $saleReturn->refund_balance, $saleReturn->currency_code) }}
                                        </option>
                                    @endforeach
                                    @foreach($purchaseOptions as $purchase)
                                        <option value="{{ $purchase->id }}" data-kind="purchase" data-balance="{{ (float) $purchase->balance_due }}" data-label="{{ $purchase->purchase_number }}" @selected(($allocation['payable_type'] ?? '') === 'purchase' && (string) ($allocation['payable_id'] ?? '') === (string) $purchase->id)>
                                            Purchase: {{ $purchase->purchase_number }} | {{ $purchase->supplier_name_snapshot ?: 'Supplier' }} | Due {{ $money->format((float) $purchase->balance_due, $purchase->currency_code) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Allocation Amount</label>
                                <input type="number" min="0.01" step="0.01" name="allocations[{{ $index }}][amount]" class="form-control" value="{{ $allocation['amount'] ?? '' }}" data-allocation-amount>
                            </div>
                            <div class="form-hint mt-2" data-transaction-summary>Pilih transaksi untuk melihat nominal outstanding.</div>
                            @if($index > 0)
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-allocation">Remove</button>
                            @endif
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
            <label class="form-label">Transaction</label>
            <select class="form-select" data-name="payable_id">
                <option value="">Pilih transaksi</option>
                @foreach($saleOptions as $sale)
                    <option value="{{ $sale->id }}" data-kind="sale" data-balance="{{ (float) $sale->balance_due }}" data-currency="{{ $sale->currency_code ?: $defaultCurrency }}" data-label="{{ $sale->sale_number }}">Sale: {{ $sale->sale_number }} | {{ $sale->customer_name_snapshot ?: 'Guest' }} | Due {{ $money->format((float) $sale->balance_due, $sale->currency_code) }}</option>
                @endforeach
                @foreach($saleReturnOptions as $saleReturn)
                    <option value="{{ $saleReturn->id }}" data-kind="sale_return" data-balance="{{ (float) $saleReturn->refund_balance }}" data-currency="{{ $saleReturn->currency_code ?: $defaultCurrency }}" data-label="{{ $saleReturn->return_number }}">Return: {{ $saleReturn->return_number }} | {{ $saleReturn->customer_name_snapshot ?: 'Guest' }} | Refund {{ $money->format((float) $saleReturn->refund_balance, $saleReturn->currency_code) }}</option>
                @endforeach
                @foreach($purchaseOptions as $purchase)
                    <option value="{{ $purchase->id }}" data-kind="purchase" data-balance="{{ (float) $purchase->balance_due }}" data-currency="{{ $purchase->currency_code ?: $defaultCurrency }}" data-label="{{ $purchase->purchase_number }}">Purchase: {{ $purchase->purchase_number }} | {{ $purchase->supplier_name_snapshot ?: 'Supplier' }} | Due {{ $money->format((float) $purchase->balance_due, $purchase->currency_code) }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Allocation Amount</label>
            <input type="number" min="0.01" step="0.01" class="form-control" data-name="amount" data-allocation-amount>
        </div>
        <div class="form-hint mb-2" data-transaction-summary>Pilih transaksi untuk melihat nominal outstanding.</div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">Remove</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.querySelector('.payment-allocation-list');
    const template = document.getElementById('payment-allocation-template');
    const button = document.getElementById('add-allocation');
    const paymentAmountField = document.querySelector('[data-payment-total]');
    const paymentSummaryField = document.querySelector('[data-summary-payment]');
    const allocationSummaryField = document.querySelector('[data-summary-allocation]');
    const differenceSummaryField = document.querySelector('[data-summary-difference]');
    const defaultCurrency = @json($defaultCurrency);
    const currencyLocaleMap = { IDR: 'id-ID', USD: 'en-US', SGD: 'en-SG', EUR: 'de-DE' };
    const formatCurrency = (value, currency = defaultCurrency) => {
        const resolvedCurrency = (currency || defaultCurrency || 'IDR').toUpperCase();
        const locale = currencyLocaleMap[resolvedCurrency] || 'id-ID';
        const fractionDigits = resolvedCurrency === 'IDR' ? 0 : 2;
        return new Intl.NumberFormat(locale, { style: 'currency', currency: resolvedCurrency, maximumFractionDigits: fractionDigits, minimumFractionDigits: fractionDigits }).format(Number.isFinite(value) ? value : 0);
    };
    const parseNumber = (value) => {
        const parsed = parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const syncTargetOptions = (wrapper) => {
        const typeField = wrapper.querySelector('[name$="[payable_type]"]');
        const targetField = wrapper.querySelector('[name$="[payable_id]"]');
        const amountField = wrapper.querySelector('[data-allocation-amount]');
        const summaryField = wrapper.querySelector('[data-transaction-summary]');

        if (!typeField || !targetField) {
            return;
        }

        Array.from(targetField.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.kind !== typeField.value;
        });

        const selected = targetField.selectedOptions[0];
        if (selected && selected.hidden) {
            targetField.value = '';
        }

        const activeOption = targetField.selectedOptions[0];
        const balance = activeOption && activeOption.value ? parseNumber(activeOption.dataset.balance) : 0;
        const currency = activeOption && activeOption.value ? (activeOption.dataset.currency || defaultCurrency) : defaultCurrency;
        const label = activeOption && activeOption.value ? activeOption.textContent.trim() : '';

        if (summaryField) {
            summaryField.textContent = label !== ''
                ? label + ' | Outstanding ' + formatCurrency(balance, currency)
                : 'Pilih transaksi untuk melihat nominal outstanding.';
        }

        if (amountField && activeOption && activeOption.value && (!amountField.value || parseNumber(amountField.value) <= 0)) {
            amountField.value = balance > 0 ? balance.toFixed(2) : '';
        }

        if (paymentAmountField && (!paymentAmountField.value || parseNumber(paymentAmountField.value) <= 0)) {
            const amountFields = Array.from(list.querySelectorAll('[data-allocation-amount]'));

            if (amountFields.length === 1 && amountField && amountField.value) {
                paymentAmountField.value = amountField.value;
            }
        }

        syncTotals();
    };

    const syncTotals = () => {
        const paymentAmount = parseNumber(paymentAmountField ? paymentAmountField.value : 0);
        const allocationTotal = Array.from(list.querySelectorAll('[data-allocation-amount]'))
            .reduce((total, field) => total + parseNumber(field.value), 0);
        const difference = paymentAmount - allocationTotal;

        if (paymentSummaryField) {
            paymentSummaryField.textContent = formatCurrency(paymentAmount);
        }

        if (allocationSummaryField) {
            allocationSummaryField.textContent = formatCurrency(allocationTotal);
        }

        if (differenceSummaryField) {
            differenceSummaryField.textContent = formatCurrency(difference);
            differenceSummaryField.classList.toggle('text-danger', Math.abs(difference) > 0.009);
            differenceSummaryField.classList.toggle('text-success', Math.abs(difference) <= 0.009);
        }
    };

    if (!list || !template || !button) {
        return;
    }

    list.querySelectorAll('.payment-allocation-item').forEach((item) => {
        syncTargetOptions(item);
    });

    list.addEventListener('change', function (event) {
        if (event.target.name && event.target.name.endsWith('[payable_type]')) {
            syncTargetOptions(event.target.closest('.payment-allocation-item'));
        }

        if (event.target.name && event.target.name.endsWith('[payable_id]')) {
            syncTargetOptions(event.target.closest('.payment-allocation-item'));
        }
    });

    list.addEventListener('input', function (event) {
        if (event.target.matches('[data-allocation-amount]')) {
            syncTotals();
        }
    });

    button.addEventListener('click', function () {
        const index = list.querySelectorAll('.payment-allocation-item').length;
        const fragment = template.content.cloneNode(true);

        fragment.querySelectorAll('[data-name]').forEach(function (field) {
            field.name = 'allocations[' + index + '][' + field.dataset.name + ']';
        });

        list.appendChild(fragment);
        syncTargetOptions(list.lastElementChild);
    });

    if (paymentAmountField) {
        paymentAmountField.addEventListener('input', syncTotals);
    }

    list.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-allocation')) {
            return;
        }

        event.target.closest('.payment-allocation-item')?.remove();
        syncTotals();
    });

    syncTotals();
});
</script>
@endsection
