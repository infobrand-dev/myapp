<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Storefront\Services\BrandPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontBrandPageController extends Controller
{
    public function __construct(
        private readonly BrandPageService $brandPages,
    ) {
    }

    public function edit(): View
    {
        return view('storefront::brand.edit', [
            'profile' => $this->brandPages->profile(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->brandPages->updateFromRequest($request);

        return redirect()
            ->route('storefront.brand.edit')
            ->with('status', 'Brand page berhasil diperbarui.');
    }
}

