<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantOnboardingSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request, TenantOnboardingSalesService $sales): View|RedirectResponse
    {
        if ($request->attributes->get('platform_admin_host')) {
            return auth()->check()
                ? redirect()->route('platform.dashboard')
                : redirect()->route('login');
        }

        if ($request->attributes->has('tenant_id')) {
            return auth()->check()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        }

        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor($request));
        }

        return view('landing', [
            'plans' => $sales->publicPlans(),
            'workspaceUrl' => $this->workspaceUrlFor($request, false),
        ]);
    }

    public function workspaceFinder(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->away($this->workspaceUrlFor(request(), false));
        }

        return view('workspace-finder');
    }

    public function redirectToWorkspaceLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:100'],
        ]);

        $workspace = strtolower(trim((string) $data['workspace']));
        $workspace = preg_replace('/[^a-z0-9-]/', '', $workspace) ?: '';

        $tenant = Tenant::query()
            ->where('slug', $workspace)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return back()->withErrors([
                'workspace' => 'Workspace tidak ditemukan atau belum aktif.',
            ])->withInput();
        }

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');

        return redirect()->away($scheme . '://' . $tenant->slug . '.' . config('multitenancy.saas_domain') . '/login');
    }

    private function workspaceUrlFor(Request $request, bool $appendDashboard = true): string
    {
        $user = $request->user();
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');
        $path = $appendDashboard ? '/dashboard' : '/login';

        if ($user && (int) $user->tenant_id === 1 && $user->hasRole('Super-admin')) {
            return $scheme . '://' . config('multitenancy.platform_admin_subdomain', 'dash') . '.' . config('multitenancy.saas_domain') . $path;
        }

        if ($user && $user->tenant) {
            return $scheme . '://' . $user->tenant->slug . '.' . config('multitenancy.saas_domain') . $path;
        }

        return route('workspace.finder');
    }
}
