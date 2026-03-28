@extends('layouts.admin')

@section('title', 'Platform Invoice')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">{{ $invoice->invoice_number }}</h1>
            <div class="text-muted small mt-1">{{ optional($invoice->tenant)->name }} · {{ optional($invoice->issued_at)->format('d M Y H:i') ?: '-' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('platform.tenants.show', $invoice->tenant) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Detail Tenant
            </a>
            <a href="{{ route('platform.orders.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-receipt me-1"></i>Orders
            </a>
            <a href="{{ $publicInvoiceUrl }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <i class="ti ti-external-link me-1"></i>Lihat Invoice
            </a>
            <form method="POST" action="{{ route('platform.invoices.resend', $invoice) }}">
                @csrf
                <button type="submit" class="btn btn-primary"
                    data-confirm="Kirim ulang email invoice ke tenant?"
                    data-loading="Mengirim...">
                    <i class="ti ti-send me-1"></i>Kirim Ulang Email
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Ringkasan Invoice</h3></div>
                <div class="card-body">
                    @php
                        $invStatusMap = [
                            'paid'    => ['label' => 'Lunas',         'class' => 'bg-success-lt text-success'],
                            'unpaid'  => ['label' => 'Belum dibayar', 'class' => 'bg-warning-lt text-warning'],
                            'overdue' => ['label' => 'Jatuh tempo',   'class' => 'bg-danger-lt text-danger'],
                            'void'    => ['label' => 'Dibatalkan',    'class' => 'bg-secondary-lt text-secondary'],
                        ];
                        $invInfo = $invStatusMap[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-secondary-lt text-secondary'];
                    @endphp
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Status</div>
                        <div class="mt-1"><span class="badge {{ $invInfo['class'] }}">{{ $invInfo['label'] }}</span></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Plan</div>
                        <div class="fw-semibold mt-1">{{ optional($invoice->plan)->name ?? '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Jumlah</div>
                        <div class="fw-semibold mt-1">{{ number_format((float) $invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Jatuh Tempo</div>
                        <div class="fw-semibold mt-1">{{ optional($invoice->due_at)->format('d M Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Dibayar</div>
                        <div class="fw-semibold mt-1">{{ optional($invoice->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-secondary small text-uppercase fw-bold">Payment Gateway</div>
                        <div class="fw-semibold mt-1">{{ $midtransReady ? 'Midtrans siap' : 'Midtrans belum dikonfigurasi' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Invoice Publik</label>
                        <input type="text" class="form-control" readonly value="{{ $publicInvoiceUrl }}">
                    </div>
                    @if($midtransReady && $invoice->status !== 'paid')
                        <div>
                            <label class="form-label">URL Checkout Midtrans</label>
                            <input type="text" class="form-control" readonly value="{{ $publicCheckoutUrl }}">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Item Invoice</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Keterangan</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->name }}</div>
                                        <div class="text-muted small text-uppercase">{{ $item->item_type }}</div>
                                    </td>
                                    <td>{{ $item->description ?: '-' }}</td>
                                    <td class="text-end">{{ number_format((int) $item->quantity, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->unit_price, 0, ',', '.') }} {{ $invoice->currency }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->total_price, 0, ',', '.') }} {{ $invoice->currency }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada item invoice.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Pembayaran Tercatat</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Referensi</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Dibayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments->sortByDesc('id') as $payment)
                                <tr>
                                    <td>{{ $payment->reference ?: '-' }}</td>
                                    <td>{{ $payment->payment_channel ?: '-' }}</td>
                                    <td>{{ number_format((float) $payment->amount, 0, ',', '.') }} {{ $payment->currency }}</td>
                                    <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">Belum ada pembayaran tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
