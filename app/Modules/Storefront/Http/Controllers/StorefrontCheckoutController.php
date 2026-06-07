<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Storefront\Http\Requests\StorefrontCartCheckoutRequest;
use App\Modules\Storefront\Http\Requests\StorefrontCheckoutRequest;
use App\Modules\Storefront\Support\StorefrontCartService;
use App\Modules\Storefront\Services\StorefrontCheckoutService;
use App\Support\Commerce\CheckoutException;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\TenantContext;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class StorefrontCheckoutController extends Controller
{
    public function __construct(
        private readonly StorefrontCheckoutService $checkoutService,
        private readonly PublicStorefrontContext $publicStorefront,
        private readonly StorefrontCartService $cart,
    ) {
    }

    public function store(StorefrontCheckoutRequest $request, Product $product): RedirectResponse
    {
        abort_unless((bool) $product->is_active, 404);

        try {
            $result = $this->checkoutService->createOrder($product, $request->validated());
        } catch (CheckoutException $exception) {
            return back()
                ->withInput()
                ->with($exception->flash())
                ->withErrors($exception->errors());
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => $exception->getMessage()]);
        }

        $sale = $result['sale'];
        $checkout = $result['gateway_checkout'];

        if ($checkout && !empty($checkout['redirect_url'])) {
            return redirect()->away((string) $checkout['redirect_url']);
        }

        return redirect()->to(URL::signedRoute('storefront.public.orders.show', ['sale' => $sale]));
    }

    public function checkout(StorefrontCartCheckoutRequest $request): RedirectResponse
    {
        $items = $this->cart->items();

        try {
            $result = $this->checkoutService->createOrderFromItems($items, $request->validated());
        } catch (CheckoutException $exception) {
            return back()
                ->withInput()
                ->with($exception->flash())
                ->withErrors($exception->errors());
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => $exception->getMessage()]);
        }

        $sale = $result['sale'];
        $checkout = $result['gateway_checkout'];
        $this->cart->clear();

        if ($checkout && !empty($checkout['redirect_url'])) {
            return redirect()->away((string) $checkout['redirect_url']);
        }

        return redirect()->to(URL::signedRoute('storefront.public.orders.show', ['sale' => $sale]));
    }

    public function retryPayment(Request $request, int $sale): RedirectResponse
    {
        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));
        }

        abort_unless($this->publicStorefront->enabled(), 404);
        $company = $this->publicStorefront->apply();
        abort_unless($company, 404);

        $sale = Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', (int) $company->id)
            ->findOrFail($sale);

        try {
            $result = $this->checkoutService->retryPayment($sale);
        } catch (CheckoutException $exception) {
            return back()->withErrors($exception->errors());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['payment_method' => $exception->getMessage()]);
        }

        $checkout = $result['gateway_checkout'];

        return redirect()->away((string) $checkout['redirect_url']);
    }
}
