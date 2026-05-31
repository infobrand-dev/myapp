<?php

namespace App\Support\Commerce;

use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CommerceOrderLifecycleService
{
    public const STATUS_DRAFT_CHECKOUT = 'draft_checkout';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_READY_FOR_FULFILLMENT = 'ready_for_fulfillment';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_CHECKOUT_CREATED = 'checkout_created';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_EXPIRED = 'expired';
    public const PAYMENT_CANCELLED = 'cancelled';

    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_PACKING = 'packing';
    public const FULFILLMENT_READY = 'ready';
    public const FULFILLMENT_HANDOFF = 'handoff';

    public const SHIPPING_PENDING = 'pending';
    public const SHIPPING_READY = 'ready';
    public const SHIPPING_SHIPPED = 'shipped';
    public const SHIPPING_DELIVERED = 'delivered';

    private const DEFAULT_EXPIRY_HOURS = 24;

    public function __construct(
        private readonly FinalizeSaleAction $finalizeSale,
    ) {
    }

    public function status(?Sale $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.status', self::STATUS_DRAFT_CHECKOUT);
    }

    public function fulfillmentStatus(?Sale $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.fulfillment.status', self::FULFILLMENT_PENDING);
    }

    public function shippingStatus(?Sale $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.shipping.status', self::SHIPPING_PENDING);
    }

    public function paymentStatus(?Sale $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.payment.status', self::PAYMENT_PENDING);
    }

    public function isCommerceOrder(?Sale $sale): bool
    {
        return $sale?->source === Sale::SOURCE_ONLINE
            && in_array((string) data_get($sale?->meta, 'commerce.channel'), [
                'public_storefront',
                'public_brand',
                'direct_offer',
                'affiliate_referral',
            ], true);
    }

    public function isPaymentPending(?Sale $sale): bool
    {
        return $this->status($sale) === self::STATUS_PENDING_PAYMENT;
    }

    public function isPayable(?Sale $sale): bool
    {
        return $this->isCommerceOrder($sale)
            && in_array($this->status($sale), [self::STATUS_PENDING_PAYMENT, self::STATUS_EXPIRED], true);
    }

    public function isRetryable(?Sale $sale): bool
    {
        return $this->isCommerceOrder($sale)
            && (string) data_get($sale?->meta, 'commerce.payment.requested_method', 'manual') !== 'manual'
            && in_array($this->status($sale), [self::STATUS_PENDING_PAYMENT, self::STATUS_EXPIRED], true)
            && (float) ($sale?->balance_due ?? 0) > 0;
    }

    public function expiresAt(?Sale $sale): ?Carbon
    {
        $expiresAt = data_get($sale?->meta, 'commerce.expires_at');

        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return null;
        }

        try {
            return Carbon::parse($expiresAt);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isExpired(?Sale $sale): bool
    {
        $expiresAt = $this->expiresAt($sale);

        return $expiresAt !== null && $expiresAt->lte(now());
    }

    public function markPendingPayment(Sale $sale, array $payment = []): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($payment): array {
            data_set($meta, 'commerce.status', self::STATUS_PENDING_PAYMENT);
            data_set($meta, 'commerce.payment', array_filter(array_replace(
                (array) data_get($meta, 'commerce.payment', []),
                $payment
            ), fn ($value) => $value !== null));
            data_set($meta, 'commerce.payment.status', self::PAYMENT_PENDING);
            data_set($meta, 'commerce.payment.failed_at', null);
            data_set($meta, 'commerce.payment.expired_at', null);
            data_set($meta, 'commerce.payment.cancelled_at', null);
            data_set($meta, 'commerce.expires_at', now()->addHours(self::DEFAULT_EXPIRY_HOURS)->toIso8601String());
            data_set($meta, 'commerce.expired_at', null);
            data_set($meta, 'commerce.paid_at', null);
            $this->appendTimeline($meta, 'pending_payment', [
                'status' => self::STATUS_PENDING_PAYMENT,
                'payment' => Arr::only($payment, ['requested_method', 'provider', 'reference']),
            ]);

            return $meta;
        });
    }

    public function markPaymentCheckoutCreated(Sale $sale, array $checkout): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($checkout): array {
            data_set($meta, 'commerce.payment.status', self::PAYMENT_CHECKOUT_CREATED);
            data_set($meta, 'commerce.payment.reference', Arr::get($checkout, 'reference'));
            data_set($meta, 'commerce.payment.redirect_url', Arr::get($checkout, 'redirect_url'));
            data_set($meta, 'commerce.payment.token', Arr::get($checkout, 'token'));
            data_set($meta, 'commerce.payment.provider', Arr::get($checkout, 'provider', data_get($meta, 'commerce.payment.provider')));
            data_set($meta, 'commerce.payment.checkout_created_at', now()->toIso8601String());
            $this->appendTimeline($meta, 'payment_checkout_created', [
                'provider' => Arr::get($checkout, 'provider'),
                'reference' => Arr::get($checkout, 'reference'),
            ]);

            return $meta;
        });
    }

    public function markPaid(Sale $sale): Sale
    {
        if ($sale->isDraft()) {
            $sale = $this->finalizeSale->execute($sale, [
                'payment_status' => Sale::PAYMENT_UNPAID,
            ]);
        }

        return $this->updateMeta($sale, function (array $meta) use ($sale): array {
            $fulfillmentMethod = (string) data_get($meta, 'commerce.fulfillment_method', 'pickup');

            data_set($meta, 'commerce.status', self::STATUS_PAID);
            data_set($meta, 'commerce.paid_at', now()->toIso8601String());
            data_set($meta, 'commerce.expires_at', null);
            data_set($meta, 'commerce.payment.status', self::PAYMENT_PAID);
            data_set($meta, 'commerce.payment.paid_at', now()->toIso8601String());

            if ($fulfillmentMethod === 'delivery') {
                data_set($meta, 'commerce.shipping.status', self::SHIPPING_READY);
            }

            if ((string) data_get($meta, 'commerce.fulfillment.status', '') === '') {
                data_set($meta, 'commerce.fulfillment.status', self::FULFILLMENT_PENDING);
            }

            $this->appendTimeline($meta, 'paid', [
                'status' => self::STATUS_PAID,
                'fulfillment_method' => $fulfillmentMethod,
            ]);

            return $meta;
        })->fresh();
    }

    public function markReadyForFulfillment(Sale $sale, ?string $note = null): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($note): array {
            data_set($meta, 'commerce.status', self::STATUS_READY_FOR_FULFILLMENT);
            data_set($meta, 'commerce.fulfillment.status', self::FULFILLMENT_READY);
            data_set($meta, 'commerce.fulfillment.ready_at', now()->toIso8601String());

            if ($note !== null && trim($note) !== '') {
                data_set($meta, 'commerce.fulfillment.note', trim($note));
            }

            $this->appendTimeline($meta, 'ready_for_fulfillment', [
                'status' => self::STATUS_READY_FOR_FULFILLMENT,
                'note' => $note ? trim($note) : null,
            ]);

            return $meta;
        });
    }

    public function markPacking(Sale $sale, ?string $note = null): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($note): array {
            data_set($meta, 'commerce.fulfillment.status', self::FULFILLMENT_PACKING);
            data_set($meta, 'commerce.fulfillment.packing_at', now()->toIso8601String());

            if ($note !== null && trim($note) !== '') {
                data_set($meta, 'commerce.fulfillment.note', trim($note));
            }

            $this->appendTimeline($meta, 'packing', [
                'status' => self::FULFILLMENT_PACKING,
                'note' => $note ? trim($note) : null,
            ]);

            return $meta;
        });
    }

    public function markShipped(Sale $sale, array $shipment): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($shipment): array {
            data_set($meta, 'commerce.shipping.status', self::SHIPPING_SHIPPED);
            data_set($meta, 'commerce.shipping.shipped_at', now()->toIso8601String());
            data_set($meta, 'commerce.shipping.tracking_number', Arr::get($shipment, 'tracking_number'));
            data_set($meta, 'commerce.shipping.courier_name', Arr::get($shipment, 'courier_name'));
            data_set($meta, 'commerce.shipping.service_name', Arr::get($shipment, 'service_name'));
            data_set($meta, 'commerce.shipping.note', Arr::get($shipment, 'note'));
            data_set($meta, 'commerce.fulfillment.status', self::FULFILLMENT_HANDOFF);
            $this->appendTimeline($meta, 'shipped', [
                'status' => self::SHIPPING_SHIPPED,
                'tracking_number' => Arr::get($shipment, 'tracking_number'),
                'courier_name' => Arr::get($shipment, 'courier_name'),
                'service_name' => Arr::get($shipment, 'service_name'),
            ]);

            return $meta;
        });
    }

    public function markShippingRateSelected(Sale $sale, array $rate): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($rate): array {
            data_set($meta, 'commerce.shipping.selected_rate', array_filter([
                'provider' => Arr::get($rate, 'provider'),
                'courier_name' => Arr::get($rate, 'courier_name'),
                'service_name' => Arr::get($rate, 'service_name'),
                'price' => (float) Arr::get($rate, 'price', 0),
                'etd' => Arr::get($rate, 'etd'),
                'selected_at' => Arr::get($rate, 'selected_at', now()->toIso8601String()),
                'raw' => Arr::get($rate, 'raw', []),
            ], fn ($value) => $value !== null && $value !== ''));
            data_set($meta, 'commerce.shipping.status', self::SHIPPING_READY);
            $this->appendTimeline($meta, 'shipping_rate_selected', [
                'courier_name' => Arr::get($rate, 'courier_name'),
                'service_name' => Arr::get($rate, 'service_name'),
                'price' => (float) Arr::get($rate, 'price', 0),
                'etd' => Arr::get($rate, 'etd'),
            ]);

            return $meta;
        });
    }

    public function markExpired(Sale $sale): Sale
    {
        return $this->updateMeta($sale, function (array $meta): array {
            data_set($meta, 'commerce.status', self::STATUS_EXPIRED);
            data_set($meta, 'commerce.expired_at', now()->toIso8601String());
            data_set($meta, 'commerce.payment.status', self::PAYMENT_EXPIRED);
            data_set($meta, 'commerce.payment.expired_at', now()->toIso8601String());
            $this->appendTimeline($meta, 'expired', [
                'status' => self::STATUS_EXPIRED,
            ]);

            return $meta;
        });
    }

    public function markCancelled(Sale $sale, ?string $reason = null): Sale
    {
        $sale->update([
            'status' => Sale::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'void_reason' => $reason ?: $sale->void_reason,
        ]);

        return $this->updateMeta($sale, function (array $meta) use ($reason): array {
            data_set($meta, 'commerce.status', self::STATUS_CANCELLED);
            data_set($meta, 'commerce.cancelled_at', now()->toIso8601String());
            data_set($meta, 'commerce.payment.status', self::PAYMENT_CANCELLED);
            data_set($meta, 'commerce.payment.cancelled_at', now()->toIso8601String());

            if ($reason !== null && trim($reason) !== '') {
                data_set($meta, 'commerce.cancel_reason', trim($reason));
            }

            $this->appendTimeline($meta, 'cancelled', [
                'status' => self::STATUS_CANCELLED,
                'reason' => $reason ? trim($reason) : null,
            ]);

            return $meta;
        });
    }

    public function markPaymentFailed(Sale $sale, ?string $reason = null): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($reason): array {
            data_set($meta, 'commerce.payment.status', self::PAYMENT_FAILED);
            data_set($meta, 'commerce.payment.failed_at', now()->toIso8601String());

            if ($reason !== null && trim($reason) !== '') {
                data_set($meta, 'commerce.payment.failure_reason', trim($reason));
            }

            $this->appendTimeline($meta, 'payment_failed', [
                'reason' => $reason ? trim($reason) : null,
            ]);

            return $meta;
        });
    }

    public function markPaymentCancelled(Sale $sale, ?string $reason = null): Sale
    {
        return $this->updateMeta($sale, function (array $meta) use ($reason): array {
            data_set($meta, 'commerce.payment.status', self::PAYMENT_CANCELLED);
            data_set($meta, 'commerce.payment.cancelled_at', now()->toIso8601String());

            if ($reason !== null && trim($reason) !== '') {
                data_set($meta, 'commerce.payment.cancel_reason', trim($reason));
            }

            $this->appendTimeline($meta, 'payment_cancelled', [
                'reason' => $reason ? trim($reason) : null,
            ]);

            return $meta;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeline(?Sale $sale): array
    {
        $timeline = data_get($sale?->meta, 'commerce.timeline', []);

        return is_array($timeline) ? array_values($timeline) : [];
    }

    private function updateMeta(Sale $sale, callable $mutator): Sale
    {
        $meta = is_array($sale->meta) ? $sale->meta : [];
        $meta = $mutator($meta);

        $sale->update([
            'meta' => $meta,
        ]);

        return $sale->fresh();
    }

    private function appendTimeline(array &$meta, string $event, array $context = []): void
    {
        $timeline = data_get($meta, 'commerce.timeline', []);
        $timeline = is_array($timeline) ? array_values($timeline) : [];
        $timeline[] = array_filter([
            'event' => $event,
            'at' => now()->toIso8601String(),
            'context' => array_filter($context, fn ($value) => $value !== null && $value !== ''),
        ], fn ($value) => $value !== null && $value !== []);

        data_set($meta, 'commerce.timeline', $timeline);
    }
}
