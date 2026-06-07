<?php

namespace App\Modules\Sales\Adapters;

use App\Contracts\CommerceDraftFinalizer;
use App\Modules\Sales\Actions\FinalizeSaleAction;

class SaleDraftFinalizer implements CommerceDraftFinalizer
{
    public function __construct(
        private readonly FinalizeSaleAction $finalizeSale,
    ) {
    }

    public function finalize(object $target, array $attributes = []): object
    {
        $saleClass = (string) config('platform-core.commerce.sale_model');

        if ($saleClass === '' || !$target instanceof $saleClass) {
            return $target;
        }

        return $this->finalizeSale->execute($target, $attributes);
    }
}
