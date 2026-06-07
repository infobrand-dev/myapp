<?php

namespace App\Modules\Biteship\Adapters;

use App\Contracts\BiteshipShippingGateway;
use App\Modules\Biteship\Services\BiteshipService;

class BiteshipShippingGatewayAdapter implements BiteshipShippingGateway
{
    public function __construct(
        private readonly BiteshipService $service,
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
