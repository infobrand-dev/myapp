@extends('layouts.tenant')

@section('title', 'Public Offers')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Storefront</div>
                <h2 class="page-title">Public Offers</h2>
                <p class="text-muted mb-0">Audit produk mana yang tampil di katalog, direct offer, atau tetap private.</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Products</a>
                <a href="{{ route('storefront.brand.edit') }}" class="btn btn-primary">Brand Page</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Offer</th>
                        <th>Visibility</th>
                        <th>Delivery</th>
                        <th>CTA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['headline'] }}</div>
                                <div class="text-muted small">{{ $row['product']->name }}</div>
                            </td>
                            <td><span class="badge bg-azure-lt text-azure">{{ strtoupper($row['visibility']) }}</span></td>
                            <td>{{ $row['delivery_type'] }}</td>
                            <td>{{ $row['cta_label'] }}</td>
                            <td class="text-end">
                                <a href="{{ route('products.edit', $row['product']) }}" class="btn btn-sm btn-outline-secondary">Edit Product</a>
                                <a href="{{ $row['public_url'] }}" class="btn btn-sm btn-primary">Buka</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada public offer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

