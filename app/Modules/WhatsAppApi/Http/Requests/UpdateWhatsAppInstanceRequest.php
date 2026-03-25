<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

class UpdateWhatsAppInstanceRequest extends StoreWhatsAppInstanceRequest
{
    // Same rules as Store; cloud/non-cloud conditional enforcement happens in controller.
}
