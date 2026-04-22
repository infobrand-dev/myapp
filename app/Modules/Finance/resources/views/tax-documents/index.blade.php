@extends('layouts.admin')

@section('title', 'Tax Register')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Tax Register</h2>
            <p class="text-muted mb-0">Register formal untuk PPN keluaran, PPN masukan, dan PPh dasar agar struktur pajak Indonesia siap dilebarkan ke export dan compliance berikutnya.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('finance.tax-documents.export', request()->query()) }}" class="btn btn-outline-secondary">Export Register CSV</a>
            <a href="{{ route('finance.tax-documents.export-efaktur-draft', request()->query()) }}" class="btn btn-outline-primary">Draft e-Faktur CSV</a>
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

<div class="alert alert-secondary mb-3">
    <strong>Catatan export:</strong>
    `Export Register CSV` dipakai untuk register internal dan review operasional.
    `Draft e-Faktur CSV` adalah struktur awal PPN keluaran dari tax register, belum final integrasi e-Faktur resmi.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Document Type</label>
                <select name="document_type" class="form-select">
                    <option value="">All</option>
                    @foreach($documentTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['document_type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="document_status" class="form-select">
                    <option value="">All</option>
                    @foreach($documentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['document_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Period Month</label>
                <input type="number" min="1" max="12" name="period_month" class="form-control" value="{{ $filters['period_month'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Period Year</label>
                <input type="number" min="2000" max="2999" name="period_year" class="form-control" value="{{ $filters['period_year'] }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('finance.tax-documents.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">PPN Keluaran</div><div class="h4 mb-0">{{ $money->format((float) $summary['output_vat_total'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">PPN Masukan</div><div class="h4 mb-0">{{ $money->format((float) $summary['input_vat_total'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">PPh Dipotong</div><div class="h4 mb-0">{{ $money->format((float) $summary['withholding_total'], $currency) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Issued Docs</div><div class="h4 mb-0">{{ number_format((float) $summary['issued_count'], 0, ',', '.') }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Tax Register</h3>
            </div>
            <form method="POST" action="{{ route('finance.tax-documents.store') }}">
                @csrf
                <div class="card-body">
                    @include('finance::tax-documents.partials.form', [
                        'taxDocument' => new \App\Modules\Finance\Models\FinanceTaxDocument(),
                        'documentTypeOptions' => $documentTypeOptions,
                        'documentStatusOptions' => $documentStatusOptions,
                        'taxRateOptions' => $taxRateOptions,
                        'sourceOptions' => $sourceOptions,
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
                                <th>Document</th>
                                <th>Source</th>
                                <th>Period</th>
                                <th>Counterparty</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $document)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $documentTypeOptions[$document->document_type] ?? $document->document_type }}</div>
                                        <div class="text-muted small">{{ $document->document_number ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional($document->document_date)->format('d M Y') ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($document->sourceDocument)
                                            @if($document->source_document_type === \App\Modules\Sales\Models\Sale::class)
                                                <a href="{{ route('sales.show', $document->sourceDocument) }}">{{ $document->sourceDocument->sale_number }}</a>
                                            @elseif($document->source_document_type === \App\Modules\Purchases\Models\Purchase::class)
                                                <a href="{{ route('purchases.show', $document->sourceDocument) }}">{{ $document->sourceDocument->purchase_number }}</a>
                                            @else
                                                {{ class_basename($document->source_document_type) }} #{{ $document->source_document_id }}
                                            @endif
                                        @else
                                            <span class="text-muted">Manual</span>
                                        @endif
                                        <div class="text-muted small">{{ optional($document->taxRate)->code ?: 'No tax master' }}</div>
                                    </td>
                                    <td>{{ sprintf('%02d', (int) $document->tax_period_month) }}/{{ $document->tax_period_year }}</td>
                                    <td>
                                        <div>{{ $document->counterparty_name_snapshot ?: '-' }}</div>
                                        <div class="text-muted small">{{ $document->counterparty_tax_id_snapshot ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="small">Base: {{ $money->format((float) $document->taxable_base, $document->currency_code ?: $currency) }}</div>
                                        <div class="small">Tax: {{ $money->format((float) $document->tax_amount, $document->currency_code ?: $currency) }}</div>
                                        @if((float) $document->withheld_amount > 0)
                                            <div class="small">PPh: {{ $money->format((float) $document->withheld_amount, $document->currency_code ?: $currency) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $document->document_status === 'issued' ? 'green' : ($document->document_status === 'draft' ? 'secondary' : 'yellow') }}-lt text-{{ $document->document_status === 'issued' ? 'green' : ($document->document_status === 'draft' ? 'secondary' : 'yellow') }}">
                                            {{ $documentStatusOptions[$document->document_status] ?? $document->document_status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('finance.tax-documents.edit', $document) }}" class="btn btn-icon btn-sm btn-outline-primary">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada tax register.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(method_exists($documents, 'links'))
                <div class="card-footer">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
