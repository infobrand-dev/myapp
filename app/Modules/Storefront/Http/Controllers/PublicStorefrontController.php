<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Storefront\Services\BrandPageService;
use App\Modules\Storefront\Support\StorefrontCartService;
use App\Modules\Storefront\Support\StorefrontProductPresenter;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\CompanyContext;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PublicStorefrontController extends Controller
{
    public function __construct(
        private readonly PublicStorefrontContext $publicStorefront,
        private readonly StorefrontProductPresenter $productPresenter,
        private readonly StorefrontCartService $cart,
        private readonly BrandPageService $brandPages,
    ) {
    }

    public function index(Request $request): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);

        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));

        $catalogItems = Product::query()
            ->with('media')
            ->where('tenant_id', TenantContext::currentId())
            ->active()
            ->orderBy('name')
            ->get()
            ->filter(function (Product $product) use ($search, $type): bool {
                if (!$this->isPublicCatalogVisible($product)) {
                    return false;
                }

                if ($search !== '') {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) $product->name,
                        (string) $product->description,
                        (string) $product->sku,
                    ]));

                    if (!str_contains($haystack, mb_strtolower($search))) {
                        return false;
                    }
                }

                return $type === '' || $product->type === $type;
            })
            ->values();
        $products = $this->paginate($catalogItems, 18, $request);
        $featuredProducts = $catalogItems
            ->filter(fn (Product $product): bool => filter_var(data_get($product->meta, 'public_offer.featured', false), FILTER_VALIDATE_BOOLEAN))
            ->take(3);

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = $catalogItems->take(3);
        }

        $brand = $this->storefrontBrand();
        $sections = collect($brand['sections'])->keyBy('key');

        return view('storefront::public.index', [
            'products' => $products,
            'featuredProducts' => $featuredProducts,
            'productImageUrls' => $products->getCollection()->mapWithKeys(
                fn (Product $product) => [$product->id => $this->productPresenter->imageUrl($product)]
            ),
            'featuredImageUrls' => $featuredProducts->mapWithKeys(
                fn (Product $product) => [$product->id => $this->productPresenter->imageUrl($product)]
            ),
            'storefrontBrand' => $brand,
            'storefrontStats' => $this->storefrontStats($catalogItems),
            'cartCount' => $this->cart->count(),
            'filters' => ['q' => $search, 'type' => $type],
            'sections' => $sections,
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);
        abort_unless((bool) $product->is_active, 404);
        abort_unless($this->isPublicShowVisible($product), 404);

        $paymentGateways = app(PaymentGatewayManager::class);
        $activeProvider = $paymentGateways->activeProviderCode();
        $shippingProviders = app(ShippingProviderManager::class);
        $activeShippingProvider = $shippingProviders->activeProviderCode();

        return view('storefront::public.show', [
            'product' => $product->loadMissing('media'),
            'offer' => $this->publicOffer($product),
            'activeGatewayProvider' => $activeProvider,
            'activeGatewayLabel' => $paymentGateways->activeProviderLabel(),
            'activeGatewayMeta' => $paymentGateways->providerMetadata($activeProvider),
            'activeShippingProvider' => $activeShippingProvider,
            'activeShippingLabel' => $shippingProviders->activeProviderLabel(),
            'activeShippingMeta' => $shippingProviders->providerMetadata($activeShippingProvider),
            'shippingSelectionOptions' => session('storefront.shipping_options', []),
            'productImageUrl' => $this->productPresenter->imageUrl($product),
            'storefrontBrand' => $this->storefrontBrand(),
            'cartCount' => $this->cart->count(),
        ]);
    }

    public function offer(Request $request, Product $product): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);
        abort_unless((bool) $product->is_active, 404);
        abort_unless($this->isPublicShowVisible($product), 404);

        $offer = $this->publicOffer($product);
        $paymentGateways = app(PaymentGatewayManager::class);
        $activeProvider = $paymentGateways->activeProviderCode();
        $shippingProviders = app(ShippingProviderManager::class);
        $activeShippingProvider = $shippingProviders->activeProviderCode();

        return view('storefront::public.offer', [
            'product' => $product->loadMissing('media'),
            'offer' => $offer,
            'activeGatewayProvider' => $activeProvider,
            'activeGatewayLabel' => $paymentGateways->activeProviderLabel(),
            'activeGatewayMeta' => $paymentGateways->providerMetadata($activeProvider),
            'activeShippingProvider' => $activeShippingProvider,
            'activeShippingLabel' => $shippingProviders->activeProviderLabel(),
            'activeShippingMeta' => $shippingProviders->providerMetadata($activeShippingProvider),
            'shippingSelectionOptions' => session('storefront.shipping_options', []),
            'productImageUrl' => $this->productPresenter->imageUrl($product),
            'storefrontBrand' => $this->storefrontBrand(),
            'cartCount' => $this->cart->count(),
        ]);
    }

    public function cart(Request $request): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);

        $items = $this->cart->items();

        return view('storefront::public.cart', [
            'items' => $items,
            'cartCount' => $this->cart->count(),
            'cartSubtotal' => $this->cart->subtotal(),
            'storefrontBrand' => $this->storefrontBrand(),
            'productImageUrls' => $items->mapWithKeys(
                fn (array $item) => [$item['product']->id => $this->productPresenter->imageUrl($item['product'])]
            ),
        ]);
    }

    public function checkout(Request $request): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);

        $items = $this->cart->items();
        abort_if($items->isEmpty(), 404);

        $paymentGateways = app(PaymentGatewayManager::class);
        $activeProvider = $paymentGateways->activeProviderCode();
        $shippingProviders = app(ShippingProviderManager::class);
        $activeShippingProvider = $shippingProviders->activeProviderCode();

        return view('storefront::public.checkout', [
            'items' => $items,
            'cartCount' => $this->cart->count(),
            'cartSubtotal' => $this->cart->subtotal(),
            'storefrontBrand' => $this->storefrontBrand(),
            'activeGatewayProvider' => $activeProvider,
            'activeGatewayLabel' => $paymentGateways->activeProviderLabel(),
            'activeGatewayMeta' => $paymentGateways->providerMetadata($activeProvider),
            'activeShippingProvider' => $activeShippingProvider,
            'activeShippingLabel' => $shippingProviders->activeProviderLabel(),
            'activeShippingMeta' => $shippingProviders->providerMetadata($activeShippingProvider),
            'shippingSelectionOptions' => session('storefront.shipping_options', []),
            'productImageUrls' => $items->mapWithKeys(
                fn (array $item) => [$item['product']->id => $this->productPresenter->imageUrl($item['product'])]
            ),
        ]);
    }

    public function order(Request $request, int $sale): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        $this->ensureViewNamespace();
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($request->hasValidSignature(), 403);

        $company = $this->publicStorefront->apply();
        abort_unless($company, 404);

        $sale = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) $company->id)
            ->findOrFail($sale);

        $sale->loadMissing('items.product', 'paymentAllocations.payment.method');

        return view('storefront::public.order', [
            'sale' => $sale,
            'publicOrderUrl' => URL::signedRoute('storefront.public.orders.show', ['sale' => $sale]),
            'publicRetryPaymentUrl' => URL::signedRoute('storefront.public.orders.retry-payment', ['sale' => $sale]),
            'companyId' => CompanyContext::currentId(),
            'storefrontBrand' => $this->storefrontBrand(),
            'cartCount' => $this->cart->count(),
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function storefrontStats(Collection $catalogItems): array
    {
        $physicalCount = $catalogItems->filter(fn (Product $product): bool => $this->fulfillmentType($product) === 'physical')->count();
        $total = $catalogItems->count();
        $digitalCount = $total - $physicalCount;

        return [
            'total' => $total,
            'physical_label' => $physicalCount . ' fisik',
            'digital_label' => $digitalCount . ' digital/jasa',
        ];
    }

    /**
     * @return array{name:string,description:?string,logo_url:?string}
     */
    private function storefrontBrand(): array
    {
        return $this->brandPages->profile();
    }

    /**
     * @return array<string, mixed>
     */
    private function publicOffer(Product $product): array
    {
        $offer = is_array(data_get($product->meta, 'public_offer')) ? data_get($product->meta, 'public_offer') : [];

        return [
            'visibility' => (string) ($offer['visibility'] ?? 'catalog'),
            'headline' => trim((string) ($offer['headline'] ?? $product->name)),
            'subtitle' => ($subtitle = trim((string) ($offer['subtitle'] ?? ''))) !== '' ? $subtitle : null,
            'delivery_type' => $this->fulfillmentType($product),
            'delivery_instructions' => ($instructions = trim((string) ($offer['delivery_instructions'] ?? ''))) !== '' ? $instructions : null,
            'download_url' => ($downloadUrl = trim((string) ($offer['download_url'] ?? ''))) !== '' ? $downloadUrl : null,
            'external_url' => ($externalUrl = trim((string) ($offer['external_url'] ?? ''))) !== '' ? $externalUrl : null,
            'slot_note' => ($slotNote = trim((string) ($offer['slot_note'] ?? ''))) !== '' ? $slotNote : null,
            'cta_label' => trim((string) ($offer['cta_label'] ?? 'Beli sekarang')),
        ];
    }

    private function isPublicCatalogVisible(Product $product): bool
    {
        return in_array($this->publicOffer($product)['visibility'], ['catalog', 'featured'], true);
    }

    private function isPublicShowVisible(Product $product): bool
    {
        return in_array($this->publicOffer($product)['visibility'], ['catalog', 'featured', 'direct'], true);
    }

    private function fulfillmentType(Product $product): string
    {
        $configured = trim((string) data_get($product->meta, 'public_offer.delivery_type', ''));

        if ($configured !== '') {
            return $configured;
        }

        return $product->track_stock ? 'physical' : 'service';
    }

    private function ensureViewNamespace(): void
    {
        app('view')->addNamespace('storefront', dirname(__DIR__, 2) . '/resources/views');
    }

    /**
     * @template TValue
     *
     * @param  Collection<int, TValue>  $items
     * @return LengthAwarePaginator<TValue>
     */
    private function paginate(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
