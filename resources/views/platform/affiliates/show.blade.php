@extends('layouts.admin')

@section('title', 'Affiliate Detail')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">{{ $affiliate->name }}</h1>
            <div class="text-muted small mt-1">{{ $affiliate->email }} · {{ $affiliate->status }}</div>
        </div>
        <a href="{{ route('platform.affiliates.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Affiliates
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Link Referral</h3></div>
                <div class="card-body">
                    <div class="mb-2 text-muted small">Bagikan link ini ke calon customer.</div>
                    <input type="text" class="form-control" value="{{ $referralLink }}" readonly>
                    <div class="mt-3 small text-muted">Kode referral: <strong>{{ $affiliate->referral_code }}</strong></div>
                    <div class="small text-muted">
                        Komisi:
                        @if($affiliate->commission_type === 'flat')
                            {{ number_format((float) $affiliate->commission_rate, 0, ',', '.') }}
                        @else
                            {{ rtrim(rtrim(number_format((float) $affiliate->commission_rate, 2, '.', ''), '0'), '.') }}%
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Referral & Penjualan</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Tenant</th>
                                <th>Order</th>
                                <th>Nilai</th>
                                <th>Komisi</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($affiliate->referrals as $referral)
                                <tr>
                                    <td>{{ $referral->status }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($referral->tenant)->name ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional($referral->tenant)->slug ?: $referral->buyer_email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($referral->order)->order_number ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional(optional($referral->order)->plan)->name ?: '-' }}</div>
                                    </td>
                                    <td>{{ app(\App\Support\MoneyFormatter::class)->format((float) $referral->order_amount, $referral->order_currency ?: 'IDR') }}</td>
                                    <td>{{ app(\App\Support\MoneyFormatter::class)->format((float) ($referral->commission_amount ?? 0), $referral->order_currency ?: 'IDR') }}</td>
                                    <td>
                                        <div class="text-muted small">Register: {{ optional($referral->registered_at)->format('d M Y H:i') ?: '-' }}</div>
                                        <div class="text-muted small">Sale: {{ optional($referral->converted_at)->format('d M Y H:i') ?: '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Belum ada referral untuk affiliate ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
