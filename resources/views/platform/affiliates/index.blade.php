@extends('layouts.admin')

@section('title', 'Platform Affiliates')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Affiliates</h1>
            <div class="text-muted small mt-1">Kelola akun affiliate platform, link referral, dan hasil penjualan yang masuk.</div>
            </div>
            <div class="col-auto">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Dashboard
        </a>
            </div>
        </div>
    </div>

    @if(!$ready)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>Table affiliate platform belum tersedia. Jalankan migration terlebih dahulu.
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Affiliates</div>
                    <div class="fs-2 fw-bold">{{ $stats['total_affiliates'] }}</div>
                    <div class="text-muted small">{{ $stats['active_affiliates'] }} aktif</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Clicks</div>
                    <div class="fs-2 fw-bold">{{ number_format($stats['total_clicks']) }}</div>
                    <div class="text-muted small">Klik link affiliate</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Converted</div>
                    <div class="fs-2 fw-bold">{{ number_format($stats['total_converted']) }}</div>
                    <div class="text-muted small">Referral menjadi sale</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small fw-bold">Pending Payout</div>
                    <div class="fs-2 fw-bold">{{ number_format($stats['total_pending_payouts']) }}</div>
                    <div class="text-muted small">Komisi menunggu approval</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Buat Affiliate</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.affiliates.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Komisi</label>
                                <select class="form-select" name="commission_type" required>
                                    <option value="percentage" @selected(old('commission_type', 'percentage') === 'percentage')>Persentase</option>
                                    <option value="flat" @selected(old('commission_type') === 'flat')>Nominal Tetap</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nilai Komisi</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="commission_rate" value="{{ old('commission_rate', 10) }}" required>
                            </div>
                        </div>
                        <div class="mt-3 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="4">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" @disabled(!$ready)>Buat Affiliate</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Affiliate</th>
                                <th>Link</th>
                                <th>Komisi</th>
                                <th>Referral</th>
                                <th>Sales</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($affiliates as $affiliate)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $affiliate->name }}</div>
                                        <div class="text-muted small">{{ $affiliate->email }}</div>
                                        <div class="text-muted small">{{ $affiliate->status }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">/aff/{{ $affiliate->slug }}</div>
                                        <div class="text-muted small">{{ number_format((int) $affiliate->click_count) }} klik · {{ $affiliate->phone ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if($affiliate->commission_type === 'flat')
                                            {{ number_format((float) $affiliate->commission_rate, 0, ',', '.') }}
                                        @else
                                            {{ rtrim(rtrim(number_format((float) $affiliate->commission_rate, 2, '.', ''), '0'), '.') }}%
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ (int) $affiliate->referrals_count }}</div>
                                        <div class="text-muted small">{{ (int) $affiliate->converted_referrals_count }} converted · {{ (int) $affiliate->pending_payouts_count }} pending payout</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ app(\App\Support\MoneyFormatter::class)->format((float) ($affiliate->converted_sales_amount ?? 0), 'IDR') }}</div>
                                        <div class="text-muted small">Komisi {{ app(\App\Support\MoneyFormatter::class)->format((float) ($affiliate->converted_commission_amount ?? 0), 'IDR') }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('platform.affiliates.show', $affiliate) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Belum ada affiliate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
