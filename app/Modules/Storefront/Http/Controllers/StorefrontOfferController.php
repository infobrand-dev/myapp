<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Storefront\Support\StorefrontProductPresenter;
use App\Support\TenantContext;
use Illuminate\View\View;

class StorefrontOfferController extends Controller
{
    public function __construct(
        private readonly StorefrontProductPresenter $productPresenter,
    ) {
    }

    public function __invoke(): View
    {
        $products = Product::query()
            ->with('media')
            ->where('tenant_id', TenantContext::currentId())
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): array {
                $offer = is_array(data_get($product->meta, 'public_offer')) ? data_get($product->meta, 'public_offer') : [];

                return [
                    'product' => $product,
                    'visibility' => (string) ($offer['visibility'] ?? 'catalog'),
                    'headline' => trim((string) ($offer['headline'] ?? $product->name)),
                    'delivery_type' => trim((string) ($offer['delivery_type'] ?? ($product->track_stock ? 'physical' : 'service'))),
                    'cta_label' => trim((string) ($offer['cta_label'] ?? 'Beli sekarang')),
                    'public_url' => (string) route('storefront.public.offers.show', $product),
                    'image_url' => $this->productPresenter->imageUrl($product),
                ];
            });

        return view('storefront::offers.index', [
            'products' => $products,
        ]);
    }
}
