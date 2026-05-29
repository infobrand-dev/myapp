<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Storefront\Support\StorefrontCartService;
use App\Modules\Storefront\Support\StorefrontProductPresenter;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\CompanyContext;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PublicStorefrontController extends Controller
{
    public function __construct(
        private readonly PublicStorefrontContext $publicStorefront,
        private readonly StorefrontProductPresenter $productPresenter,
        private readonly StorefrontCartService $cart,
    ) {
    }

    public function index(Request $request): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);

        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));

        $catalogQuery = Product::query()
            ->with('media')
            ->where('tenant_id', TenantContext::currentId())
            ->active()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->when($type !== '' && in_array($type, ['simple', 'digital', 'service', 'custom'], true), fn ($query) => $query->where('type', $type))
            ->orderBy('name');
        $products = (clone $catalogQuery)->paginate(18);
        $featuredProducts = (clone $catalogQuery)->take(3)->get();

        return view('storefront::public.index', [
            'products' => $products,
            'featuredProducts' => $featuredProducts,
            'productImageUrls' => $products->getCollection()->mapWithKeys(
                fn (Product $product) => [$product->id => $this->productPresenter->imageUrl($product)]
            ),
            'featuredImageUrls' => $featuredProducts->mapWithKeys(
                fn (Product $product) => [$product->id => $this->productPresenter->imageUrl($product)]
            ),
            'storefrontBrand' => $this->storefrontBrand(),
            'storefrontStats' => $this->storefrontStats($catalogQuery),
            'cartCount' => $this->cart->count(),
            'filters' => ['q' => $search, 'type' => $type],
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_if($request->attributes->get('platform_admin_host'), 404);
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }
        abort_unless($this->publicStorefront->enabled(), 404);
        abort_unless($this->publicStorefront->apply(), 404);
        abort_unless((bool) $product->is_active, 404);

        $paymentGateways = app(PaymentGatewayManager::class);
        $activeProvider = $paymentGateways->activeProviderCode();
        $shippingProviders = app(ShippingProviderManager::class);
        $activeShippingProvider = $shippingProviders->activeProviderCode();

        return view('storefront::public.show', [
            'product' => $product->loadMissing('media'),
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
    private function storefrontStats($catalogQuery): array
    {
        $physicalCount = (clone $catalogQuery)->trackingStock()->count();
        $total = (clone $catalogQuery)->count();
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
        $tenant = TenantContext::currentTenant();
        $meta = is_array($tenant?->meta) ? $tenant->meta : [];

        return [
            'name' => trim((string) ($meta['public_brand_name'] ?? $tenant?->name ?? config('app.name'))),
            'description' => ($description = trim((string) ($meta['public_brand_description'] ?? ''))) !== '' ? $description : null,
            'logo_url' => $this->publicAssetUrl((string) ($meta['public_brand_logo_path'] ?? '')),
        ];
    }

    private function publicAssetUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $publicStoragePath = public_path('storage/' . $path);
        if (File::exists($publicStoragePath)) {
            return asset('storage/' . $path);
        }

        $publicDirectPath = public_path($path);
        if (File::exists($publicDirectPath)) {
            return asset($path);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }
}
