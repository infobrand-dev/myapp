@extends('layouts.admin')

@section('title', 'Affiliate Detail')

@section('content')
    @php
        $money = app(\App\Support\MoneyFormatter::class);
        $payoutStatusMap = [
            'unqualified' => ['label' => 'Belum Qualified', 'class' => 'bg-secondary-lt text-secondary'],
            'not_eligible' => ['label' => 'Tidak Eligible', 'class' => 'bg-secondary-lt text-secondary'],
            'pending' => ['label' => 'Pending', 'class' => 'bg-warning-lt text-warning'],
            'approved' => ['label' => 'Approved', 'class' => 'bg-azure-lt text-azure'],
            'paid' => ['label' => 'Paid', 'class' => 'bg-success-lt text-success'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger-lt text-danger'],
        ];
    @endphp

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">{{ $affiliate->name }}</h1>
            <div class="text-muted small mt-1">{{ $affiliate->email }} · {{ $affiliate->status }}</div>
            </div>
            <div class="col-auto">
        <a href="{{ route('platform.affiliates.index', $affiliate) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Affiliates
        </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Link Referral</h3></div>
                <div class="card-body">
                    <div class="mb-2 text-muted small">Gunakan link branded ini untuk dibagikan ke calon customer.</div>
                    <input type="text" class="form-control" value="{{ $referralLink }}" readonly>
                    <div class="mt-3 small text-muted">Slug publik: <strong>{{ $affiliate->slug }}</strong></div>
                    <div class="small text-muted">Kode internal: <strong>{{ $affiliate->referral_code }}</strong></div>
                    <div class="small text-muted">
                        Komisi:
                        @if($affiliate->commission_type === 'flat')
                            {{ number_format((float) $affiliate->commission_rate, 0, ',', '.') }}
                        @else
                            {{ rtrim(rtrim(number_format((float) $affiliate->commission_rate, 2, '.', ''), '0'), '.') }}%
                        @endif
                    </div>
                    <hr>
                    <div class="small text-muted">Klik: <strong>{{ number_format($stats['clicks']) }}</strong></div>
                    <div class="small text-muted">Referral terdaftar: <strong>{{ number_format($stats['registered']) }}</strong></div>
                    <div class="small text-muted">Converted sale: <strong>{{ number_format($stats['converted']) }}</strong></div>
                    <div class="small text-muted">Pending payout: <strong>{{ number_format($stats['pending_payouts']) }}</strong></div>
                    <div class="small text-muted">Klik terakhir: <strong>{{ optional($affiliate->last_clicked_at)->format('d M Y H:i') ?: '-' }}</strong></div>
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
                                <th>Payout</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($affiliate->referrals as $referral)
                                @php
                                    $payoutInfo = $payoutStatusMap[$referral->payout_status] ?? ['label' => $referral->payout_status, 'class' => 'bg-secondary-lt text-secondary'];
                                @endphp
                                <tr>
                                    <td>{{ $referral->status }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($referral->tenant)->name ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional($referral->tenant)->slug ?: $referral->buyer_email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($referral->order)->order_number ?: '-' }}</div>
                                        <div class="text-muted small">{{ optional(optional($referral->order)->plan)->display_name ?: optional(optional($referral->order)->plan)->name ?: '-' }}</div>
                                    </td>
                                    <td>{{ $money->format((float) $referral->order_amount, $referral->order_currency ?: 'IDR') }}</td>
                                    <td>{{ $money->format((float) ($referral->commission_amount ?? 0), $referral->order_currency ?: 'IDR') }}</td>
                                    <td>
                                        <span class="badge {{ $payoutInfo['class'] }}">{{ $payoutInfo['label'] }}</span>
                                        @if($referral->payout_reference)
                                            <div class="text-muted small mt-1">{{ $referral->payout_reference }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-muted small">Register: {{ optional($referral->registered_at)->format('d M Y H:i') ?: '-' }}</div>
                                        <div class="text-muted small">Sale: {{ optional($referral->converted_at)->format('d M Y H:i') ?: '-' }}</div>
                                        <div class="text-muted small">Paid: {{ optional($referral->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                                    </td>
                                    <td style="min-width: 230px;">
                                        @if(in_array($referral->payout_status, ['pending', 'approved', 'rejected'], true))
                                            <form method="POST" action="{{ route('platform.affiliates.referrals.payout', [$affiliate, $referral]) }}" class="d-grid gap-2">
                                                @csrf
                                                <select name="payout_status" class="form-select form-select-sm">
                                                    <option value="pending" @selected($referral->payout_status === 'pending')>Pending</option>
                                                    <option value="approved" @selected($referral->payout_status === 'approved')>Approved</option>
                                                    <option value="paid" @selected($referral->payout_status === 'paid')>Paid</option>
                                                    <option value="rejected" @selected($referral->payout_status === 'rejected')>Rejected</option>
                                                </select>
                                                <input type="text" name="payout_reference" class="form-control form-control-sm" value="{{ $referral->payout_reference }}" placeholder="Referensi payout">
                                                <input type="text" name="payout_notes" class="form-control form-control-sm" value="{{ $referral->payout_notes }}" placeholder="Catatan payout">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                            </form>
                                        @elseif($referral->payout_status === 'paid')
                                            <div class="text-muted small">Payout sudah selesai.</div>
                                        @else
                                            <div class="text-muted small">Tidak ada aksi payout.</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Belum ada referral untuk affiliate ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
