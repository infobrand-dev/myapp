<?php

namespace App\Modules\EmailMarketing\Http\Requests;

class UpdateEmailCampaignRequest extends StoreEmailCampaignRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_attachments'   => ['array'],
            'remove_attachments.*' => ['integer'],
        ]);
    }
}
