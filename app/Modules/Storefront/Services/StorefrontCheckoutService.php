<?php

namespace App\Modules\Storefront\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\AffiliateAttribution;
use App\Support\Commerce\CheckoutException;
use Illuminate\Support\Collection;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Support\Commerce\CommerceSalePricingService;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\CompanyContext;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\CheckoutShippingQuoteService;
use App\Support\Shipping\ShippingProviderManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StorefrontCheckoutService
{
    public function __construct(
        private readonly CreateDraftSaleAction $createDraftSale,
        private readonly PaymentGatewayManager $paymentGateways,
        private readonly ShippingProviderManager $shippingProviders,
        private readonly CheckoutShippingQuoteService $shippingQuoteService,
        private readonly CommerceSalePricingService $commercePricing,
        private readonly CommerceOrderLifecycleService $commerceOrders,
        private readonly PublicStorefrontContext $publicStorefront,
        private readonly AffiliateAttribution $affiliateAttribution,
    ) {
    }

    /**
     * @return array{sale: Sale, gateway_checkout: ?array{provider:string,redirect_url:string,reference:string,token:?string}}
     */
    public function createOrder(Product $product, array $payload): array
    {
        return $this->createOrderFromItems(collect([[
            'product' => $product,
            'qty' => max(1, (int) ($payload['qty'] ?? 1)),
        ]]), $payload);
    }

    /**
     * @param  Collection<int, array{product:Product,qty:int}>  $items
     * @return array{sale: Sale, gateway_checkout: ?array{provider:string,redirect_url:string,reference:string,token:?string}}
     */
    public function createOrderFromItems(Collection $items, array $payload): array
    {
        $items = $items
            ->filter(fn (array $item): bool => isset($item['product']) && $item['product'] instanceof Product && (int) ($item['qty'] ?? 0) > 0)
            ->values();
        if ($items->isEmpty()) {
            throw new CheckoutException(
                'Cart Anda masih kosong.',
                ['cart' => 'Tambahkan produk ke cart terlebih dahulu.']
            );
        }

        $publicCompany = $this->publicStorefront->apply();
        $paymentMethod = (string) ($payload['payment_method'] ?? 'manual');
        $tenantId = (int) $items->first()['product']->tenant_id;
        $fulfillmentType = $this->resolveFulfillmentType($items);
        $channel = (string) ($payload['checkout_channel'] ?? ($items->count() === 1 ? 'direct_offer' : 'public_brand'));
        $affiliateCode = $this->affiliateAttribution->currentCode();
        $fulfillmentMethod = $fulfillmentType === 'physical'
            ? (string) ($payload['fulfillment_method'] ?? 'pickup')
            : 'digital';
        $itemFingerprint = $items
            ->sortBy(fn (array $item) => (int) $item['product']->id)
            ->map(fn (array $item) => (int) $item['product']->id . ':' . max(1, (int) $item['qty']))
            ->implode(',');
        $idempotencySeed = implode('|', [
            $itemFingerprint,
            Str::lower(trim((string) ($payload['customer_phone'] ?? ''))),
            Str::lower(trim((string) ($payload['customer_email'] ?? ''))),
            Str::lower(trim($fulfillmentMethod)),
            now()->format('YmdHi'),
        ]);
        $baseReference = 'storefront-' . sha1($idempotencySeed);
        $shippingQuote = $fulfillmentType === 'physical'
            ? $this->shippingQuoteService->quoteForItems($items, $payload)
            : null;
        $existing = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $publicCompany?->id)
            ->where('source', Sale::SOURCE_ONLINE)
            ->where('external_reference', $baseReference)
            ->first();

        if ($existing && $this->commerceOrders->isCommerceOrder($existing)) {
            if ($this->commerceOrders->isPaymentPending($existing) && !$this->commerceOrders->isExpired($existing)) {
                $sale = $this->commerceOrders->markPendingPayment($existing, [
                    'requested_method' => $paymentMethod,
                    'provider' => $paymentMethod !== 'manual' ? $paymentMethod : null,
                    'public_order_url_requested_at' => now()->toIso8601String(),
                ]);
                $selectedRate = $fulfillmentType === 'physical'
                    ? ($shippingQuote['selected_rate'] ?? data_get($sale->meta, 'commerce.shipping.selected_rate'))
                    : null;
                if (is_array($selectedRate) && $selectedRate !== []) {
                    $sale = $this->commercePricing->applyShippingCharge($sale, $selectedRate);
                }

                $checkout = null;
                if ($paymentMethod !== 'manual') {
                    $checkout = $this->createGatewayCheckout($sale, $paymentMethod);
                    $sale = $sale->fresh();
                }

                return [
                    'sale' => $sale,
                    'gateway_checkout' => $checkout,
                ];
            }

            if ($this->commerceOrders->isExpired($existing) && $this->commerceOrders->status($existing) !== CommerceOrderLifecycleService::STATUS_EXPIRED) {
                $this->commerceOrders->markExpired($existing);
            }
        }

        $externalReference = $existing
            ? $baseReference . '-' . now()->format('His')
            : $baseReference;

        $sale = $this->createDraftSale->execute([
            'external_reference' => $externalReference,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'source' => Sale::SOURCE_ONLINE,
            'transaction_date' => now()->toDateTimeString(),
            'currency_code' => 'IDR',
            'customer_name' => $payload['customer_name'] ?? null,
            'customer_email' => $payload['customer_email'] ?? null,
            'customer_phone' => $payload['customer_phone'] ?? null,
            'customer_address' => $payload['customer_address'] ?? null,
            'customer_note' => $payload['customer_note'] ?? null,
            'items' => $items->map(fn (array $item) => [
                'sellable_key' => 'product:' . $item['product']->id,
                'product_id' => (int) $item['product']->id,
                'qty' => max(1, (int) $item['qty']),
                'unit_price' => (float) $item['product']->sell_price,
                'discount_total' => 0,
                'tax_total' => 0,
                'notes' => null,
            ])->all(),
            'meta' => [
                'commerce' => [
                    'channel' => $channel,
                    'status' => CommerceOrderLifecycleService::STATUS_DRAFT_CHECKOUT,
                    'public_company_id' => $publicCompany?->id,
                    'fulfillment_type' => $fulfillmentType,
                    'fulfillment_method' => $fulfillmentMethod,
                    'fulfillment' => [
                        'status' => CommerceOrderLifecycleService::FULFILLMENT_PENDING,
                    ],
                    'buyer_access' => [
                        'status' => $fulfillmentType === 'physical' ? 'pending' : 'pending_payment',
                    ],
                    'shipping' => [
                        'status' => $fulfillmentMethod === 'delivery'
                            ? ($shippingQuote ? CommerceOrderLifecycleService::SHIPPING_READY : CommerceOrderLifecycleService::SHIPPING_PENDING)
                            : null,
                        'selected_rate' => $shippingQuote['selected_rate'] ?? Arr::get($payload, 'selected_rate'),
                        'available_rates' => $shippingQuote['options'] ?? [],
                    ],
                    'delivery' => [
                        'address' => $payload['customer_address'] ?? null,
                        'quote_provider' => $this->shippingProviders->activeProviderCode() ?: 'manual',
                        'selected_rate' => $shippingQuote['selected_rate'] ?? Arr::get($payload, 'selected_rate'),
                        'quote_payload' => $shippingQuote['request'] ?? null,
                    ],
                    'affiliate' => array_filter([
                        'code' => $affiliateCode,
                        'status' => $affiliateCode ? 'captured' : null,
                        'landing_url' => $affiliateCode ? request()->fullUrl() : null,
                    ]),
                    'platform_fee_snapshot' => [
                        'percentage' => (float) config('services.commerce_creator.platform_fee_percentage', 0),
                        'flat' => (float) config('services.commerce_creator.platform_fee_flat', 0),
                    ],
                    'wallet_settlement' => [
                        'status' => 'pending',
                        'delay_days' => (int) config('services.commerce_creator.settlement_delay_days', 0),
                    ],
                    'payment' => [
                        'requested_method' => $paymentMethod,
                        'status' => CommerceOrderLifecycleService::PAYMENT_PENDING,
                    ],
                ],
            ],
        ]);

        $checkout = null;
        $sale = $this->commerceOrders->markPendingPayment($sale, [
            'requested_method' => $paymentMethod,
            'provider' => $paymentMethod !== 'manual' ? $paymentMethod : null,
            'public_order_url_requested_at' => now()->toIso8601String(),
        ]);
        if (isset($shippingQuote['selected_rate']) && is_array($shippingQuote['selected_rate'])) {
            $sale = $this->commercePricing->applyShippingCharge($sale, $shippingQuote['selected_rate']);
        }

        if ($paymentMethod !== 'manual') {
            $checkout = $this->createGatewayCheckout($sale, $paymentMethod);
            $sale = $sale->fresh();
        }

        return [
            'sale' => $sale,
            'gateway_checkout' => $checkout,
        ];
    }

    /**
     * @return array{sale: Sale, gateway_checkout: array{provider:string,redirect_url:string,reference:string,token:?string}}
     */
    public function retryPayment(Sale $sale): array
    {
        if (!$this->commerceOrders->isRetryable($sale)) {
            throw new CheckoutException(
                'Pesanan ini tidak bisa dibuatkan checkout ulang.',
                ['payment_method' => 'Pesanan ini tidak bisa dibuatkan checkout ulang.']
            );
        }

        $paymentMethod = (string) data_get($sale->meta, 'commerce.payment.provider', data_get($sale->meta, 'commerce.payment.requested_method', 'manual'));
        if ($paymentMethod === '' || $paymentMethod === 'manual') {
            throw new CheckoutException(
                'Pesanan ini menggunakan pembayaran manual.',
                ['payment_method' => 'Pesanan ini menggunakan pembayaran manual.']
            );
        }

        if ($this->commerceOrders->status($sale) === CommerceOrderLifecycleService::STATUS_EXPIRED) {
            $sale = $this->commerceOrders->markPendingPayment($sale, [
                'requested_method' => $paymentMethod,
                'provider' => $paymentMethod,
                'retry_requested_at' => now()->toIso8601String(),
            ]);
        }

        $selectedRate = data_get($sale->meta, 'commerce.shipping.selected_rate');
        if (is_array($selectedRate) && $selectedRate !== []) {
            $sale = $this->commercePricing->applyShippingCharge($sale, $selectedRate);
        }

        $checkout = $this->createGatewayCheckout($sale, $paymentMethod);
        $sale = $sale->fresh();

        return [
            'sale' => $sale,
            'gateway_checkout' => $checkout,
        ];
    }

    /**
     * @return array{provider:string,redirect_url:string,reference:string,token:?string}
     */
    private function createGatewayCheckout(Sale $sale, string $paymentMethod): array
    {
        $this->paymentGateways->assertCheckoutReady($paymentMethod);
        try {
            $checkout = $this->paymentGateways->createCheckoutForTarget($sale, $paymentMethod);
        } catch (\RuntimeException $exception) {
            $this->commerceOrders->markPaymentFailed($sale, $exception->getMessage());
            throw $exception;
        }

        $this->commerceOrders->markPaymentCheckoutCreated($sale, $checkout);

        return $checkout;
    }

    /**
     * @param  Collection<int, array{product:Product,qty:int}>  $items
     */
    private function resolveFulfillmentType(Collection $items): string
    {
        $types = $items
            ->map(function (array $item): string {
                $product = $item['product'];
                $configured = trim((string) data_get($product->meta, 'public_offer.delivery_type', ''));

                if ($configured !== '') {
                    return $configured;
                }

                return $product->track_stock ? 'physical' : 'service';
            })
            ->unique()
            ->values();

        if ($types->contains('physical')) {
            return 'physical';
        }

        if ($types->contains('download') || $types->contains('external_link')) {
            return 'digital';
        }

        return 'service';
    }
}
