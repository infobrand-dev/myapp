<?php

namespace App\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

interface PublicStorefrontResponder
{
    public function renderRoot(Request $request): View|RedirectResponse|null;
}
