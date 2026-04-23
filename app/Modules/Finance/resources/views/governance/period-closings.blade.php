@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp
<div class="container-xl">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Buat Period Closing</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        Closing akan membuat journal `period_closing` untuk menutup akun laba/rugi ke retained earnings, lalu mengunci periode.
                    </div>
                    <form method="POST" action="{{ route('finance.period-closings.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Tanggal mulai</label>
                            <input type="date" name="period_start" class="form-control @error('period_start') is-invalid @enderror" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required>
                            @error('period_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tanggal akhir</label>
                            <input type="date" name="period_end" class="form-control @error('period_end') is-invalid @enderror" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required>
                            @error('period_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">Company level / semua branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-hint">Pilih branch jika closing dan lock ingin dipisah per branch.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">Close Period</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <h3 class="card-title mb-0">Period Closings</h3>
                        <div class="text-muted small">Closing journal menjadi bukti retained earnings permanen untuk periode yang ditutup.</div>
                    </div>
                    <a href="{{ route('finance.period-locks.index') }}" class="btn btn-outline-secondary btn-sm">Lihat Period Locks</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Status</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Expense</th>
                                <th class="text-end">Net Income</th>
                                <th>Journal</th>
                                <th>Closed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($closings as $closing)
                                <tr>
                                    <td>
                                        <div>{{ $closing->period_start->format('Y-m-d') }} s/d {{ $closing->period_end->format('Y-m-d') }}</div>
                                        <div class="text-muted small">{{ $closing->closing_scope_key }}</div>
                                    </td>
                                    <td><span class="badge bg-green-lt text-green">{{ strtoupper($closing->status) }}</span></td>
                                    <td class="text-end">{{ $money->format((float) $closing->revenue_total, $currency) }}</td>
                                    <td class="text-end">{{ $money->format((float) $closing->expense_total, $currency) }}</td>
                                    <td class="text-end {{ (float) $closing->net_income >= 0 ? 'text-green' : 'text-red' }}">{{ $money->format((float) $closing->net_income, $currency) }}</td>
                                    <td>
                                        @if($closing->closingJournal)
                                            <a href="{{ route('finance.journals.show', $closing->closingJournal->id) }}">{{ $closing->closingJournal->journal_number ?: ('Journal #' . $closing->closingJournal->id) }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $closing->closer ? $closing->closer->name : '-' }}</div>
                                        <div class="text-muted small">{{ $closing->closed_at ? $closing->closed_at->format('Y-m-d H:i') : '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada period closing.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $closings->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
