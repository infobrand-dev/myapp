@extends('layouts.admin')

@section('title', 'Detail Transaksi — ' . $transaction->transaction_number)

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();

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
                {{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y, H:i') : '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
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
                <h3 class="card-title">Detail Transaksi</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 fw-normal text-muted">Tipe</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $typeConfig['badge'] }}">{{ $typeConfig['label'] }}</span>
                    </dd>

                    <dt class="col-sm-4 fw-normal text-muted">Tanggal</dt>
                    <dd class="col-sm-8">{{ $transaction->transaction_date ? $transaction->transaction_date->format('d M Y, H:i') : '-' }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Akun</dt>
                    <dd class="col-sm-8">{{ $transaction->account?->name ?? '-' }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Kategori</dt>
                    <dd class="col-sm-8">{{ $transaction->category?->name ?? '-' }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Cabang</dt>
                    <dd class="col-sm-8">{{ $transaction->branch?->name ?? '-' }}</dd>

                    @if($shiftEnabled && $transaction->shift)
                        <dt class="col-sm-4 fw-normal text-muted">Shift</dt>
                        <dd class="col-sm-8">{{ $transaction->shift->code }}</dd>
                    @endif

                    <dt class="col-sm-4 fw-normal text-muted">Catatan</dt>
                    <dd class="col-sm-8 mb-0">{{ $transaction->notes ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Audit --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Audit</h3>
            </div>
            <div class="card-body d-flex flex-column gap-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="avatar avatar-sm bg-green-lt flex-shrink-0">
                        <i class="ti ti-user-plus" style="font-size:.95rem; color:var(--tblr-green);"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Dibuat oleh</div>
                        <div class="fw-medium">{{ $transaction->creator?->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $transaction->created_at?->format('d M Y, H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-start gap-3">
                    <span class="avatar avatar-sm bg-blue-lt flex-shrink-0">
                        <i class="ti ti-user-edit" style="font-size:.95rem; color:var(--tblr-blue);"></i>
                    </span>
                    <div>
                        <div class="text-muted small">Diubah oleh</div>
                        <div class="fw-medium">{{ $transaction->updater?->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $transaction->updated_at?->format('d M Y, H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
