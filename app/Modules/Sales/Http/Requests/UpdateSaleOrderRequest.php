<?php

namespace App\Modules\Sales\Http\Requests;

class UpdateSaleOrderRequest extends StoreSaleOrderRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_order.update_draft') : false;
    }
}
