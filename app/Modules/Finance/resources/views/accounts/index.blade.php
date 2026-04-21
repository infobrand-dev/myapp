@extends('layouts.admin')

@section('title', 'Finance Accounts')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Finance Accounts</h2>
            <p class="text-muted mb-0">Pisahkan sumber dana operasional seperti cash, bank, dan e-wallet tanpa memisah domain transaksi finance.</p>
        </div>
        <div class="col-auto">
            @include('shared.accounting.mode-badge')
        </div>
    </div>
</div>

@include('finance::partials.accounting-nav')

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
                <h3 class="card-title">Tambah Account</h3>
            </div>
            <form method="POST" action="{{ route('finance.accounts.store') }}">
                @csrf
                <div class="card-body">
                    @include('finance::accounts.partials.form', [
                        'typeOptions' => $typeOptions,
                        'showSlug' => false,
                        'notesRows' => 3,
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
                                <th>Name</th>
                                <th>Type</th>
                                <th>Opening</th>
                                <th>Current</th>
                                <th>Status</th>
                                <th>Transactions</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $account->name }}</div>
                                        @if($account->account_number)
                                            <div class="text-muted small">{{ $account->account_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-azure-lt text-azure">{{ $typeOptions[$account->account_type] ?? $account->account_type }}</span>
                                        @if($account->is_default)
                                            <span class="badge bg-primary-lt text-primary ms-1">Default</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $money->format((float) $account->opening_balance, $currency) }}</div>
                                        <div class="text-muted small">{{ $account->opening_balance_date?->format('d/m/Y') ?? 'No date' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $money->format((float) ($account->current_balance ?? 0), $currency) }}</div>
                                        <a href="{{ route('finance.transactions.cashbook', ['finance_account_id' => $account->id]) }}" class="text-muted small">Lihat cashbook</a>
                                    </td>
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge bg-green-lt text-green">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>{{ $account->transactions_count }}</td>
                                    <td class="text-end align-middle">
                                        <div class="table-actions">
                                            <a href="{{ route('finance.accounts.edit', $account) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            @if($account->transactions_count === 0)
                                                <form class="d-inline-block m-0" method="POST" action="{{ route('finance.accounts.destroy', $account) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Hapus" data-confirm="Hapus account ini?">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn btn-icon btn-sm btn-outline-danger"
                                                    title="Cannot delete because this account is already used in transactions."
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    disabled
                                                >
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="ti ti-building-bank text-muted d-block mb-2" style="font-size:2rem;"></i>
                                        <div class="text-muted mb-2">Belum ada finance account.</div>
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
