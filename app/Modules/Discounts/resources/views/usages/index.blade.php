@extends('layouts.admin')

@section('content')
@php
    $money = app(\App\Support\MoneyFormatter::class);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Discount Usage History</h2>
        <div class="text-muted small">Riwayat penggunaan diskon.</div>
    </div>
    <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Discount</th><th>Reference</th><th>Customer</th><th>Totals</th><th>Status</th><th>Applied At</th></tr></thead>
            <tbody>
                @forelse($usages as $usage)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $usage->discount?->internal_name }}</div>
                            <div class="text-muted small">{{ $usage->voucher?->code ?: 'No voucher' }}</div>
                        </td>
                        <td>{{ $usage->usage_reference_type ?: 'manual' }}<div class="text-muted small">{{ $usage->usage_reference_id ?: '-' }}</div></td>
                        <td>{{ $usage->customer_reference_type ?: '-' }}<div class="text-muted small">{{ $usage->customer_reference_id ?: '-' }}</div></td>
                        <td>Subtotal: {{ $money->format((float) $usage->subtotal_before, $usage->currency_code ?: 'IDR') }}<div class="text-muted small">Discount: {{ $money->format((float) $usage->discount_total, $usage->currency_code ?: 'IDR') }}</div></td>
                        <td><span class="badge bg-blue-lt text-blue">{{ $usage->usage_status }}</span></td>
                        <td>{{ $usage->applied_at?->format('d/m/Y H:i') ?: $usage->evaluated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada data penggunaan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $usages->links() }}</div>
</div>
@endsection
