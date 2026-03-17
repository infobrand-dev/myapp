<?php

namespace App\Modules\Sales\Listeners;

use App\Modules\Sales\Events\SaleFinalized;
use App\Modules\Sales\Services\SaleIntegrationPayloadBuilder;
use App\Support\HookManager;

class DispatchFinalizedSaleHooks
{
    private $hooks;
    private $payloadBuilder;

    public function __construct(HookManager $hooks, SaleIntegrationPayloadBuilder $payloadBuilder)
    {
        $this->hooks = $hooks;
        $this->payloadBuilder = $payloadBuilder;
    }

    public function handle(SaleFinalized $event): void
    {
        $this->hooks->dispatch('sales.finalized', [
            'sale' => $event->sale,
            'payload' => $this->payloadBuilder->build($event->sale->fresh(['items'])),
            'event' => 'finalized',
        ]);
    }
}
