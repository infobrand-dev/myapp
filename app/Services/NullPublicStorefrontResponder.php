<?php

namespace App\Services;

use App\Contracts\PublicStorefrontResponder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NullPublicStorefrontResponder implements PublicStorefrontResponder
{
    public function renderRoot(Request $request): View|RedirectResponse|null
    {
        return null;
    }
}
