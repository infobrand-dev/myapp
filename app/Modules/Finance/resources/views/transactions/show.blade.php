@extends('layouts.admin')

@section('title', 'Detail Transaksi — ' . $transaction->transaction_number)

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';

    $typeConfig = match($transaction->transaction_type) {
        'cash_in'  => ['label' => 'Cash In',  'badge' => 'bg-green-lt text-green',   'icon' => 'ti-arrow-down-circle', 'color' => 'green'],
        'cash_out' => ['label' => 'Cash Out', 'badge' => 'bg-red-lt text-red',       'icon' => 'ti-arrow-up-circle',   'color' => 'red'],
        'expense'  => ['label' => 'Expense',  'badge' => 'bg-orange-lt text-orange', 'icon' => 'ti-receipt',           'color' => 'orange'],
        default    => ['label' => $transaction->transaction_type, 'badge' => 'bg-secondary-lt text-secondary', 'icon' => 'ti-circle', 'color' => 'secondary'],
    };
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan &rsaquo; Transaksi</div>
            <h2 class="page-title">{{ $transaction->transaction_number }}</h2>
            <p class="text-muted mb-0">
                <span class="badge {{ $typeConfig['badge'] }} me-1">{{ $typeConfig['label'] }}</span>
                @if($transaction->isTransfer())
                    <span class="badge bg-azure-lt text-azure me-1">Transfer</span>
                @endif
                {{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y, H:i') : '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @include('shared.accounting.mode-badge')
            <a href="{{ route('finance.transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-pencil me-1"></i>Edit
            </a>
            <form class="d-inline-block m-0" method="POST" action="{{ route('finance.transactions.destroy', $transaction) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"
                    data-confirm="Hapus transaksi {{ $transaction->transaction_number }}?">
                    <i class="ti ti-trash me-1"></i>Hapus
                </button>
            </form>
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

{{-- Amount Hero --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col">
                <div class="text-muted small text-uppercase fw-medium mb-1" style="letter-spacing:.06em;">Jumlah Transaksi</div>
                <div class="fs-1 fw-bold text-{{ $typeConfig['color'] }}">
                    {{ $money->format((float) $transaction->amount, $currency) }}
                </div>
            </div>
            <div class="col-auto">
                <i class="ti {{ $typeConfig['icon'] }}"
                    style="font-size:3rem; color:var(--tblr-{{ $typeConfig['color'] }});"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Detail Transaksi --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Transaction Details</h3>
            </div>
            <div class="card-body p-0">

                {{-- Group 1: Type & Date --}}
                <div class="px-4 py-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-tag me-1"></i>Type
                            </div>
                            <span class="badge {{ $typeConfig['badge'] }}">{{ $typeConfig['label'] }}</span>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-calendar me-1"></i>Date
                            </div>
                            <div>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y, H:i') : '-' }}</div>
                        </div>
                    </div>
                </div>

                <hr class="m-0">

                {{-- Group 2: Account & Category --}}
                <div class="px-4 py-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-building-bank me-1"></i>Account
                            </div>
                            <div>{{ $transaction->account?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-folder me-1"></i>Category
                            </div>
                            <div>{{ $transaction->category?->name ?? '-' }}</div>
                        </div>
                        @if($transaction->isTransfer())
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">
                                    <i class="ti ti-arrows-transfer-up me-1"></i>Target Account
                                </div>
                                <div>{{ $transaction->counterpartyAccount?->name ?? $transaction->transferPair?->account?->name ?? '-' }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <hr class="m-0">

                {{-- Group 3: Branch & Shift --}}
                <div class="px-4 py-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-map-pin me-1"></i>Branch
                            </div>
                            <div>{{ $transaction->branch?->name ?? '-' }}</div>
                        </div>
                        @if($shiftEnabled)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">
                                <i class="ti ti-clock me-1"></i>Shift
                            </div>
                            <div>{{ $transaction->shift?->code ?? '-' }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                @if($transaction->notes)
                <hr class="m-0">

                {{-- Group 4: Notes --}}
                <div class="px-4 py-3">
                    <div class="text-muted small mb-1">
                        <i class="ti ti-notes me-1"></i>Notes
                    </div>
                    <div class="text-body">{{ $transaction->notes }}</div>
                </div>
                @endif

                @if($transaction->attachment_path)
                <hr class="m-0">
                <div class="px-4 py-3">
                    <div class="text-muted small mb-1">
                        <i class="ti ti-paperclip me-1"></i>Attachment
                    </div>
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($transaction->attachment_path) }}" target="_blank" rel="noreferrer">Lihat bukti transaksi</a>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Audit --}}
    <div class="col-lg-4">
        @include('shared.accounting.audit-summary', [
            'entries' => [
                ['label' => 'Dibuat oleh', 'user' => $transaction->creator, 'timestamp' => $transaction->created_at, 'icon' => 'ti-user-plus', 'color' => 'green'],
                ['label' => 'Diubah terakhir', 'user' => $transaction->updater, 'timestamp' => $transaction->updated_at, 'icon' => 'ti-user-edit', 'color' => 'blue'],
            ],
        ])
    </div>
</div>
@include('shared.accounting.activity-log', [
    'activities' => $activities,
    'fieldLabels' => [
        'transaction_type' => 'Type',
        'transaction_date' => 'Date',
        'amount' => 'Amount',
        'finance_account_id' => 'Account',
        'finance_category_id' => 'Category',
        'notes' => 'Notes',
        'counterparty_finance_account_id' => 'Target Account',
        'attachment_path' => 'Attachment',
        'branch_id' => 'Branch',
        'pos_cash_session_id' => 'Shift',
    ],
    'money' => $money,
    'currency' => $currency,
])

@endsection
