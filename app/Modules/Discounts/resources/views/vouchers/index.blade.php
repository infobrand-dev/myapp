@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Voucher / Promo Codes</h2>
        <div class="text-muted small">Voucher opsional per discount. Tidak semua discount harus punya voucher.</div>
    </div>
    <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">Kembali ke Discounts</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Code</th><th>Discount</th><th>Window</th><th>Limit</th><th>Status</th><th>Usage</th></tr></thead>
            <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td class="fw-semibold">{{ $voucher->code }}</td>
                        <td>{{ $voucher->discount?->internal_name }}</td>
                        <td>{{ $voucher->starts_at?->format('d/m/Y H:i') ?? 'No start' }}<div class="text-muted small">{{ $voucher->ends_at?->format('d/m/Y H:i') ?? 'No end' }}</div></td>
                        <td>{{ $voucher->usage_limit ?? 'Unlimited' }}<div class="text-muted small">Per customer: {{ $voucher->usage_limit_per_customer ?? '-' }}</div></td>
                        <td><span class="badge bg-{{ $voucher->is_active ? 'success' : 'secondary' }}-lt text-{{ $voucher->is_active ? 'success' : 'secondary' }}">{{ $voucher->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $voucher->usages_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada voucher.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $vouchers->links() }}</div>
</div>
@endsection
