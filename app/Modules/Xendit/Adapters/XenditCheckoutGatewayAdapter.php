<?php

namespace App\Modules\Xendit\Adapters;

use App\Contracts\XenditCheckoutGateway;
use App\Modules\Xendit\Services\XenditService;

class XenditCheckoutGatewayAdapter implements XenditCheckoutGateway
{
    public function __construct(
        private readonly XenditService $service,
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
            throw new \InvalidArgumentException('Xendit checkout target tidak valid.');
        }

        return $this->service->createOrReuseCheckoutForSale($checkoutTarget);
    }
}
