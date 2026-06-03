<?php

namespace App\Modules\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliate\Services\TenantAffiliateService;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AffiliateReferralCaptureController extends Controller
{
    public function __construct(
        private readonly TenantAffiliateService $affiliates,
        private readonly PublicStorefrontContext $publicStorefront,
    ) {
    }

    public function __invoke(Request $request, string $code): RedirectResponse
    {
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }

        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);

        $listing = $this->affiliates->capture($request, $code);
        abort_unless($listing, 404);

        $listing->loadMissing('sourceProduct');

        return redirect()->to($this->redirectTarget($request, $listing))
            ->with('status', 'Referral affiliate berhasil dicatat.');
    }

    private function redirectTarget(Request $request, $listing): string
    {
        $redirect = trim((string) $request->query('redirect', ''));
        $productSlug = trim((string) $request->query('product', optional($listing->sourceProduct)->slug));

        if ($productSlug !== '' && $redirect === 'storefront.public.offers.show') {
            return route('storefront.public.offers.show', ['product' => $productSlug]);
        }

        if ($productSlug !== '' && $redirect === 'storefront.public.products.show') {
            return route('storefront.public.products.show', ['product' => $productSlug]);
        }

        return route('storefront.public.index');
    }
}
