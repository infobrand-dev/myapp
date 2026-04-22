<?php

namespace App\Modules\Sales\Http\Requests;

class UpdateSaleQuotationRequest extends StoreSaleQuotationRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_quotation.update_draft') : false;
    }
}
