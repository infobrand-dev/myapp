@extends('layouts.admin')

@section('title', 'Commerce Payments')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Payment Status</h2>
                <p class="text-muted mb-0">Lihat pembayaran yang masuk dari order publik dan status postingnya.</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Payments</div><div class="h2 mb-0">{{ number_format((int) ($summary['total_count'] ?? 0)) }}</div></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="text-muted small">Posted</div><div class="h2 mb-0">{{ number_format((int) ($summary['posted_count'] ?? 0)) }}</div></div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body"><div class="text-muted small">Posted Amount</div><div class="h2 mb-0">{{ number_format((float) ($summary['posted_amount'] ?? 0), 0, ',', '.') }}</div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <a href="{{ route('payments.commerce.show', $payment) }}" class="fw-semibold text-decoration-none">
                                    {{ $payment->payment_number }}
                                </a>
                                <div class="text-muted small">{{ $payment->reference_number ?: $payment->external_reference ?: '-' }}</div>
                            </td>
                            <td><span class="badge bg-azure-lt text-azure">{{ strtoupper((string) $payment->status) }}</span></td>
                            <td>{{ optional($payment->method)->name ?: '-' }}</td>
                            <td>{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                            <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada pembayaran commerce.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($payments, 'links'))
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
