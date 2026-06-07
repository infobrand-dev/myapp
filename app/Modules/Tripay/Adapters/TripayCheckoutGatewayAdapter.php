<?php

namespace App\Modules\Tripay\Adapters;

use App\Contracts\TripayCheckoutGateway;
use App\Modules\Tripay\Services\TripayService;

class TripayCheckoutGatewayAdapter implements TripayCheckoutGateway
{
    public function __construct(
        private readonly TripayService $service,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        $saleClass = (string) config('platform-core.commerce.sale_model');

        if ($saleClass === '' || !$checkoutTarget instanceof $saleClass) {
            throw new \InvalidArgumentException('Tripay checkout target tidak valid.');
        }

        return $this->service->createOrReuseCheckoutForSale($checkoutTarget);
    }
}
