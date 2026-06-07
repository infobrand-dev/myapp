<?php

namespace App\Support\Commerce;

use App\Contracts\CommerceDraftFinalizer;
use App\Services\PlatformActivityRecorder;
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
        private readonly CommerceDraftFinalizer $draftFinalizer,
        private readonly PlatformActivityRecorder $activity,
    ) {
    }

    public function status(?object $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.status', self::STATUS_DRAFT_CHECKOUT);
    }

    public function fulfillmentStatus(?object $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.fulfillment.status', self::FULFILLMENT_PENDING);
    }

    public function shippingStatus(?object $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.shipping.status', self::SHIPPING_PENDING);
    }

    public function paymentStatus(?object $sale): string
    {
        return (string) data_get($sale?->meta, 'commerce.payment.status', self::PAYMENT_PENDING);
    }

    public function isCommerceOrder(?object $sale): bool
    {
        return data_get($sale, 'source') === config('platform-core.commerce.sale_source_online', 'online')
            && in_array((string) data_get($sale?->meta, 'commerce.channel'), [
                'public_storefront',
                'public_brand',
                'direct_offer',
                'affiliate_referral',
            ], true);
    }

    public function isPaymentPending(?object $sale): bool
    {
        return $this->status($sale) === self::STATUS_PENDING_PAYMENT;
    }

    public function isPayable(?object $sale): bool
    {
        return $this->isCommerceOrder($sale)
            && in_array($this->status($sale), [self::STATUS_PENDING_PAYMENT, self::STATUS_EXPIRED], true);
    }

    public function isRetryable(?object $sale): bool
    {
        return $this->isCommerceOrder($sale)
            && (string) data_get($sale?->meta, 'commerce.payment.requested_method', 'manual') !== 'manual'
            && in_array($this->status($sale), [self::STATUS_PENDING_PAYMENT, self::STATUS_EXPIRED], true)
            && (float) ($sale?->balance_due ?? 0) > 0;
    }

    public function expiresAt(?object $sale): ?Carbon
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

    public function isExpired(?object $sale): bool
    {
        $expiresAt = $this->expiresAt($sale);

        return $expiresAt !== null && $expiresAt->lte(now());
    }

    public function markPendingPayment(object $sale, array $payment = []): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($payment): array {
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
        }),
            'commerce.pending_payment',
            'Commerce order masuk ke status pending payment.',
            [
                'status' => self::STATUS_PENDING_PAYMENT,
                'payment' => Arr::only($payment, ['requested_method', 'provider', 'reference']),
            ]
        );
    }

    public function markPaymentCheckoutCreated(object $sale, array $checkout): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($checkout): array {
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
        }),
            'commerce.payment_checkout_created',
            'Checkout payment commerce berhasil dibuat.',
            Arr::only($checkout, ['provider', 'reference', 'redirect_url'])
        );
    }

    public function markPaid(object $sale): object
    {
        if (method_exists($sale, 'isDraft') && $sale->isDraft()) {
            $sale = $this->draftFinalizer->finalize($sale, [
                'payment_status' => config('platform-core.commerce.sale_payment_unpaid', 'unpaid'),
            ]);
        }

        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($sale): array {
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
        })->fresh(),
            'commerce.paid',
            'Commerce order ditandai lunas.',
            [
                'status' => self::STATUS_PAID,
                'payment_status' => self::PAYMENT_PAID,
            ]
        );
    }

    public function markReadyForFulfillment(object $sale, ?string $note = null): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($note): array {
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
        }),
            'commerce.ready_for_fulfillment',
            'Commerce order siap untuk fulfillment.',
            [
                'status' => self::STATUS_READY_FOR_FULFILLMENT,
                'note' => $note ? trim($note) : null,
            ]
        );
    }

    public function markPacking(object $sale, ?string $note = null): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($note): array {
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
        }),
            'commerce.packing',
            'Commerce order masuk proses packing.',
            [
                'status' => self::FULFILLMENT_PACKING,
                'note' => $note ? trim($note) : null,
            ]
        );
    }

    public function markShipped(object $sale, array $shipment): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($shipment): array {
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
        }),
            'commerce.shipped',
            'Commerce order diserahkan ke pengiriman.',
            Arr::only($shipment, ['tracking_number', 'courier_name', 'service_name'])
        );
    }

    public function markShippingRateSelected(object $sale, array $rate): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($rate): array {
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
        }),
            'commerce.shipping_rate_selected',
            'Rate pengiriman commerce dipilih.',
            Arr::only($rate, ['provider', 'courier_name', 'service_name', 'price', 'etd'])
        );
    }

    public function markExpired(object $sale): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta): array {
            data_set($meta, 'commerce.status', self::STATUS_EXPIRED);
            data_set($meta, 'commerce.expired_at', now()->toIso8601String());
            data_set($meta, 'commerce.payment.status', self::PAYMENT_EXPIRED);
            data_set($meta, 'commerce.payment.expired_at', now()->toIso8601String());
            $this->appendTimeline($meta, 'expired', [
                'status' => self::STATUS_EXPIRED,
            ]);

            return $meta;
        }),
            'commerce.expired',
            'Commerce order kedaluwarsa.',
            ['status' => self::STATUS_EXPIRED]
        );
    }

    public function markCancelled(object $sale, ?string $reason = null): object
    {
        $sale->update([
            'status' => config('platform-core.commerce.sale_status_cancelled', 'cancelled'),
            'cancelled_at' => now(),
            'void_reason' => $reason ?: $sale->void_reason,
        ]);

        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($reason): array {
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
        }),
            'commerce.cancelled',
            'Commerce order dibatalkan.',
            [
                'status' => self::STATUS_CANCELLED,
                'reason' => $reason ? trim($reason) : null,
            ]
        );
    }

    public function markPaymentFailed(object $sale, ?string $reason = null): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($reason): array {
            data_set($meta, 'commerce.payment.status', self::PAYMENT_FAILED);
            data_set($meta, 'commerce.payment.failed_at', now()->toIso8601String());

            if ($reason !== null && trim($reason) !== '') {
                data_set($meta, 'commerce.payment.failure_reason', trim($reason));
            }

            $this->appendTimeline($meta, 'payment_failed', [
                'reason' => $reason ? trim($reason) : null,
            ]);

            return $meta;
        }),
            'commerce.payment_failed',
            'Pembayaran commerce gagal.',
            ['reason' => $reason ? trim($reason) : null]
        );
    }

    public function markPaymentCancelled(object $sale, ?string $reason = null): object
    {
        return $this->recordStateTransition(
            $this->updateMeta($sale, function (array $meta) use ($reason): array {
            data_set($meta, 'commerce.payment.status', self::PAYMENT_CANCELLED);
            data_set($meta, 'commerce.payment.cancelled_at', now()->toIso8601String());

            if ($reason !== null && trim($reason) !== '') {
                data_set($meta, 'commerce.payment.cancel_reason', trim($reason));
            }

            $this->appendTimeline($meta, 'payment_cancelled', [
                'reason' => $reason ? trim($reason) : null,
            ]);

            return $meta;
        }),
            'commerce.payment_cancelled',
            'Pembayaran commerce dibatalkan.',
            ['reason' => $reason ? trim($reason) : null]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeline(?object $sale): array
    {
        $timeline = data_get($sale?->meta, 'commerce.timeline', []);

        return is_array($timeline) ? array_values($timeline) : [];
    }

    private function updateMeta(object $sale, callable $mutator): object
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

    private function recordStateTransition(object $sale, string $eventType, string $summary, array $payload = []): object
    {
        $this->activity->record(
            'commerce',
            $eventType,
            get_class($sale),
            method_exists($sale, 'getKey') ? $sale->getKey() : null,
            $summary,
            array_filter([
                'status' => $this->status($sale),
                'payment_status' => $this->paymentStatus($sale),
                'fulfillment_status' => $this->fulfillmentStatus($sale),
                'shipping_status' => $this->shippingStatus($sale),
                'sale_number' => data_get($sale, 'sale_number'),
                'external_reference' => data_get($sale, 'external_reference'),
            ] + $payload, fn ($value) => $value !== null && $value !== '')
        );

        return $sale;
    }
}
