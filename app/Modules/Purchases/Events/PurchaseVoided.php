<?php

namespace App\Modules\Purchases\Events;

use App\Modules\Purchases\Models\Purchase;

class PurchaseVoided
{
    public $purchase;

    public function __construct(Purchase $purchase)
    {
        $this->purchase = $purchase;
    }
}
