@extends('layouts.tenant')

@section('title', 'Affiliates')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Affiliates</h2>
                <p class="text-muted mb-0">Dashboard tenant untuk membuka produk ke affiliator Meetra, memantau produk yang Anda claim, dan melihat conversion affiliate tanpa duplikasi product source.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('affiliate.marketplace') }}" class="btn btn-primary">Cari Produk Affiliate</a>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h3 class="card-title mb-0">Produk Saya yang Dibuka untuk Affiliate</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Commission</th>
                                <th>Landing Copy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sellerProducts as $product)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        <div class="text-muted small">{{ data_get($product->meta, 'public_offer.headline', $product->name) }}</div>
                                    </td>
                                    <td>{{ data_get($product->meta, 'affiliate_offer.commission_type') }} / {{ number_format((float) data_get($product->meta, 'affiliate_offer.commission_rate', 0), 2) }}</td>
                                    <td>{{ data_get($product->meta, 'affiliate_offer.allow_landing_copy', true) ? 'Allowed' : 'Locked' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada produk yang dibuka untuk affiliator. Aktifkan dari form product.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Affiliator yang Claim Produk Saya</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Affiliator</th>
                                <th>Product</th>
                                <th>Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sellerClaimedListings as $claimed)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $claimed->user?->name ?: 'Unknown User' }}</div>
                                        <div class="text-muted small">{{ $claimed->user?->tenant?->name ?: 'Unknown Tenant' }}</div>
                                    </td>
                                    <td>{{ $claimed->sourceProduct?->name ?: 'Unknown Product' }}</td>
                                    <td><code>{{ $claimed->share_code }}</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada affiliator yang claim produk Anda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Produk Affiliate di Workspace Saya</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Source Product</th>
                                <th>Code</th>
                                <th>Commission</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myListings as $listing)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $listing->sourceProduct?->name ?: 'Unknown Product' }}</div>
                                        <div class="text-muted small">{{ data_get($listing->landing_page_meta, 'headline', data_get($listing->sourceProduct?->meta, 'public_offer.headline')) }}</div>
                                    </td>
                                    <td><code>{{ $listing->share_code }}</code></td>
                                    <td>{{ $listing->commission_type }} / {{ number_format((float) $listing->commission_rate, 2) }}</td>
                                    <td><code>{{ $affiliateUrls[$listing->id] ?? route('affiliate.public.capture', ['account' => $listing->sourceTenant?->slug, 'code' => $listing->share_code]) }}</code></td>
                                </tr>
                                <tr>
                                    <td colspan="4">
                                        <form method="POST" action="{{ route('affiliate.listings.update', $listing) }}" class="row g-2">
                                            @csrf
                                            <div class="col-md-4"><input type="text" name="headline" class="form-control form-control-sm" value="{{ data_get($listing->landing_page_meta, 'headline', '') }}" placeholder="Headline copy"></div>
                                            <div class="col-md-5"><input type="text" name="subtitle" class="form-control form-control-sm" value="{{ data_get($listing->landing_page_meta, 'subtitle', '') }}" placeholder="Subtitle copy"></div>
                                            <div class="col-md-2"><input type="text" name="cta_label" class="form-control form-control-sm" value="{{ data_get($listing->landing_page_meta, 'cta_label', '') }}" placeholder="CTA"></div>
                                            <div class="col-md-1 d-grid"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada produk affiliate yang Anda claim.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header"><h3 class="card-title mb-0">Conversions</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Affiliate</th>
                                <th>Gross</th>
                                <th>Commission</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($referrals as $referral)
                                <tr>
                                    <td>{{ $referral->sale?->sale_number ?: '#' . $referral->sale_id }}</td>
                                    <td>{{ $referral->listing?->user?->name ?: $referral->referral_code }}</td>
                                    <td>Rp{{ number_format((float) $referral->order_gross, 0, ',', '.') }}</td>
                                    <td>Rp{{ number_format((float) $referral->commission_amount, 0, ',', '.') }}</td>
                                    <td>{{ $referral->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada conversion affiliate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

