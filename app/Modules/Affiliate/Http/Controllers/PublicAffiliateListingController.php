<?php

namespace App\Modules\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliate\Models\AffiliateListing;
use App\Modules\Affiliate\Services\TenantAffiliateService;
use App\Modules\Storefront\Services\BrandPageService;
use App\Modules\Storefront\Support\StorefrontProductPresenter;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicAffiliateListingController extends Controller
{
    public function __construct(
        private readonly PublicStorefrontContext $publicStorefront,
        private readonly BrandPageService $brandPages,
        private readonly StorefrontProductPresenter $productPresenter,
        private readonly TenantAffiliateService $affiliates,
    ) {
    }

    public function __invoke(Request $request, AffiliateListing $listing): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);

        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }

        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);
        abort_unless((int) $listing->tenant_id === TenantContext::currentId(), 404);

        $listing->loadMissing('sourceProduct.media', 'sourceTenant', 'user');
        $sourceProduct = $listing->sourceProduct;
        abort_unless($sourceProduct && (bool) $sourceProduct->is_active, 404);

        $publicOffer = is_array(data_get($sourceProduct->meta, 'public_offer')) ? data_get($sourceProduct->meta, 'public_offer') : [];
        $landing = is_array($listing->landing_page_meta) ? $listing->landing_page_meta : [];

        return view('affiliate::public.show', [
            'listing' => $listing,
            'product' => $sourceProduct,
            'brand' => $this->brandPages->profile(),
            'sellerName' => $listing->sourceTenant?->name,
            'productImageUrl' => $this->productPresenter->imageUrl($sourceProduct),
            'headline' => trim((string) ($landing['headline'] ?? ($publicOffer['headline'] ?? $sourceProduct->name))),
            'subtitle' => trim((string) ($landing['subtitle'] ?? ($publicOffer['subtitle'] ?? $sourceProduct->description ?? ''))),
            'ctaLabel' => trim((string) ($landing['cta_label'] ?? ($publicOffer['cta_label'] ?? 'Beli sekarang'))),
            'purchaseUrl' => $this->affiliates->affiliateProductUrl($listing),
        ]);
    }
}
