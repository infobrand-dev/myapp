<?php

namespace App\Modules\Sales\Listeners;

use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Services\SaleIntegrationPayloadBuilder;
use App\Support\HookManager;

class DispatchVoidedSaleHooks
{
    private $hooks;
    private $payloadBuilder;

    public function __construct(HookManager $hooks, SaleIntegrationPayloadBuilder $payloadBuilder)
    {
        $this->hooks = $hooks;
        $this->payloadBuilder = $payloadBuilder;
    }

    public function handle(SaleVoided $event): void
    {
        $this->hooks->dispatch('sales.voided', [
            'sale' => $event->sale,
            'payload' => $this->payloadBuilder->build($event->sale->fresh(['items', 'voidLogs'])),
            'event' => 'voided',
        ]);
    }
}
