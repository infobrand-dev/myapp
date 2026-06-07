<?php

namespace App\Http\Controllers;

use App\Contracts\PublicStorefrontResponder;
use App\Services\PlatformAffiliateService;
use App\Support\Commerce\PublicStorefrontContext;
use App\Support\PublicModuleCatalog;
use App\Support\TenantContext;
use App\Support\WorkspaceUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicRootController extends Controller
{
    public function __invoke(
        Request $request,
        PlatformAffiliateService $affiliateService,
        PublicStorefrontContext $publicStorefront,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl,
        PublicStorefrontResponder $storefrontResponder
    ): View|RedirectResponse {
        $affiliateService->captureFromRequest($request);

        if ($request->attributes->get('platform_admin_host')) {
            return auth()->check()
                ? redirect()->route('platform.dashboard')
                : redirect()->route('login');
        }

        if ($request->attributes->has('tenant_id')) {
            TenantContext::setCurrentId((int) $request->attributes->get('tenant_id'));

            if ($publicStorefront->enabled()) {
                $response = $storefrontResponder->renderRoot($request);
                if ($response !== null) {
                    return $response;
                }
            }

            abort(404);
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-meetra', [
            'featuredModules' => $catalog->modules($catalog->meetraFeaturedSlugs()),
            'accountingModules' => $catalog->modules($catalog->accountingSlugs()),
        ]);
    }
}
