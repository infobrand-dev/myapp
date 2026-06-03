<?php

namespace App\Modules\Affiliate\Services;

use App\Models\User;
use App\Modules\Affiliate\Models\AffiliateListing;
use App\Support\Commerce\AffiliateAttribution;
use App\Modules\Products\Models\Product;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantAffiliateService
{
    public function __construct(
        private readonly AffiliateAttribution $attribution,
    ) {
    }

    /**
     * @return Collection<int, Product>
     */
    public function marketplaceProducts(): Collection
    {
        return Product::query()
            ->with('media')
            ->active()
            ->where('tenant_id', '!=', TenantContext::currentId())
            ->whereNotNull('meta')
            ->get()
            ->filter(fn (Product $product): bool => filter_var(data_get($product->meta, 'affiliate_offer.enabled', false), FILTER_VALIDATE_BOOLEAN))
            ->values();
    }

    public function claimProduct(Product $product, User $user, array $data = []): AffiliateListing
    {
        abort_unless((int) $user->tenant_id === TenantContext::currentId(), 403, 'User affiliator tidak berada pada tenant aktif.');
        abort_if((int) $product->tenant_id === TenantContext::currentId(), 422, 'Produk sendiri tidak perlu di-claim sebagai affiliate listing.');
        abort_unless(filter_var(data_get($product->meta, 'affiliate_offer.enabled', false), FILTER_VALIDATE_BOOLEAN), 404);

        $existing = AffiliateListing::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('source_product_id', (int) $product->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $publicOffer = is_array(data_get($product->meta, 'public_offer')) ? data_get($product->meta, 'public_offer') : [];
        $allowLandingCopy = filter_var(data_get($product->meta, 'affiliate_offer.allow_landing_copy', true), FILTER_VALIDATE_BOOLEAN);

        return AffiliateListing::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'user_id' => (int) $user->id,
            'source_tenant_id' => (int) $product->tenant_id,
            'source_product_id' => (int) $product->id,
            'share_code' => $this->nextShareCode($product->name),
            'status' => 'active',
            'commission_type' => (string) data_get($product->meta, 'affiliate_offer.commission_type', config('services.tenant_affiliate.default_commission_type', 'percentage')),
            'commission_rate' => (float) data_get($product->meta, 'affiliate_offer.commission_rate', config('services.tenant_affiliate.default_commission_rate', 10)),
            'landing_page_meta' => $allowLandingCopy ? [
                'headline' => trim((string) ($data['headline'] ?? ($publicOffer['headline'] ?? $product->name))),
                'subtitle' => $this->nullableString($data['subtitle'] ?? ($publicOffer['subtitle'] ?? null)),
                'cta_label' => trim((string) ($data['cta_label'] ?? ($publicOffer['cta_label'] ?? 'Beli sekarang'))),
            ] : null,
            'claimed_at' => now(),
        ]);
    }

    public function capture(Request $request, string $code): ?AffiliateListing
    {
        $listing = AffiliateListing::query()
            ->where('source_tenant_id', TenantContext::currentId())
            ->where('share_code', $this->normalizeCode($code))
            ->where('status', 'active')
            ->first();

        if (!$listing) {
            return null;
        }

        $this->attribution->store($request, $listing->share_code, (int) config('services.tenant_affiliate.cookie_days', 30));

        return $listing;
    }

    public function affiliateProductUrl(AffiliateListing $listing): ?string
    {
        $sellerSlug = (string) optional($listing->sourceTenant)->slug;

        if ($sellerSlug === '') {
            return null;
        }

        $visibility = (string) data_get($listing->sourceProduct?->meta, 'public_offer.visibility', 'catalog');
        $route = in_array($visibility, ['direct', 'featured'], true)
            ? 'storefront.public.offers.show'
            : 'storefront.public.products.show';

        return route('affiliate.public.capture', [
            'account' => $sellerSlug,
            'code' => $listing->share_code,
            'redirect' => $route,
            'product' => $listing->sourceProduct?->slug,
        ]);
    }

    public function publicListingUrl(AffiliateListing $listing): ?string
    {
        $affiliateSlug = (string) optional($listing->user?->tenant)->slug;

        if ($affiliateSlug === '') {
            return null;
        }

        return route('affiliate.public.products.show', [
            'account' => $affiliateSlug,
            'listing' => $listing->share_code,
        ]);
    }

    private function nextShareCode(string $name): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'AFF', 0, 6));

        do {
            $code = $base . random_int(100, 999);
        } while (AffiliateListing::query()
            ->where('share_code', $code)
            ->exists());

        return $code;
    }

    private function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
