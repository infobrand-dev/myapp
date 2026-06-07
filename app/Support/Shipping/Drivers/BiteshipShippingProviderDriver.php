<?php

namespace App\Support\Shipping\Drivers;

use App\Contracts\BiteshipShippingGateway;
use App\Support\Shipping\Contracts\ShippingProviderDriver;

class BiteshipShippingProviderDriver implements ShippingProviderDriver
{
    public function __construct(
        private readonly BiteshipShippingGateway $service,
    ) {
    }

    public function provider(): string
    {
        return 'biteship';
    }

    public function label(): string
    {
        return 'Biteship';
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function requiredConfigFields(): array
    {
        return ['api_key'];
    }

    public function requiredCheckoutFields(): array
    {
        return ['origin_postal_code', 'destination_postal_code'];
    }

    public function capabilities(): array
    {
        return [
            'multi_rate_checkout' => true,
            'supports_quote' => true,
        ];
    }

    public function settingsRoute(): ?string
    {
        return 'biteship.settings.edit';
    }

    public function transactionsRoute(): ?string
    {
        return null;
    }

    public function quoteRates(array $payload): array
    {
        return $this->service->quoteRates($payload);
    }
}
