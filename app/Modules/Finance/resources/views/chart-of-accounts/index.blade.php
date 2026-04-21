@extends('layouts.admin')

@section('title', 'Chart of Accounts')

@section('content')
@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Accounting</div>
            <h2 class="page-title">Chart of Accounts</h2>
            <p class="text-muted mb-0">Master akun untuk journal governance, trial balance, general ledger, dan neraca.</p>
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
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
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

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Akun COA</h3>
            </div>
            <form method="POST" action="{{ route('finance.chart-accounts.store') }}">
                @csrf
                <div class="card-body">
                    @include('finance::chart-of-accounts.partials.form', [
                        'account' => new \App\Modules\Finance\Models\ChartOfAccount(),
                        'parentOptions' => $parentOptions,
                        'typeOptions' => $typeOptions,
                        'normalBalanceOptions' => $normalBalanceOptions,
                        'reportSectionOptions' => $reportSectionOptions,
                    ])
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan
                    </button>
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
                                <th>Type</th>
                                @if($isAdvancedMode)
                                    <th>Parent</th>
                                    <th>Normal</th>
                                    <th>Section</th>
                                @endif
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td class="fw-semibold">{{ $account->code }}</td>
                                    <td>
                                        <div>{{ $account->name }}</div>
                                        @if($isAdvancedMode && $account->description)
                                            <div class="text-muted small">{{ $account->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $typeOptions[$account->account_type] ?? $account->account_type }}</td>
                                    @if($isAdvancedMode)
                                        <td>{{ $account->parent ? $account->parent->code . ' - ' . $account->parent->name : '-' }}</td>
                                        <td>{{ $normalBalanceOptions[$account->normal_balance] ?? $account->normal_balance }}</td>
                                        <td>{{ $reportSectionOptions[$account->report_section] ?? $account->report_section }}</td>
                                    @endif
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge bg-green-lt text-green">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                        @endif
                                        @if(!$account->is_postable)
                                            <span class="badge bg-yellow-lt text-yellow ms-1">Header</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="table-actions">
                                            <a href="{{ route('finance.chart-accounts.edit', $account) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            <form class="d-inline-block m-0" method="POST" action="{{ route('finance.chart-accounts.destroy', $account) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Hapus" data-confirm="Hapus akun COA ini?">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdvancedMode ? '7' : '5' }}" class="text-center py-5 text-muted">Belum ada akun COA.</td>
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
