@extends('layouts.admin')

@section('title', 'Affiliate Payouts')

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
            <h1 class="page-title">Affiliate Payouts</h1>
            <div class="text-muted small mt-1">Kelola komisi affiliate yang menunggu approval atau pembayaran.</div>
            </div>
            <div class="col-auto">
        <a href="{{ route('platform.affiliates.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-share-2 me-1"></i>Affiliates
        </a>
            </div>
        </div>
    </div>

    @if(!$ready)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>Table affiliate platform belum lengkap. Jalankan migration affiliate terbaru terlebih dahulu.
        </div>
    @endif

    @if($ready)
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-bold">Pending</div>
                        <div class="fs-2 fw-bold">{{ number_format($summary['pending_count']) }}</div>
                        <div class="text-muted small">{{ $money->format($summary['pending_amount'], 'IDR') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-bold">Approved</div>
                        <div class="fs-2 fw-bold">{{ number_format($summary['approved_count']) }}</div>
                        <div class="text-muted small">{{ $money->format($summary['approved_amount'], 'IDR') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-bold">Paid</div>
                        <div class="fs-2 fw-bold">{{ number_format($summary['paid_count']) }}</div>
                        <div class="text-muted small">{{ $money->format($summary['paid_amount'], 'IDR') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" @selected($status === 'pending')>Pending</option>
                            <option value="approved" @selected($status === 'approved')>Approved</option>
                            <option value="paid" @selected($status === 'paid')>Paid</option>
                            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                            <option value="not_eligible" @selected($status === 'not_eligible')>Tidak Eligible</option>
                            <option value="all" @selected($status === 'all')>Semua</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Affiliate</th>
                            <th>Tenant</th>
                            <th>Order</th>
                            <th>Komisi</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $referral)
                            @php
                                $payoutInfo = $payoutStatusMap[$referral->payout_status] ?? ['label' => $referral->payout_status, 'class' => 'bg-secondary-lt text-secondary'];
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('platform.affiliates.show', $referral->affiliate) }}" class="fw-semibold text-reset">{{ $referral->affiliate?->name ?: '-' }}</a>
                                    <div class="text-muted small">{{ $referral->affiliate?->email ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $referral->tenant?->name ?: '-' }}</div>
                                    <div class="text-muted small">{{ $referral->tenant?->slug ?: $referral->buyer_email ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $referral->order?->order_number ?: '-' }}</div>
                                    <div class="text-muted small">{{ optional($referral->order?->plan)->display_name ?: optional($referral->order?->plan)->name ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $money->format((float) ($referral->commission_amount ?? 0), $referral->order_currency ?: 'IDR') }}</div>
                                    <div class="text-muted small">{{ $money->format((float) $referral->order_amount, $referral->order_currency ?: 'IDR') }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $payoutInfo['class'] }}">{{ $payoutInfo['label'] }}</span>
                                    @if($referral->payout_reference)
                                        <div class="text-muted small mt-1">{{ $referral->payout_reference }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-muted small">Sale: {{ optional($referral->converted_at)->format('d M Y H:i') ?: '-' }}</div>
                                    <div class="text-muted small">Approved: {{ optional($referral->approved_at)->format('d M Y H:i') ?: '-' }}</div>
                                    <div class="text-muted small">Paid: {{ optional($referral->paid_at)->format('d M Y H:i') ?: '-' }}</div>
                                </td>
                                <td style="min-width: 240px;">
                                    @if(in_array($referral->payout_status, ['pending', 'approved', 'rejected'], true))
                                        <form method="POST" action="{{ route('platform.affiliates.referrals.payout', [$referral->affiliate, $referral]) }}" class="d-grid gap-2">
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
                                    @else
                                        <div class="text-muted small">Tidak ada aksi payout.</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada data payout affiliate.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($payouts, 'links'))
                <div class="card-footer">{{ $payouts->links() }}</div>
            @endif
        </div>
    @endif
@endsection
