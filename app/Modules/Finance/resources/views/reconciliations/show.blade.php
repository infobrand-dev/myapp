@extends('layouts.admin')

@section('title', 'Detail Reconciliation')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $statementStatusOptions = \App\Modules\Finance\Models\BankStatementLine::statusOptions();
@endphp

@include('finance::partials.accounting-nav')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Finance / Bank Reconciliation</div>
            <h2 class="page-title">{{ optional($reconciliation->account)->name ?: 'Account' }}</h2>
            <p class="text-muted mb-0">
                {{ $reconciliation->period_start->format('d M Y') }} - {{ $reconciliation->period_end->format('d M Y') }}
                | Status: {{ ucfirst($reconciliation->status) }}
                @if($reconciliation->reviewed_at)
                    | Reviewed {{ $reconciliation->reviewed_at->format('d M Y H:i') }}
                @endif
                @if($reconciliation->completed_at)
                    | Completed {{ $reconciliation->completed_at->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @if(in_array($reconciliation->status, ['reviewed', 'completed'], true))
                <form method="POST" action="{{ route('finance.reconciliations.reopen', $reconciliation) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Alasan reopen" required>
                    <button type="submit" class="btn btn-sm btn-outline-warning">Reopen</button>
                </form>
            @endif
            <a href="{{ route('finance.reconciliations.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Statement Ending</div><div class="fs-3 fw-bold">{{ $money->format((float) $reconciliation->statement_ending_balance, 'IDR') }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Book Closing</div><div class="fs-3 fw-bold">{{ $money->format((float) $reconciliation->book_closing_balance, 'IDR') }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Difference</div><div class="fs-3 fw-bold">{{ $money->format((float) $reconciliation->difference_amount, 'IDR') }}</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted small">Selected Payments</div><div class="fs-3 fw-bold">{{ $money->format((float) $selectedTotal, 'IDR') }}</div></div></div>
    </div>
</div>

@if($statementLines->isNotEmpty())
    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted small">Matched</div><div class="fs-4 fw-bold">{{ $statementSummary['matched'] }}</div></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted small">Suggested</div><div class="fs-4 fw-bold">{{ $statementSummary['suggested'] }}</div></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted small">Open</div><div class="fs-4 fw-bold">{{ $statementSummary['unmatched'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Exception</div><div class="fs-4 fw-bold text-red">{{ $statementSummary['exception'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Ignored</div><div class="fs-4 fw-bold text-yellow">{{ $statementSummary['ignored'] }}</div></div></div></div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Closure Summary</h3></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Cleared Items</div><div class="fs-4 fw-bold">{{ $closureSummary['cleared_item_count'] }}</div><div class="text-muted small mt-1">{{ $money->format((float) $closureSummary['cleared_total'], 'IDR') }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Open Unmatched</div><div class="fs-4 fw-bold {{ $closureSummary['open_unmatched_count'] > 0 ? 'text-yellow' : 'text-green' }}">{{ $closureSummary['open_unmatched_count'] }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Exceptions</div><div class="fs-4 fw-bold {{ $closureSummary['exception_count'] > 0 ? 'text-red' : 'text-green' }}">{{ $closureSummary['exception_count'] }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-muted small">Difference</div><div class="fs-4 fw-bold">{{ $money->format((float) $closureSummary['difference_amount'], 'IDR') }}</div><div class="text-muted small mt-1">Ignored: {{ $closureSummary['ignored_count'] }}</div></div></div>
        </div>
        @if(($closureSummary['open_unmatched_count'] + $closureSummary['exception_count']) > 0)
            <div class="alert alert-warning mt-3 mb-0">Masih ada item unresolved. Sesi bisa direview dulu, atau force complete bila penutupan operasional memang harus dilakukan.</div>
        @endif
    </div>
</div>

@if($reconciliation->status === 'draft')
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Import Bank Statement</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('finance.reconciliations.import-statement', $reconciliation) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-lg-6">
                    <label class="form-label">File Statement</label>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt,.xlsx" required>
                    <div class="form-hint">Header minimum: tanggal transaksi dan <code>amount</code> atau pasangan <code>debit</code>/<code>credit</code>. Alias tanggal umum dan kolom referensi dasar juga didukung.</div>
                </div>
                <div class="col-lg-6">
                    <div class="text-muted small mb-2">Import terakhir</div>
                    @forelse($reconciliation->statementImports->take(3) as $importBatch)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $importBatch->original_name }}</div>
                            <div class="text-muted small">{{ $importBatch->imported_rows }} rows | {{ optional($importBatch->created_at)->format('d M Y H:i') ?: '-' }}</div>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada statement yang diimport.</div>
                    @endforelse
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-outline-primary">Import Statement</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($statementLines->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Statement Lines</h3>
            <div class="text-muted small">Imported: {{ $statementLines->count() }} rows</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            @if($reconciliation->status === 'draft')
                                <th class="w-1"></th>
                            @endif
                            <th>Date</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Suggestion</th>
                            @if($reconciliation->status === 'draft')
                                <th>Manual Match</th>
                                <th>Exception</th>
                            @endif
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statementLines as $line)
                            @php
                                $duplicateCandidate = data_get($line->meta, 'duplicate_candidate');
                                $suggestionReasonLabels = [
                                    'amount_match' => 'Amount cocok',
                                    'reference_match' => 'Referensi cocok',
                                    'description_match' => 'Deskripsi cocok',
                                    'context_match' => 'Konteks statement cocok',
                                    'date_near' => 'Tanggal sangat dekat',
                                    'date_close' => 'Tanggal masih dekat',
                                    'direction_match' => 'Arah transaksi cocok',
                                    'bank_fee_keyword_match' => 'Keyword biaya bank cocok',
                                    'transfer_keyword_match' => 'Keyword transfer cocok',
                                    'refund_keyword_match' => 'Keyword refund cocok',
                                    'payment_penalty_for_bank_keyword' => 'Diturunkan karena terlihat seperti fee/transfer',
                                    'ambiguous_candidates' => 'Ada kandidat lain yang hampir sama',
                                ];
                                $suggestionReason = collect(data_get($line->meta, 'suggestion_reason', []))
                                    ->map(fn ($reason) => $suggestionReasonLabels[$reason] ?? $reason)
                                    ->implode(', ');
                            @endphp
                            <tr>
                                @if($reconciliation->status === 'draft')
                                    <td>
                                        @if(
                                            $line->match_status !== \App\Modules\Finance\Models\BankStatementLine::MATCH_STATUS_EXCEPTION
                                            && $line->match_status !== \App\Modules\Finance\Models\BankStatementLine::MATCH_STATUS_IGNORED
                                        )
                                            <input type="checkbox" name="statement_line_ids[]" value="{{ $line->id }}" form="reconciliation-complete-form" @checked(in_array((int) $line->id, $matchedStatementLineIds, true))>
                                        @endif
                                    </td>
                                @endif
                                <td>{{ optional($line->transaction_date)->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div>{{ $line->description ?: '-' }}</div>
                                    <div class="text-muted small">{{ optional($line->importBatch)->original_name ?: '-' }}</div>
                                    @if($duplicateCandidate)
                                        <div class="mt-1">
                                            <span class="badge bg-orange-lt text-orange">Duplicate Candidate</span>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $line->reference_number ?: '-' }}</td>
                                <td>{{ $money->format((float) $line->amount, 'IDR') }}</td>
                                <td>
                                    @if($line->suggestedReconcilable)
                                        @if($line->suggested_reconcilable_type === \App\Modules\Payments\Models\Payment::class)
                                            <div><a href="{{ route('payments.show', $line->suggestedReconcilable) }}">{{ $line->suggestedReconcilable->payment_number }}</a></div>
                                            <div class="text-muted small">Target: Payment</div>
                                        @elseif($line->suggested_reconcilable_type === \App\Modules\Finance\Models\FinanceTransaction::class)
                                            <div><a href="{{ route('finance.transactions.show', $line->suggestedReconcilable) }}">{{ $line->suggestedReconcilable->transaction_number }}</a></div>
                                            <div class="text-muted small">Target: Finance Tx</div>
                                        @endif
                                        <div class="text-muted small">Score: {{ $line->match_score }}</div>
                                        @if($suggestionReason !== '')
                                            <div class="text-muted small">{{ $suggestionReason }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">No suggestion</span>
                                    @endif
                                </td>
                                @if($reconciliation->status === 'draft')
                                    <td style="min-width: 280px;">
                                        @php
                                            $defaultType = '';
                                            if ($line->suggested_reconcilable_type === \App\Modules\Payments\Models\Payment::class) {
                                                $defaultType = 'payment';
                                            } elseif ($line->suggested_reconcilable_type === \App\Modules\Finance\Models\FinanceTransaction::class) {
                                                $defaultType = 'finance_transaction';
                                            }
                                            $defaultId = (int) ($line->suggested_reconcilable_id ?: 0);
                                        @endphp
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <select name="statement_matches[{{ $line->id }}][target_type]" class="form-select" form="reconciliation-complete-form">
                                                    <option value="">Skip</option>
                                                    <option value="payment" @selected($defaultType === 'payment')>Payment</option>
                                                    <option value="finance_transaction" @selected($defaultType === 'finance_transaction')>Finance Tx</option>
                                                </select>
                                            </div>
                                            <div class="col-7">
                                                <select name="statement_matches[{{ $line->id }}][target_id]" class="form-select" form="reconciliation-complete-form">
                                                    <option value="">Pilih target</option>
                                                    @foreach($candidatePayments as $payment)
                                                        <option value="{{ $payment->id }}" @selected($defaultType === 'payment' && $defaultId === (int) $payment->id)>
                                                            Payment: {{ $payment->payment_number }} | {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                    @foreach($candidateFinanceTransactions as $transaction)
                                                        <option value="{{ $transaction->id }}" @selected($defaultType === 'finance_transaction' && $defaultId === (int) $transaction->id)>
                                                            Finance Tx: {{ $transaction->transaction_number }} | {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-1">Gunakan manual override untuk mutasi yang bukan payment.</div>
                                    </td>
                                    <td style="min-width: 260px;">
                                        <form method="POST" action="{{ route('finance.reconciliations.statement-lines.resolve', [$reconciliation, $line]) }}" class="row g-2">
                                            @csrf
                                            <div class="col-12">
                                                <select name="status" class="form-select">
                                                    <option value="unmatched" @selected($line->match_status === \App\Modules\Finance\Models\BankStatementLine::MATCH_STATUS_UNMATCHED)>Open</option>
                                                    <option value="exception" @selected($line->match_status === \App\Modules\Finance\Models\BankStatementLine::MATCH_STATUS_EXCEPTION)>Exception</option>
                                                    <option value="ignored" @selected($line->match_status === \App\Modules\Finance\Models\BankStatementLine::MATCH_STATUS_IGNORED)>Ignored</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <select name="resolution_reason" class="form-select">
                                                    <option value="">Pilih reason</option>
                                                    @foreach($resolutionReasonOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($line->resolution_reason === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <textarea name="resolution_note" class="form-control" rows="2" placeholder="Catatan exception / ignore">{{ $line->resolution_note }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </td>
                                @endif
                                <td>
                                    @if($line->match_status === 'matched')
                                        <span class="badge bg-green-lt text-green">Matched</span>
                                    @elseif($line->match_status === 'exception')
                                        <span class="badge bg-red-lt text-red">Exception</span>
                                        @if($line->resolution_reason)
                                            <div class="text-muted small mt-1">{{ $line->resolution_reason }}</div>
                                        @endif
                                    @elseif($line->match_status === 'ignored')
                                        <span class="badge bg-yellow-lt text-yellow">Ignored</span>
                                        @if($line->resolution_reason)
                                            <div class="text-muted small mt-1">{{ $line->resolution_reason }}</div>
                                        @endif
                                    @elseif($line->match_status === 'suggested')
                                        <span class="badge bg-azure-lt text-azure">Suggested</span>
                                        @if($duplicateCandidate)
                                            <div class="text-muted small mt-1">Periksa duplikat sebelum complete.</div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">Unmatched</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Candidate Payments</h3>
        <div class="text-muted small">Total candidate: {{ $money->format((float) $candidateTotal, 'IDR') }}</div>
    </div>
    <div class="card-body p-0">
        @if($reconciliation->status === 'draft')
            <form method="POST" action="{{ route('finance.reconciliations.review', $reconciliation) }}" id="reconciliation-complete-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1"></th>
                                <th>Payment</th>
                                <th>Method</th>
                                <th>Paid At</th>
                                <th>Amount</th>
                                <th>Allocation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidatePayments as $payment)
                                <tr>
                                    <td><input type="checkbox" name="payment_ids[]" value="{{ $payment->id }}" @checked(in_array((int) $payment->id, $selectedPaymentIds, true))></td>
                                    <td>
                                        <div class="fw-semibold"><a href="{{ route('payments.show', $payment) }}">{{ $payment->payment_number }}</a></div>
                                        <div class="text-muted small">{{ $payment->reference_number ?: ($payment->external_reference ?: '-') }}</div>
                                    </td>
                                    <td>{{ optional($payment->method)->name ?: '-' }}</td>
                                    <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                                    <td>{{ $money->format((float) $payment->amount, $payment->currency_code ?: 'IDR') }}</td>
                                    <td>
                                        @php $firstAllocation = $payment->allocations->first(); @endphp
                                        @if($firstAllocation && $firstAllocation->payable)
                                            {{ class_basename($firstAllocation->payable_type) }}:
                                            {{ $firstAllocation->payable->sale_number ?? $firstAllocation->payable->purchase_number ?? $firstAllocation->payable->return_number ?? '#' . $firstAllocation->payable_id }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Tidak ada payment candidate untuk account dan periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $reconciliation->notes) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Review Note</label>
                        <textarea name="review_note" class="form-control" rows="2" placeholder="Catatan untuk tahap review">{{ old('review_note') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Review Reconciliation</button>
                </div>
            </form>
        @elseif($reconciliation->status === 'reviewed')
            <div class="card-body border-top">
                <div class="alert alert-info">Sesi sudah masuk tahap review. Periksa closure summary sebelum complete.</div>
                <form method="POST" action="{{ route('finance.reconciliations.complete', $reconciliation) }}" class="row g-3">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label">Closure Reason</label>
                        <input type="text" name="closure_reason" class="form-control" placeholder="Contoh: unmatched dianggap timing difference akhir periode">
                    </div>
                    <div class="col-md-3">
                        <label class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" name="force_complete" value="1">
                            <span class="form-check-label">Force complete</span>
                        </label>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Complete</button>
                    </div>
                </form>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Payment</th>
                            <th>Cleared Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliation->items as $item)
                            <tr>
                                <td>
                                    @if($item->reconcilable)
                                        @if($item->reconcilable_type === \App\Modules\Payments\Models\Payment::class)
                                            <a href="{{ route('payments.show', $item->reconcilable) }}">{{ $item->reconcilable->payment_number }}</a>
                                        @elseif($item->reconcilable_type === \App\Modules\Finance\Models\FinanceTransaction::class)
                                            <a href="{{ route('finance.transactions.show', $item->reconcilable) }}">{{ $item->reconcilable->transaction_number }}</a>
                                        @else
                                            {{ class_basename($item->reconcilable_type) }} #{{ $item->reconcilable_id }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ optional($item->cleared_date)->format('d M Y') ?: '-' }}</td>
                                <td>{{ $money->format((float) $item->cleared_amount, 'IDR') }}</td>
                                <td><span class="badge bg-green-lt text-green">{{ ucfirst($item->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Belum ada item reconciliation.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
