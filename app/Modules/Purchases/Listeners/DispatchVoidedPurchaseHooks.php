<?php

namespace App\Modules\Purchases\Listeners;

use App\Modules\Purchases\Events\PurchaseVoided;
use App\Modules\Purchases\Services\PurchaseIntegrationPayloadBuilder;
use App\Support\HookManager;

class DispatchVoidedPurchaseHooks
{
    private $hooks;
    private $payloadBuilder;

    public function __construct(
        HookManager $hooks,
        PurchaseIntegrationPayloadBuilder $payloadBuilder
    ) {
        $this->hooks = $hooks;
        $this->payloadBuilder = $payloadBuilder;
    }

    public function handle(PurchaseVoided $event): void
    {
        $this->hooks->dispatch('purchases.voided', [
            'purchase' => $event->purchase,
            'payload' => $this->payloadBuilder->build($event->purchase),
            'event' => 'voided',
        ]);
    }
}
