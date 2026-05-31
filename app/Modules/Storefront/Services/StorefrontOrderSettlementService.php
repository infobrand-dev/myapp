<?php

namespace App\Modules\Storefront\Services;

use App\Models\Tenant;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Storefront\Mail\StorefrontOrderAccessMail;
use App\Support\Commerce\CommerceOrderLifecycleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class StorefrontOrderSettlementService
{
    public function __construct(
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    /**
     * @param  Collection<int, mixed>  $payables
     * @param  Collection<int, mixed>  $allocations
     */
    public function handle(mixed $payment, Collection $payables, Collection $allocations): void
    {
        if (!$payment instanceof Payment) {
            return;
        }

        $payables
            ->filter(fn ($payable): bool => $payable instanceof Sale)
            ->each(function (Sale $sale): void {
                $sale = $sale->fresh(['items']);

                if (!$sale || !$this->commerceOrders->isCommerceOrder($sale)) {
                    return;
                }

                if (!in_array((string) $sale->payment_status, [Sale::PAYMENT_PAID, Sale::PAYMENT_OVERPAID], true)) {
                    return;
                }

                if ($this->commerceOrders->status($sale) !== CommerceOrderLifecycleService::STATUS_PAID) {
                    $sale = $this->commerceOrders->markPaid($sale);
                }

                $sale = $this->markBuyerAccess($sale);
                $this->sendBuyerAccessMail($sale);
            });
    }

    private function markBuyerAccess(Sale $sale): Sale
    {
        $meta = is_array($sale->meta) ? $sale->meta : [];
        $fulfillmentType = (string) data_get($meta, 'commerce.fulfillment_type', 'physical');

        if ($fulfillmentType === 'physical') {
            if ($this->commerceOrders->status($sale) !== CommerceOrderLifecycleService::STATUS_READY_FOR_FULFILLMENT) {
                $sale = $this->commerceOrders->markReadyForFulfillment($sale, 'Pembayaran diterima dan order masuk antrean fulfillment.');
            }

            return $sale;
        }

        data_set($meta, 'commerce.buyer_access.status', 'available');
        data_set($meta, 'commerce.buyer_access.available_at', now()->toIso8601String());
        data_set($meta, 'commerce.buyer_access.order_url', URL::signedRoute('storefront.public.orders.show', $this->orderRouteParameters($sale)));

        $sale->update(['meta' => $meta]);

        return $sale->fresh(['items']);
    }

    private function sendBuyerAccessMail(Sale $sale): void
    {
        $email = trim((string) $sale->customer_email_snapshot);
        if ($email === '') {
            return;
        }

        if (data_get($sale->meta, 'commerce.buyer_access.email_sent_at')) {
            return;
        }

        Mail::to($email)->send(new StorefrontOrderAccessMail($sale));

        $meta = is_array($sale->meta) ? $sale->meta : [];
        data_set($meta, 'commerce.buyer_access.email_sent_at', now()->toIso8601String());
        $sale->update(['meta' => $meta]);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRouteParameters(Sale $sale): array
    {
        $parameters = ['sale' => $sale];
        $slug = (string) Tenant::query()->whereKey($sale->tenant_id)->value('slug');

        if ($slug !== '') {
            $parameters['account'] = $slug;
        }

        return $parameters;
    }
}
