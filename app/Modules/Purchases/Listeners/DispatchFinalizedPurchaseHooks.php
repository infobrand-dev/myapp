<?php

namespace App\Modules\Purchases\Listeners;

use App\Modules\Purchases\Events\PurchaseFinalized;
use App\Modules\Purchases\Services\PurchaseIntegrationPayloadBuilder;
use App\Support\HookManager;

class DispatchFinalizedPurchaseHooks
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

    public function handle(PurchaseFinalized $event): void
    {
        $this->hooks->dispatch('purchases.finalized', [
            'purchase' => $event->purchase,
            'payload' => $this->payloadBuilder->build($event->purchase),
            'event' => 'finalized',
        ]);
    }
}
