<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\Payment;
use App\Modules\Products\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\CompanyContext;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
        private readonly ShippingProviderManager $shippingProviders,
        private readonly PublicStorefrontContext $publicStorefront,
    ) {
    }

    public function __invoke(): View
    {
        $tenantId = TenantContext::currentId();
        $companyId = (int) CompanyContext::currentId();

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('source', Sale::SOURCE_ONLINE);
        BranchContext::applyScope($salesQuery);

        $paymentsQuery = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('source', Payment::SOURCE_ONLINE);
        BranchContext::applyScope($paymentsQuery);

        $recentOrders = (clone $salesQuery)
            ->latest('transaction_date')
            ->latest('id')
            ->take(10)
            ->get();
        $commerceOrders = (clone $salesQuery)->latest('transaction_date')->latest('id')->get();
        $postedPayments = (clone $paymentsQuery)->where('status', Payment::STATUS_POSTED);
        $pendingPaymentCount = $commerceOrders
            ->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_PENDING_PAYMENT)
            ->count();
        $paymentIssueOrders = $commerceOrders
            ->filter(fn (Sale $sale) => in_array($this->commerceOrders->status($sale), [
                CommerceOrderLifecycleService::STATUS_PENDING_PAYMENT,
                CommerceOrderLifecycleService::STATUS_EXPIRED,
            ], true))
            ->take(5)
            ->values();
        $fulfillmentBacklog = $commerceOrders
            ->filter(fn (Sale $sale) => in_array($this->commerceOrders->status($sale), [
                CommerceOrderLifecycleService::STATUS_PAID,
                CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT,
            ], true))
            ->take(5)
            ->values();
        $shippingBacklog = $commerceOrders
            ->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery'
                && in_array($this->commerceOrders->shippingStatus($sale), [
                    CommerceOrderLifecycleService::SHIPPING_PENDING,
                    CommerceOrderLifecycleService::SHIPPING_READY,
                ], true))
            ->take(5)
            ->values();
        $quoteIssueCount = $commerceOrders
            ->filter(fn (Sale $sale) => data_get($sale->meta, 'commerce.fulfillment_method') === 'delivery'
                && empty(data_get($sale->meta, 'commerce.shipping.selected_rate')))
            ->count();
        $shippedToday = $commerceOrders
            ->filter(fn (Sale $sale) => $this->commerceOrders->shippingStatus($sale) === CommerceOrderLifecycleService::SHIPPING_SHIPPED
                && str_starts_with((string) data_get($sale->meta, 'commerce.shipping.shipped_at', ''), now()->toDateString()))
            ->count();
        $activeCompany = CompanyContext::currentCompany();
        $companyMeta = is_array($activeCompany?->meta) ? $activeCompany->meta : [];
        $shippingDriver = $this->shippingProviders->driver();
        $publicCompany = $this->publicStorefront->company();
        $shippableProducts = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->count();
        $shippableProductsWithoutWeight = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->get(['id', 'meta'])
            ->filter(function (Product $product): bool {
                $meta = is_array($product->meta) ? $product->meta : [];
                $weight = data_get($meta, 'shipping.weight_grams');

                return empty($weight) || (int) $weight <= 0;
            })
            ->count();

        return view('storefront::index', [
            'metrics' => [
                'orders' => $commerceOrders->count(),
                'paid_orders' => $commerceOrders->where('payment_status', Sale::PAYMENT_PAID)->count(),
                'pending_orders' => $pendingPaymentCount,
                'payments_received' => (float) ($postedPayments->sum('amount') ?: 0),
                'gross_revenue' => (float) ($commerceOrders->where('payment_status', Sale::PAYMENT_PAID)->sum('grand_total') ?: 0),
                'expired_orders' => $commerceOrders->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_EXPIRED)->count(),
                'ready_fulfillment' => $commerceOrders->filter(fn (Sale $sale) => $this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT)->count(),
                'shipping_queue' => $shippingBacklog->count(),
                'quote_issues' => $quoteIssueCount,
                'shipped_today' => $shippedToday,
            ],
            'recentOrders' => $recentOrders,
            'paymentIssueOrders' => $paymentIssueOrders,
            'fulfillmentBacklog' => $fulfillmentBacklog,
            'shippingBacklog' => $shippingBacklog,
            'health' => [
                'public_storefront_enabled' => $this->publicStorefront->enabled(),
                'public_company_name' => $publicCompany?->name,
                'origin_ready' => !empty($companyMeta['shipping_origin_postal_code']) || !empty($companyMeta['shipping_origin_area_id']),
                'provider_ready' => $shippingDriver ? $shippingDriver->isConfigured() : false,
                'provider_label' => $this->shippingProviders->activeProviderLabel(),
                'shippable_products' => $shippableProducts,
                'missing_weight_products' => $shippableProductsWithoutWeight,
            ],
        ]);
    }
}
