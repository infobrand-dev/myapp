@extends('layouts.admin')

@section('title', 'Finance Taxes')

@section('content')
@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Finance Taxes</h2>
            <p class="text-muted mb-0">Master tarif pajak, mapping akun pajak, dan rekap pajak dasar untuk sales dan purchases.</p>
        </div>
        <div class="col-auto">
            @include('shared.accounting.mode-badge')
        </div>
    </div>
</div>

@include('finance::partials.accounting-nav')

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-secondary mb-3">
            <strong>Mode mapping:</strong>
            standard = kode, nama, tarif, status, dan rekap dasar.
            advanced = seluruh standard + tax type, akun COA sales/purchase, inclusive flag, dan deskripsi.
        </div>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Type</label>
                <select name="tax_type" class="form-select">
                    <option value="">All</option>
                    @foreach($taxTypeOptions as $type => $label)
                        <option value="{{ $type }}" @selected($filters['tax_type'] === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('finance.taxes.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">PPN Keluaran</div><div class="h4 mb-0">{{ $money->format((float) $summary['sales_tax_total'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">PPN Masukan</div><div class="h4 mb-0">{{ $money->format((float) $summary['purchase_tax_total'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Net VAT</div><div class="h4 mb-0 {{ $summary['net_vat_payable'] >= 0 ? 'text-danger' : 'text-success' }}">{{ $money->format((float) $summary['net_vat_payable'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Posted Journal Tax</div><div class="small">Sales credit: {{ $money->format((float) $summary['sales_tax_journal_credit'], $currency) }}</div><div class="small">Purchase debit: {{ $money->format((float) $summary['purchase_tax_journal_debit'], $currency) }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Master Pajak</h3>
            </div>
            <form method="POST" action="{{ route('finance.taxes.store') }}">
                @csrf
                <div class="card-body">
                    @include('finance::taxes.partials.form', [
                        'taxRate' => new \App\Modules\Finance\Models\FinanceTaxRate(),
                        'taxTypeOptions' => $taxTypeOptions,
                        'chartOfAccountOptions' => $chartOfAccountOptions,
                    ])
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                @if($isAdvancedMode)
                                    <th>Type</th>
                                @endif
                                <th>Rate</th>
                                <th>Status</th>
                                @if($isAdvancedMode)
                                    <th>Accounts</th>
                                @endif
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taxRates as $taxRate)
                                <tr>
                                    <td class="fw-semibold">{{ $taxRate->code }}</td>
                                    <td>
                                        <div>{{ $taxRate->name }}</div>
                                        @if($isAdvancedMode && $taxRate->description)
                                            <div class="text-muted small">{{ $taxRate->description }}</div>
                                        @endif
                                    </td>
                                    @if($isAdvancedMode)
                                        <td>{{ $taxTypeOptions[$taxRate->tax_type] ?? $taxRate->tax_type }}</td>
                                    @endif
                                    <td>
                                        <div>{{ number_format((float) $taxRate->rate_percent, 2, ',', '.') }}%</div>
                                        @if($isAdvancedMode && $taxRate->is_inclusive)
                                            <div class="text-muted small">Inclusive</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $taxRate->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                            {{ $taxRate->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    @if($isAdvancedMode)
                                        <td>
                                            <div class="small">Sales: {{ $taxRate->sales_account_code ?: '-' }}</div>
                                            <div class="small">Purchase: {{ $taxRate->purchase_account_code ?: '-' }}</div>
                                        </td>
                                    @endif
                                    <td class="text-end align-middle">
                                        <div class="table-actions">
                                            <a href="{{ route('finance.taxes.edit', $taxRate) }}" class="btn btn-icon btn-sm btn-outline-primary">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <form class="d-inline-block m-0" method="POST" action="{{ route('finance.taxes.destroy', $taxRate) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" data-confirm="Hapus master pajak ini?">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdvancedMode ? '7' : '5' }}" class="text-center py-5 text-muted">Belum ada master pajak.</td>
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
