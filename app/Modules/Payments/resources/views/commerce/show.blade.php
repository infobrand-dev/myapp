@extends('layouts.tenant')

@section('title', 'Commerce Payment Detail')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">{{ $payment->payment_number }}</h2>
                <p class="text-muted mb-0">Detail pembayaran untuk order commerce dan alokasinya.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('payments.commerce.index') }}" class="btn btn-outline-secondary">Kembali ke Payments</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Status</h3></div>
                <div class="card-body">
                    <div class="mb-2"><strong>Status:</strong> {{ strtoupper((string) $payment->status) }}</div>
                    <div class="mb-2"><strong>Metode:</strong> {{ optional($payment->method)->name ?: '-' }}</div>
                    <div class="mb-2"><strong>Amount:</strong> {{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
                    <div><strong>Paid At:</strong> {{ optional($payment->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Alokasi</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Tipe</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payment->allocations as $allocation)
                                <tr>
                                    <td>{{ $allocation->payable?->sale_number ?? $allocation->payable?->return_number ?? '-' }}</td>
                                    <td>{{ class_basename((string) $allocation->payable_type) }}</td>
                                    <td>{{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada alokasi pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

