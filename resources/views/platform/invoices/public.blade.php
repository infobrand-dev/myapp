<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-light">
    @php
        $money = app(\App\Support\MoneyFormatter::class);
    @endphp
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if(session('error'))
                    <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
                @endif
                @if(session('status'))
                    <div class="alert alert-success shadow-sm">{{ session('status') }}</div>
                @endif
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <div class="text-secondary text-uppercase fw-bold small">Platform Invoice</div>
                                <h1 class="h3 mb-1">{{ $invoice->invoice_number }}</h1>
                                <div class="text-muted">{{ optional($invoice->tenant)->name }}</div>
                            </div>
                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary text-uppercase fw-bold small">Plan</div>
                                    <div class="fw-semibold mt-1">{{ optional($invoice->plan)->display_name ?? optional($invoice->plan)->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary text-uppercase fw-bold small">Amount</div>
                                    <div class="fw-semibold mt-1">{{ $money->format((float) $invoice->amount, $invoice->currency) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary text-uppercase fw-bold small">Issued At</div>
                                    <div class="fw-semibold mt-1">{{ optional($invoice->issued_at)->format('d M Y H:i') ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary text-uppercase fw-bold small">Due At</div>
                                    <div class="fw-semibold mt-1">{{ optional($invoice->due_at)->format('d M Y H:i') ?: '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-secondary text-uppercase fw-bold small mb-2">Invoice Items</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Description</th>
                                            <th class="text-end">Qty</th>
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
                                                <td class="text-end">{{ $money->format((float) $item->total_price, $invoice->currency) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-muted">Belum ada item invoice.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if($invoice->status !== 'paid')
                            <div class="border rounded p-4 mb-4 bg-white">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">Pembayaran online</div>
                                        <div class="text-muted small">
                                            Bayar invoice digital ini melalui Midtrans. Setelah pembayaran sukses, langganan akan aktif otomatis.
                                        </div>
                                    </div>
                                    @if($midtransReady)
                                        <form method="POST" action="{{ $publicCheckoutUrl }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">Bayar via Midtrans</button>
                                        </form>
                                    @else
                                        <div class="text-muted small">Midtrans belum tersedia untuk invoice ini.</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($invoice->payments->isNotEmpty())
                            <div class="mb-4">
                                <div class="text-secondary text-uppercase fw-bold small mb-2">Payment History</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Reference</th>
                                                <th>Channel</th>
                                                <th>Amount</th>
                                                <th>Paid At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->payments->sortByDesc('id') as $payment)
                                                <tr>
                                                    <td>{{ $payment->reference ?: '-' }}</td>
                                                    <td>{{ $payment->payment_channel ?: '-' }}</td>
                                                    <td>{{ $money->format((float) $payment->amount, $payment->currency) }}</td>
                                                    <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="border-top pt-3 text-muted small">
                            Ini adalah invoice digital untuk produk/langganan platform {{ config('app.name') }}.
                            Jika Anda membutuhkan bantuan pembayaran, hubungi tim support.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
