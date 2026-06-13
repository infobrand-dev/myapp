@extends('layouts.tenant')

@section('title', 'Affiliate Marketplace')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Affiliate Marketplace</div>
                <h2 class="page-title">Cari Produk Affiliate</h2>
                <p class="text-muted mb-0">Produk di bawah ini dibuka oleh tenant lain untuk affiliator Meetra. Anda bisa claim ke workspace sendiri tanpa menduplikasi master product source.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('affiliate.index') }}" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @forelse($products as $product)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <div>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="text-muted small">{{ data_get($product->meta, 'public_offer.headline', $product->name) }}</div>
                            </div>
                            <span class="badge bg-azure-lt text-azure">{{ strtoupper((string) data_get($product->meta, 'affiliate_offer.commission_type', 'percentage')) }} {{ number_format((float) data_get($product->meta, 'affiliate_offer.commission_rate', 0), 2) }}</span>
                        </div>

                        <div class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit((string) data_get($product->meta, 'public_offer.subtitle', $product->description), 180) }}</div>

                        @if(in_array($product->id, $claimedProductIds, true))
                            <div class="alert alert-secondary mb-0">Produk ini sudah ada di affiliate workspace Anda.</div>
                        @else
                            <form method="POST" action="{{ route('affiliate.marketplace.claim', $product) }}" class="row g-2">
                                @csrf
                                <div class="col-md-5">
                                    <input type="text" name="headline" class="form-control form-control-sm" placeholder="Optional headline copy">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="subtitle" class="form-control form-control-sm" placeholder="Optional subtitle copy">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-sm btn-primary">Claim</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary mb-0">Belum ada produk yang dibuka untuk affiliate marketplace.</div>
            </div>
        @endforelse
    </div>
@endsection

