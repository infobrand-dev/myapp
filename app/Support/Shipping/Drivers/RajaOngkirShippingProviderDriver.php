<?php

namespace App\Support\Shipping\Drivers;

use App\Contracts\RajaOngkirShippingGateway;
use App\Support\Shipping\Contracts\ShippingProviderDriver;

class RajaOngkirShippingProviderDriver implements ShippingProviderDriver
{
    public function __construct(
        private readonly RajaOngkirShippingGateway $service,
    ) {
    }

    public function provider(): string
    {
        return 'rajaongkir';
    }

    public function label(): string
    {
        return 'RajaOngkir';
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
        return ['origin_area_id', 'destination_area_id'];
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
        return 'rajaongkir.settings.edit';
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
