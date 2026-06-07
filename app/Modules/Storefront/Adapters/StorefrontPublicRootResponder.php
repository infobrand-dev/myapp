<?php

namespace App\Modules\Storefront\Adapters;

use App\Contracts\PublicStorefrontResponder;
use App\Modules\Storefront\Http\Controllers\PublicStorefrontController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontPublicRootResponder implements PublicStorefrontResponder
{
    public function __construct(
        private readonly PublicStorefrontController $controller
    ) {
    }

    public function renderRoot(Request $request): View|RedirectResponse|null
    {
        return $this->controller->index($request);
    }
}
