<?php

namespace App\Support\Payments\Drivers;

use App\Contracts\MidtransCheckoutGateway;
use App\Support\Payments\Contracts\PaymentGatewayDriver;

class MidtransPaymentGatewayDriver implements PaymentGatewayDriver
{
    public function __construct(
        private readonly MidtransCheckoutGateway $service,
    ) {
    }

    public function provider(): string
    {
        return 'midtrans';
    }

    public function label(): string
    {
        return 'Midtrans';
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function requiredConfigFields(): array
    {
        return ['server_key', 'client_key'];
    }

    public function capabilities(): array
    {
        return [
            'public_checkout' => true,
            'supports_retry' => true,
            'supports_webhook' => true,
            'supports_token' => true,
        ];
    }

    public function settingsRoute(): ?string
    {
        return 'midtrans.settings.edit';
    }

    public function transactionsRoute(): ?string
    {
        return 'midtrans.transactions.index';
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        $checkout = $this->service->createCheckoutForTarget($checkoutTarget);

        return [
            'provider' => $this->provider(),
            'redirect_url' => (string) $checkout['redirect_url'],
            'reference' => (string) $checkout['order_id'],
            'token' => (string) ($checkout['snap_token'] ?? ''),
        ];
    }
}
