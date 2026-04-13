@extends('layouts.admin')

@section('title', 'Platform Promos')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Owner</div>
                <h1 class="page-title">Promo Codes</h1>
                <p class="text-muted mb-0">Kelola promo onboarding platform dan pantau status campaign tanpa edit database.</p>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-layout-dashboard me-1"></i>Dashboard
                </a>
                <a href="{{ route('platform.orders.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-receipt-2 me-1"></i>Orders
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Buat Promo</h3>
                </div>
                <div class="card-body">
                    @if(!$promoReady)
                        <div class="alert alert-warning mb-0">Table promo platform belum tersedia. Jalankan migration terlebih dahulu.</div>
                    @else
                        <form method="POST" action="{{ route('platform.promos.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="MEETRA2ND" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" value="{{ old('label') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Discount Percent</label>
                                <input type="number" name="discount_percent" class="form-control" min="1" max="100" value="{{ old('discount_percent', 10) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Product Line</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="product_lines[]" value="accounting" id="promo-create-accounting" @checked(in_array('accounting', old('product_lines', []), true))>
                                    <label class="form-check-label" for="promo-create-accounting">Accounting</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="product_lines[]" value="omnichannel" id="promo-create-omnichannel" @checked(in_array('omnichannel', old('product_lines', []), true))>
                                    <label class="form-check-label" for="promo-create-omnichannel">Omnichannel</label>
                                </div>
                                <div class="form-hint">Kosongkan semua pilihan jika promo berlaku untuk semua product line.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Expires At</label>
                                <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Max Uses</label>
                                <input type="number" name="max_uses" class="form-control" min="1" value="{{ old('max_uses') }}">
                                <div class="form-hint">Kosongkan untuk unlimited.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                                    <span class="form-check-label">Promo aktif</span>
                                </label>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Simpan Promo</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Daftar Promo</h3>
                </div>
                <div class="card-body p-0">
                    @if(!$promoReady)
                        <div class="p-3 text-muted">Table promo platform belum tersedia.</div>
                    @elseif($promoCodes->isEmpty())
                        <div class="p-3 text-muted">Belum ada promo code platform.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Aturan</th>
                                        <th>Pemakaian</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($promoCodes as $promo)
                                        <tr>
                                            <td class="align-top">
                                                <div class="fw-semibold">{{ $promo->code }}</div>
                                                <div class="text-muted small">{{ $promo->label }}</div>
                                            </td>
                                            <td class="align-top">
                                                <form method="POST" action="{{ route('platform.promos.update', $promo) }}" class="row g-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="col-12">
                                                        <input type="text" name="label" class="form-control form-control-sm" value="{{ $promo->label }}" required>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input type="number" name="discount_percent" class="form-control form-control-sm" min="1" max="100" value="{{ $promo->discount_percent }}" required>
                                                    </div>
                                                    <div class="col-sm-8">
                                                        <input type="datetime-local" name="expires_at" class="form-control form-control-sm" value="{{ $promo->expires_at?->format('Y-m-d\\TH:i') }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="number" name="max_uses" class="form-control form-control-sm" min="1" value="{{ $promo->max_uses }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <label class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($promo->is_active)>
                                                            <span class="form-check-label">Aktif</span>
                                                        </label>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="d-flex gap-3 flex-wrap">
                                                            <label class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="product_lines[]" value="accounting" @checked(is_array($promo->applicable_product_lines) && in_array('accounting', $promo->applicable_product_lines, true))>
                                                                <span class="form-check-label">Accounting</span>
                                                            </label>
                                                            <label class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="product_lines[]" value="omnichannel" @checked(is_array($promo->applicable_product_lines) && in_array('omnichannel', $promo->applicable_product_lines, true))>
                                                                <span class="form-check-label">Omnichannel</span>
                                                            </label>
                                                        </div>
                                                        <div class="form-hint">Kosongkan semua pilihan untuk semua product line.</div>
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="align-top">
                                                <div class="fw-semibold">{{ number_format((int) $promo->used_count) }}</div>
                                                <div class="text-muted small">dari {{ $promo->max_uses ? number_format((int) $promo->max_uses) : 'Unlimited' }}</div>
                                                <div class="text-muted small mt-2">{{ is_array($promo->applicable_product_lines) && count($promo->applicable_product_lines) ? implode(', ', $promo->applicable_product_lines) : 'Semua product line' }}</div>
                                            </td>
                                            <td class="align-top">
                                                @if($promo->is_active)
                                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                                @endif
                                                <div class="text-muted small mt-2">{{ $promo->expires_at?->format('d/m/Y H:i') ?? 'No expiry' }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
