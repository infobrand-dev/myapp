<?php

namespace App\Modules\Midtrans\Adapters;

use App\Contracts\MidtransCheckoutGateway;
use App\Modules\Midtrans\Services\MidtransService;

class MidtransCheckoutGatewayAdapter implements MidtransCheckoutGateway
{
    public function __construct(
        private readonly MidtransService $service,
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
            throw new \InvalidArgumentException('Midtrans checkout target tidak valid.');
        }

        return $this->service->createOrReuseCheckoutForSale($checkoutTarget);
    }
}
