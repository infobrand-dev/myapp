@extends('layouts.admin')

@section('title', 'Buat Payment')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Keuangan</div>
        <h2 class="page-title">Buat Payment</h2>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST" action="{{ route('payments.store') }}" class="row g-3" enctype="multipart/form-data">
    @csrf
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title">Payment Detail</h3>@include('shared.accounting.mode-badge')</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    @include('shared.accounting.field-label', [
                        'label' => 'Payment Method',
                        'required' => true,
                        'tooltip' => 'Pilih metode pembayaran yang benar-benar digunakan, seperti transfer bank, cash, atau e-wallet.',
                    ])
                    <select name="payment_method_id" class="form-select" required>
                        <option value="">Pilih method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    @include('shared.accounting.field-label', [
                        'label' => 'Amount',
                        'required' => true,
                        'tooltip' => 'Nominal pembayaran yang diterima atau dibayarkan. Nilai ini harus sama dengan total allocation.',
                    ])
                    <input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount') }}" required data-payment-total>
                    <div class="form-hint">Nominal payment harus sama dengan total allocation.</div>
                </div>
                <div class="col-md-4">
                    @include('shared.accounting.field-label', [
                        'label' => 'Paid At',
                        'tooltip' => 'Tanggal dan jam pembayaran benar-benar terjadi. Boleh disesuaikan jika input dilakukan belakangan.',
                    ])
                    <input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d\\TH:i')) }}">
                </div>
                @if($isAdvancedMode)
                    <div class="col-md-4">
                        @include('shared.accounting.field-label', [
                            'label' => 'Source',
                            'tooltip' => 'Sumber asal pembayaran, misalnya backoffice, POS, atau integrasi lain. Berguna untuk audit proses penerimaan uang.',
                        ])
                        <select name="source" class="form-select">
                            @foreach($paymentSourceOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('source', 'backoffice') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('shared.accounting.field-label', [
                            'label' => 'Received By',
                            'tooltip' => 'Petugas yang menerima atau mencatat pembayaran ini. Jika dikosongkan, sistem memakai user aktif.',
                        ])
                        <select name="received_by" class="form-select">
                            <option value="">Auto current user</option>
                            @foreach($receivers as $receiver)
                                <option value="{{ $receiver->id }}" @selected((string) old('received_by') === (string) $receiver->id)>{{ $receiver->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Reference Number',
                            'tooltip' => 'Nomor referensi internal seperti nomor slip transfer atau bukti setor. Boleh dikosongkan jika tidak ada.',
                        ])
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'External Reference',
                            'tooltip' => 'Nomor referensi dari sistem lain seperti marketplace, gateway, atau POS. Berguna untuk pelacakan silang antar sistem.',
                        ])
                        <input type="text" name="external_reference" class="form-control" value="{{ old('external_reference') }}">
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Proof of Payment',
                            'tooltip' => 'Upload bukti transfer, slip, atau dokumen pembayaran lain. Boleh dikosongkan jika belum tersedia.',
                        ])
                        <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-6">
                        @include('shared.accounting.field-label', [
                            'label' => 'Reconciliation Status',
                            'tooltip' => 'Gunakan untuk menandai apakah payment ini sudah dicek dan cocok dengan bukti atau mutasi rekening.',
                        ])
                        <select name="reconciliation_status" class="form-select">
                            @foreach($reconciliationStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('reconciliation_status', 'unreconciled') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="source" value="{{ old('source', 'backoffice') }}">
                    <input type="hidden" name="reconciliation_status" value="{{ old('reconciliation_status', 'unreconciled') }}">
                @endif
                <div class="col-12">
                    @include('shared.accounting.field-label', [
                        'label' => 'Notes',
                        'tooltip' => 'Catatan tambahan untuk pembayaran ini. Boleh dikosongkan jika tidak ada keterangan khusus.',
                    ])
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
                                @include('shared.accounting.field-label', [
                                    'label' => 'Transaction Type',
                                    'tooltip' => 'Pilih jenis transaksi yang akan dibayar atau dikaitkan, seperti sale, purchase, atau sale return.',
                                ])
                                <select name="allocations[{{ $index }}][payable_type]" class="form-select">
                                    @foreach($payableTypeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($allocation['payable_type'] ?? 'sale') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                @include('shared.accounting.field-label', [
                                    'label' => 'Transaction',
                                    'tooltip' => 'Pilih transaksi spesifik yang ingin dialokasikan pembayaran ini.',
                                ])
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
                                @include('shared.accounting.field-label', [
                                    'label' => 'Allocation Amount',
                                    'tooltip' => 'Nominal dari payment ini yang dialokasikan ke transaksi terpilih. Total semua allocation harus sama dengan Amount.',
                                ])
                                <input type="number" min="0.01" step="0.01" name="allocations[{{ $index }}][amount]" class="form-control" value="{{ $allocation['amount'] ?? '' }}" data-allocation-amount>
                            </div>
                            <div class="form-hint mt-2" data-transaction-summary>Pilih transaksi untuk melihat nominal outstanding.</div>
                            @if($isAdvancedMode && $index > 0)
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-allocation">Remove</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($isAdvancedMode)
                    <button type="button" class="btn btn-outline-secondary w-100 mt-3" id="add-allocation">Add Allocation</button>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary flex-fill">Batal</a>
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

<template id="payment-allocation-template">
    <div class="col-12 payment-allocation-item border rounded p-3">
        <div class="mb-2">
            @include('shared.accounting.field-label', [
                'label' => 'Transaction Type',
                'tooltip' => 'Pilih jenis transaksi yang akan dibayar atau dikaitkan, seperti sale, purchase, atau sale return.',
            ])
            <select class="form-select" data-name="payable_type">
                @foreach($payableTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-2">
            @include('shared.accounting.field-label', [
                'label' => 'Transaction',
                'tooltip' => 'Pilih transaksi spesifik yang ingin dialokasikan pembayaran ini.',
            ])
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
            @include('shared.accounting.field-label', [
                'label' => 'Allocation Amount',
                'tooltip' => 'Nominal dari payment ini yang dialokasikan ke transaksi terpilih. Total semua allocation harus sama dengan Amount.',
            ])
            <input type="number" min="0.01" step="0.01" class="form-control" data-name="amount" data-allocation-amount>
        </div>
        <div class="form-hint mb-2" data-transaction-summary>Pilih transaksi untuk melihat nominal outstanding.</div>
        @if($isAdvancedMode)
            <button type="button" class="btn btn-sm btn-outline-danger remove-allocation">Remove</button>
        @endif
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

    if (!list || !template) {
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

    if (button) {
        button.addEventListener('click', function () {
            const index = list.querySelectorAll('.payment-allocation-item').length;
            const fragment = template.content.cloneNode(true);

            fragment.querySelectorAll('[data-name]').forEach(function (field) {
                field.name = 'allocations[' + index + '][' + field.dataset.name + ']';
            });

            list.appendChild(fragment);
            syncTargetOptions(list.lastElementChild);
        });
    }

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
