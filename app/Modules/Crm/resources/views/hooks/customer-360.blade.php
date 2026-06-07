@php($money = app(\App\Support\MoneyFormatter::class))
<div class="row g-3 mt-1">
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Customer Snapshot</h3></div>
            <div class="card-body">
                <div class="fw-semibold">{{ $contact->name }}</div>
                <div class="small text-muted">{{ $contact->email ?: ($contact->mobile ?: $contact->phone ?: 'Tanpa channel utama') }}</div>
                <a href="{{ route('crm.customers.show', $contact) }}" class="btn btn-outline-primary w-100 mt-3">Open Full Customer 360</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Open Deals</h3></div>
            <div class="card-body">
                @forelse($customer360['openDeals'] as $deal)
                    <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                        <a href="{{ route('crm.show', $deal) }}" class="fw-semibold text-decoration-none">{{ $deal->title }}</a>
                        <div class="small text-muted">{{ $deal->stageModel?->name ?? $deal->stage }} • {{ $money->format((float) ($deal->estimated_value ?? 0), $deal->currency) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada open deal.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Pending Follow-Up</h3></div>
            <div class="card-body">
                @forelse($customer360['pendingFollowUps'] as $task)
                    <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $task->subject }}</div>
                        <div class="small text-muted">{{ $task->due_at ? $task->due_at->translatedFormat('d M Y H:i') : 'Tanpa jadwal' }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada follow-up pending.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Recent Timeline</h3></div>
            <div class="card-body">
                @forelse($customer360['timeline'] as $event)
                    <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $event->title }}</div>
                        <div class="small text-muted">{{ $event->occurred_at?->translatedFormat('d M Y H:i') }} • {{ strtoupper($event->source_suite) }}</div>
                        @if($event->description)
                            <div class="small mt-1">{{ $event->description }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted small">Timeline internal CRM belum ada.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        @if($customer360['accounting']['enabled'])
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Recent Quotations</h3></div>
                <div class="card-body">
                    @forelse($customer360['accounting']['recentQuotations'] as $quotation)
                        <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                            <a href="{{ route('sales.quotations.show', $quotation) }}" class="fw-semibold text-decoration-none">{{ $quotation->quotation_number }}</a>
                            <div class="small text-muted">{{ \Illuminate\Support\Str::headline($quotation->status) }} • {{ $money->format((float) $quotation->grand_total, $quotation->currency_code) }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada quotation untuk customer ini.</div>
                    @endforelse
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Open Invoices</h3></div>
                <div class="card-body">
                    @forelse($customer360['accounting']['openInvoices'] as $invoice)
                        <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                            <a href="{{ route('sales.show', $invoice) }}" class="fw-semibold text-decoration-none">{{ $invoice->sale_number }}</a>
                            <div class="small text-muted">Outstanding {{ $money->format((float) $invoice->balance_due, $invoice->currency_code) }} • {{ \Illuminate\Support\Str::headline($invoice->payment_status) }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Tidak ada invoice outstanding.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Recent Payments</h3></div>
                <div class="card-body">
                    @forelse($customer360['accounting']['recentPayments'] as $payment)
                        <div class="{{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                            <a href="{{ route('payments.show', $payment) }}" class="fw-semibold text-decoration-none">{{ $payment->payment_number }}</a>
                            <div class="small text-muted">{{ $money->format((float) $payment->amount, $payment->currency_code) }} • {{ optional($payment->paid_at)->translatedFormat('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Belum ada payment yang tercatat.</div>
                    @endforelse
                </div>
            </div>
        @else
            @foreach($customer360['integrationPlaceholders'] as $suite => $card)
                <div class="card {{ !$loop->last ? 'mb-3' : '' }}">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">{{ strtoupper($suite) }} Bridge</div>
                        <div class="fw-semibold">{{ $card['title'] }}</div>
                        <div class="small text-muted">{{ $card['description'] }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
