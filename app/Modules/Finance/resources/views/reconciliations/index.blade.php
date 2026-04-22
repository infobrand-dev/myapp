@extends('layouts.admin')

@section('title', 'Bank Reconciliation')

@section('content')
@include('finance::partials.accounting-nav')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Finance</div>
            <h2 class="page-title">Bank Reconciliation</h2>
            <p class="text-muted mb-0">Buat sesi reconciliation formal per account dan periode agar status reconciled tidak lagi diisi manual per payment.</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Buat Sesi</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.reconciliations.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Finance Account</label>
                        <select name="finance_account_id" class="form-select" required>
                            <option value="">Pilih account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('finance_account_id') == $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Period Start</label>
                        <input type="date" name="period_start" class="form-control" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Period End</label>
                        <input type="date" name="period_end" class="form-control" value="{{ old('period_end', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Statement Ending Balance</label>
                        <input type="number" step="0.01" name="statement_ending_balance" class="form-control" value="{{ old('statement_ending_balance', '0') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Buat Reconciliation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Periode</th>
                                <th>Statement</th>
                                <th>Book</th>
                                <th>Diff</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconciliations as $reconciliation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($reconciliation->account)->name ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional($reconciliation->branch)->name ?: 'All Branch Scope' }}</div>
                                    </td>
                                    <td>{{ $reconciliation->period_start->format('d M Y') }} - {{ $reconciliation->period_end->format('d M Y') }}</td>
                                    <td>{{ number_format((float) $reconciliation->statement_ending_balance, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $reconciliation->book_closing_balance, 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) $reconciliation->difference_amount, 2, ',', '.') }}</td>
                                    <td>
                                        @if($reconciliation->status === 'completed')
                                            <span class="badge bg-green-lt text-green">Completed</span>
                                        @else
                                            <span class="badge bg-azure-lt text-azure">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.reconciliations.show', $reconciliation) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada sesi reconciliation.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
            {{ $reconciliations->links() }}
        </div>
    </div>
</div>
@endsection
