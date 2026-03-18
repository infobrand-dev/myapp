<?php

namespace App\Modules\Purchases\Http\Requests;

class UpdateDraftPurchaseRequest extends StoreDraftPurchaseRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.edit_draft') : false;
    }
}
