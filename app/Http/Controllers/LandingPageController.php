<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlatformAffiliateService;
use App\Services\TenantOnboardingSalesService;
use App\Support\PublicModuleCatalog;
use App\Support\WorkspaceUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function omnichannel(
        Request $request,
        TenantOnboardingSalesService $sales,
        PlatformAffiliateService $affiliateService,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        $affiliate = $affiliateService->captureFromRequest($request);

        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-omnichannel', [
            'plans' => $sales->publicPlans(),
            'workspaceUrl' => $workspaceUrl->forCurrentUser($request, false),
            'affiliate' => $affiliate,
        ]);
    }

    public function accounting(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-accounting', [
            'publicPlans' => $sales->publicPlans('accounting'),
            'modules' => $catalog->modules($catalog->accountingSlugs()),
            'supportingModules' => $catalog->modules(['purchases', 'inventory', 'discounts']),
            'addonModules' => $catalog->modules(['point-of-sale']),
        ]);
    }

    public function commerce(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-commerce', [
            'publicPlans' => $sales->publicPlans('commerce'),
            'modules' => $catalog->modules($catalog->commerceSlugs()),
            'supportingModules' => $catalog->modules(['products', 'contacts', 'sales', 'payments']),
        ]);
    }

    public function crm(
        Request $request,
        TenantOnboardingSalesService $sales,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-crm', [
            'publicPlans' => $sales->publicPlans('crm'),
            'modules' => $catalog->modules($catalog->crmSlugs()),
            'supportingModules' => $catalog->modules(['contacts']),
        ]);
    }

    public function mulaiDigital(
        Request $request,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-mulai-digital', [
            'modules' => $catalog->modules([
                'contacts',
                'products',
                'sales',
                'payments',
                'finance',
                'reports',
            ]),
        ]);
    }

    public function websiteApps(
        Request $request,
        PublicModuleCatalog $catalog,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-website-apps', [
            'modules' => $catalog->modules([
                'live_chat',
                'crm',
                'contacts',
                'sales',
                'payments',
                'reports',
            ]),
        ]);
    }

    public function websiteService(Request $request, WorkspaceUrl $workspaceUrl): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-website-service');
    }

    public function products(Request $request, WorkspaceUrl $workspaceUrl): View|RedirectResponse
    {
        if ($redirect = $this->landingHostRedirect($request)) {
            return $redirect;
        }

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request));
        }

        return view('landing-products');
    }

    public function workspaceFinder(
        Request $request,
        PlatformAffiliateService $affiliateService,
        WorkspaceUrl $workspaceUrl
    ): View|RedirectResponse
    {
        $affiliateService->captureFromRequest($request);

        if (auth()->check()) {
            return redirect()->away($workspaceUrl->forCurrentUser($request, false));
        }

        return view('workspace-finder');
    }

    public function about(): View
    {
        return view('about');
    }

    public function contact(): View
    {
        return view('contact');
    }

    public function security(): View
    {
        return view('security');
    }

    public function privacy(): View
    {
        return view('privacy');
    }

    public function terms(): View
    {
        return view('terms');
    }

    public function affiliateRedirect(Request $request, string $slug, PlatformAffiliateService $affiliateService): RedirectResponse
    {
        $affiliate = $affiliateService->findActiveBySlug($slug);
        abort_unless($affiliate, 404);

        $affiliateService->captureAffiliate($request, $affiliate);

        return redirect()->route('landing');
    }

    public function redirectToWorkspaceLogin(Request $request, WorkspaceUrl $workspaceUrl): RedirectResponse
    {
        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:100'],
        ]);

        $workspace = strtolower(trim((string) $data['workspace']));
        $workspace = preg_replace('/[^a-z0-9-]/', '', $workspace) ?: '';

        $tenant = Tenant::query()
            ->where('slug', $workspace)
            ->active()
            ->first();

        if (!$tenant) {
            return back()->withErrors([
                'workspace' => 'Workspace tidak ditemukan atau belum aktif.',
            ])->withInput();
        }

        return redirect()->away($workspaceUrl->loginForTenant($request, $tenant->slug));
    }

    private function landingHostRedirect(Request $request): ?RedirectResponse
    {
        if ($request->attributes->get('platform_admin_host')) {
            return auth()->check()
                ? redirect()->route('platform.dashboard')
                : redirect()->route('login');
        }

        if ($request->attributes->has('tenant_id')) {
            return redirect()->route('landing');
        }

        return null;
    }
}
