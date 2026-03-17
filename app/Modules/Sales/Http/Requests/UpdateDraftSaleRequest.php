<?php

namespace App\Modules\Sales\Http\Requests;

class UpdateDraftSaleRequest extends StoreDraftSaleRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.update-draft') : false;
    }
}
