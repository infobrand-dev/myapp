<?php

namespace App\Modules\RajaOngkir\Adapters;

use App\Contracts\RajaOngkirShippingGateway;
use App\Modules\RajaOngkir\Services\RajaOngkirService;

class RajaOngkirShippingGatewayAdapter implements RajaOngkirShippingGateway
{
    public function __construct(
        private readonly RajaOngkirService $service,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function quoteRates(array $payload): array
    {
        return $this->service->quoteRates($payload);
    }
}
