<?php

namespace App\Support\Payments\Drivers;

use App\Contracts\XenditCheckoutGateway;
use App\Support\Payments\Contracts\PaymentGatewayDriver;

class XenditPaymentGatewayDriver implements PaymentGatewayDriver
{
    public function __construct(
        private readonly XenditCheckoutGateway $service,
    ) {
    }

    public function provider(): string
    {
        return 'xendit';
    }

    public function label(): string
    {
        return 'Xendit';
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function requiredConfigFields(): array
    {
        return ['secret_key', 'webhook_token'];
    }

    public function capabilities(): array
    {
        return [
            'public_checkout' => true,
            'supports_retry' => true,
            'supports_webhook' => true,
            'supports_token' => false,
        ];
    }

    public function settingsRoute(): ?string
    {
        return 'xendit.settings.edit';
    }

    public function transactionsRoute(): ?string
    {
        return 'xendit.transactions.index';
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        $checkout = $this->service->createCheckoutForTarget($checkoutTarget);

        return [
            'provider' => $this->provider(),
            'redirect_url' => (string) $checkout['redirect_url'],
            'reference' => (string) $checkout['reference'],
            'token' => null,
        ];
    }
}
