<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleReturnCalculationService;

class CalculateReturnTotalsAction
{
    private $calculator;

    public function __construct(SaleReturnCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function execute(Sale $sale, array $requestedItems, array $returnableMap): array
    {
        return $this->calculator->calculate($sale, $requestedItems, $returnableMap);
    }
}
