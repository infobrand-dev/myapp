<?php

namespace App\Modules\Purchases\Http\Requests;

class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchase_order.update_draft') : false;
    }
}
