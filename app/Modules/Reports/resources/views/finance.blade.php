@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
    $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="mb-0">Finance Reports</h2>
            <div class="text-muted small">Ringkasan kas masuk, kas keluar, pengeluaran, dan output accounting formal sesuai mode UI.</div>
            <div class="text-muted small mt-1">
                Standard mode menampilkan summary operasional. Advanced mode menampilkan trial balance, general ledger, dan balance sheet.
            </div>
        </div>
        @include('shared.accounting.mode-badge')
    </div>
</div>

@include('reports::partials.nav')

<div class="card mb-3">
    <div class="card-body">
        <div class="alert alert-info mb-3">Finance report mengikuti active company/branch context dari topbar switcher, bukan filter outlet manual.</div>
        <div class="alert alert-secondary">
            <strong>Mode mapping:</strong>
            standard = cashflow, profit/loss sederhana, expense analytics.
            advanced = seluruh standard + trial balance, general ledger, balance sheet.
        </div>
        <form method="GET" class="row g-3">
            <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><label class="form-label">Category</label><select name="finance_category_id" class="form-select"><option value="">All</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $filters['finance_category_id'] === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="transaction_type" class="form-control" value="{{ $filters['transaction_type'] }}" placeholder="cash_in, cash_out, expense"></div>
            <div class="col-md-3"><label class="form-label">GL Account</label><select name="account_code" class="form-select"><option value="">All</option>@foreach($accountOptions as $accountOption)<option value="{{ $accountOption->account_code }}" @selected($filters['account_code'] === $accountOption->account_code)>{{ $accountOption->account_code }} - {{ $accountOption->account_name }}</option>@endforeach</select></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('reports.finance') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash In</div><div class="fs-2 fw-bold text-success">{{ $money->format((float) $summary['cash_in_total'], $currency) }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Cash Out + Expense</div><div class="fs-2 fw-bold text-danger">{{ $money->format((float) $summary['cash_out_total'], $currency) }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Net</div><div class="fs-2 fw-bold {{ $summary['net_total'] >= 0 ? 'text-primary' : 'text-danger' }}">{{ $money->format((float) $summary['net_total'], $currency) }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Arus Kas</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating inflow</span><span>{{ $money->format((float) $cashFlowSummary['operating_inflow'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating outflow</span><span>{{ $money->format((float) $cashFlowSummary['operating_outflow'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Expense outflow</span><span>{{ $money->format((float) $cashFlowSummary['expense_outflow'], $currency) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-semibold"><span>Net cash flow</span><span>{{ $money->format((float) $cashFlowSummary['net_cash_flow'], $currency) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Laba Rugi Sederhana</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Revenue</span><span>{{ $money->format((float) $profitLoss['revenue'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Estimated COGS</span><span>{{ $money->format((float) $profitLoss['estimated_cogs'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Gross profit</span><span>{{ $money->format((float) $profitLoss['gross_profit'], $currency) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Operating expenses</span><span>{{ $money->format((float) $profitLoss['operating_expenses'], $currency) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-semibold"><span>Net profit</span><span>{{ $money->format((float) $profitLoss['net_profit'], $currency) }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Cash In / Out by Date</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Date</th><th>Cash In</th><th>Cash Out</th></tr></thead><tbody>
            @forelse($cashInOut as $row)
                <tr><td>{{ \Illuminate\Support\Carbon::parse($row->report_date)->format('d/m/Y') }}</td><td>{{ $money->format((float) $row->cash_in_total, $currency) }}</td><td>{{ $money->format((float) $row->cash_out_total, $currency) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h3 class="card-title mb-0">Expense by Category</h3></div><div class="table-responsive"><table class="table table-vcenter"><thead><tr><th>Category</th><th>Transactions</th><th>Total</th></tr></thead><tbody>
            @forelse($expenseByCategory as $row)
                <tr><td>{{ $row->category_name }}</td><td>{{ $row->transaction_count }}</td><td>{{ $money->format((float) $row->total_amount, $currency) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody></table></div></div>
    </div>
</div>

@if($isAdvancedMode)
    <div class="row g-3 mt-1">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Trial Balance</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $trialDebitTotal = 0.0;
                                $trialCreditTotal = 0.0;
                            @endphp
                            @forelse($trialBalance as $row)
                                @php
                                    $trialDebitTotal += (float) $row->debit_total;
                                    $trialCreditTotal += (float) $row->credit_total;
                                @endphp
                                <tr>
                                    <td>{{ $row->account_code }} - {{ $row->account_name }}</td>
                                    <td class="text-end">{{ $money->format((float) $row->debit_total, $currency) }}</td>
                                    <td class="text-end">{{ $money->format((float) $row->credit_total, $currency) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Belum ada journal posted.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ $money->format($trialDebitTotal, $currency) }}</td>
                                <td class="text-end">{{ $money->format($trialCreditTotal, $currency) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">General Ledger Summary</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Lines</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledgerSummary as $row)
                                <tr>
                                    <td>{{ $row->account_code }} - {{ $row->account_name }}</td>
                                    <td class="text-end">{{ $row->line_count }}</td>
                                    <td class="text-end">{{ $money->format((float) $row->debit_total, $currency) }}</td>
                                    <td class="text-end">{{ $money->format((float) $row->credit_total, $currency) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Belum ada journal posted.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">General Ledger</h3>
            @if($filters['account_code'])
                <span class="text-muted small">Filter account: {{ $filters['account_code'] }}</span>
            @else
                <span class="text-muted small">Tampilkan semua account posted journal dalam periode aktif.</span>
            @endif
        </div>
        <div class="card-body">
            @forelse($generalLedger as $accountCode => $entries)
                @php
                    $accountName = $entries->first()->account_name ?? '';
                    $runningBalance = 0.0;
                @endphp
                <div class="@if(!$loop->last) mb-4 pb-3 border-bottom @endif">
                    <div class="fw-semibold mb-2">{{ $accountCode }} - {{ $accountName }}</div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Journal</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    @php
                                        $runningBalance += (float) $entry->debit - (float) $entry->credit;
                                    @endphp
                                    <tr>
                                        <td>{{ optional(\Illuminate\Support\Carbon::parse($entry->entry_date))->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div>{{ $entry->journal_number ?: '-' }}</div>
                                            <div class="text-muted small">{{ $entry->entry_type }} / {{ strtoupper($entry->status) }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $entry->description ?: '-' }}</div>
                                            @if(data_get($entry, 'line_meta.notes'))
                                                <div class="text-muted small">{{ data_get($entry, 'line_meta.notes') }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $money->format((float) $entry->debit, $currency) }}</td>
                                        <td class="text-end">{{ $money->format((float) $entry->credit, $currency) }}</td>
                                        <td class="text-end">{{ $money->format($runningBalance, $currency) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-muted">Belum ada data general ledger untuk periode ini.</div>
            @endforelse
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h3 class="card-title mb-0">Balance Sheet</h3>
                <div class="text-muted small">{{ $balanceSheet['basis'] }}</div>
            </div>
            <span class="badge {{ $balanceSheet['is_balanced'] ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">
                {{ $balanceSheet['is_balanced'] ? 'BALANCED' : 'PROVISIONAL' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-0">Assets</h4></div>
                        <div class="card-body">
                            @forelse($balanceSheet['assets'] as $group => $rows)
                                <div class="@if(!$loop->last) mb-3 pb-3 border-bottom @endif">
                                    <div class="fw-semibold mb-2">{{ $group }}</div>
                                    @foreach($rows as $row)
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">{{ $row['account_code'] }} - {{ $row['account_name'] }}</span>
                                            <span>{{ $money->format((float) $row['balance'], $currency) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-muted">Belum ada akun asset yang dapat diklasifikasikan.</div>
                            @endforelse
                            <hr>
                            <div class="d-flex justify-content-between fw-semibold">
                                <span>Total Assets</span>
                                <span>{{ $money->format((float) $balanceSheet['asset_total'], $currency) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h4 class="card-title mb-0">Liabilities</h4></div>
                        <div class="card-body">
                            @forelse($balanceSheet['liabilities'] as $group => $rows)
                                <div class="@if(!$loop->last) mb-3 pb-3 border-bottom @endif">
                                    <div class="fw-semibold mb-2">{{ $group }}</div>
                                    @foreach($rows as $row)
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">{{ $row['account_code'] }} - {{ $row['account_name'] }}</span>
                                            <span>{{ $money->format((float) $row['balance'], $currency) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-muted">Belum ada akun liability yang dapat diklasifikasikan.</div>
                            @endforelse
                            <hr>
                            <div class="d-flex justify-content-between fw-semibold">
                                <span>Total Liabilities</span>
                                <span>{{ $money->format((float) $balanceSheet['liability_total'], $currency) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-0">Equity</h4></div>
                        <div class="card-body">
                            @forelse($balanceSheet['equity'] as $group => $rows)
                                <div class="@if(!$loop->last) mb-3 pb-3 border-bottom @endif">
                                    <div class="fw-semibold mb-2">{{ $group }}</div>
                                    @foreach($rows as $row)
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">{{ $row['account_code'] }} - {{ $row['account_name'] }}</span>
                                            <span>{{ $money->format((float) $row['balance'], $currency) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-muted">Belum ada akun equity yang dapat diklasifikasikan.</div>
                            @endforelse
                            <hr>
                            <div class="d-flex justify-content-between fw-semibold">
                                <span>Total Equity</span>
                                <span>{{ $money->format((float) $balanceSheet['equity_total'], $currency) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-semibold mt-2">
                                <span>Total Liabilities + Equity</span>
                                <span>{{ $money->format((float) $balanceSheet['liability_and_equity_total'], $currency) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-secondary mt-3 mb-0">
        Standard mode menampilkan finance summary inti. Pindah ke <strong>Advanced mode</strong> untuk membuka governance report seperti trial balance, general ledger, dan balance sheet.
    </div>
@endif
@endsection
