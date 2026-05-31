<?php

namespace App\Modules\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliate\Models\AffiliateListing;
use App\Modules\Affiliate\Models\AffiliateReferral;
use App\Modules\Products\Models\Product;
use App\Modules\Affiliate\Services\TenantAffiliateService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function __construct(
        private readonly TenantAffiliateService $affiliates,
    ) {
    }

    public function index(): View
    {
        $sellerProducts = Product::query()
            ->where('tenant_id', TenantContext::currentId())
            ->active()
            ->get()
            ->filter(fn (Product $product): bool => filter_var(data_get($product->meta, 'affiliate_offer.enabled', false), FILTER_VALIDATE_BOOLEAN))
            ->values();

        $myListings = AffiliateListing::query()
            ->with('sourceProduct', 'sourceTenant')
            ->where('tenant_id', TenantContext::currentId())
            ->latest('id')
            ->get();

        return view('affiliate::index', [
            'sellerProducts' => $sellerProducts,
            'myListings' => $myListings,
            'referrals' => AffiliateReferral::query()
                ->with('listing.user', 'listing.sourceProduct', 'sale')
                ->where(function ($query): void {
                    $query->where('tenant_id', TenantContext::currentId())
                        ->orWhere('affiliate_tenant_id', TenantContext::currentId());
                })
                ->latest('id')
                ->take(20)
                ->get(),
        ]);
    }

    public function marketplace(): View
    {
        return view('affiliate::marketplace', [
            'products' => $this->affiliates->marketplaceProducts(),
            'claimedProductIds' => AffiliateListing::query()
                ->where('tenant_id', TenantContext::currentId())
                ->pluck('source_product_id')
                ->all(),
        ]);
    }

    public function claim(int $sourceProduct): RedirectResponse
    {
        $product = Product::query()->findOrFail($sourceProduct);

        $listing = $this->affiliates->claimProduct($product, request()->user(), request()->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:80'],
        ]));

        return redirect()->route('affiliate.index')
            ->with('status', 'Produk affiliate berhasil ditambahkan ke workspace Anda dengan kode ' . $listing->share_code . '.');
    }

    public function updateListing(AffiliateListing $listing): RedirectResponse
    {
        $validated = request()->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:80'],
        ]);

        $landing = is_array($listing->landing_page_meta) ? $listing->landing_page_meta : [];
        $landing['headline'] = trim((string) ($validated['headline'] ?? $landing['headline'] ?? ''));
        $landing['subtitle'] = trim((string) ($validated['subtitle'] ?? $landing['subtitle'] ?? ''));
        $landing['cta_label'] = trim((string) ($validated['cta_label'] ?? $landing['cta_label'] ?? ''));

        $listing->update(['landing_page_meta' => $landing]);

        return redirect()->route('affiliate.index')->with('status', 'Landing copy affiliate berhasil diperbarui.');
    }
}
