<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AiCreditPricingService;
use App\Services\AiUsageService;
use App\Support\ModuleManager;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(ModuleManager $modules, TenantPlanManager $plans, AiUsageService $aiUsage, AiCreditPricingService $aiPricing): View|RedirectResponse
    {
        if (request()->attributes->get('platform_admin_host')) {
            return redirect()->route('platform.dashboard');
        }

        $user = auth()->user();

        if ($redirect = $this->redirectToExpectedHost()) {
            return $redirect;
        }

        $isPrivileged = $user->hasAnyRole(['Super-admin', 'Admin']);
        $allModules = collect($modules->all());
        $tenantId = TenantContext::currentId();
        $visibleModules = $allModules->filter(function ($module) use ($plans, $tenantId) {
            $requirement = PlanFeature::moduleFeatureRequirement((string) ($module['slug'] ?? ''));
            $all = (array) ($requirement['all'] ?? []);
            $any = (array) ($requirement['any'] ?? []);

            if ($all !== []) {
                return collect($all)->every(fn (string $feature) => $plans->hasFeature($feature, $tenantId));
            }

            if ($any !== []) {
                return collect($any)->contains(fn (string $feature) => $plans->hasFeature($feature, $tenantId));
            }

            return true;
        });
        $activeModules = $visibleModules->filter(fn ($module) => $module['installed'] && $module['active']);
        $installedModules = $visibleModules->filter(fn ($module) => $module['installed']);


        $recentUsers = $isPrivileged
            ? User::query()
                ->where('tenant_id', TenantContext::currentId())
                ->latest()
                ->limit(6)
                ->get(['id', 'name', 'email', 'created_at', 'avatar'])
            : collect([$user]);

        $moduleHighlights = $activeModules
            ->map(function ($module) {
                $firstNav = collect($module['navigation'] ?? [])
                    ->first(fn ($item) => !empty($item['route']));

                return [
                    'name'        => $module['name'],
                    'description' => $module['description'] ?: 'Modul aktif',
                    'icon'        => $module['icon'] ?? null,
                    'route'       => $firstNav['route'] ?? null,
                ];
            })
            ->take(6)
            ->values();

        $aiCredits = $aiUsage->summary($tenantId);
        $aiState = $plans->usageState(PlanLimit::AI_CREDITS_MONTHLY, $tenantId);
        $aiAdvice = $plans->limitActionAdvice(PlanLimit::AI_CREDITS_MONTHLY, $aiState['status'], $tenantId);

        return view('dashboard', [
            'isPrivileged' => $isPrivileged,
            'recentUsers' => $recentUsers,
            'moduleHighlights' => $moduleHighlights,
            'activeModules' => $activeModules,
            'totalModules' => $visibleModules->count(),
            'aiCredits' => $aiCredits + [
                'enabled' => $plans->hasFeature(PlanFeature::CHATBOT_AI, $tenantId),
                'status' => $aiState['status'],
                'advice' => $aiAdvice,
            ],
            'aiCreditPricing' => $aiPricing->snapshot(),
        ]);
    }

    private function redirectToExpectedHost(): ?RedirectResponse
    {
        if (config('multitenancy.mode') !== 'saas') {
            return null;
        }

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $target = $this->workspaceUrlFor(request());
        $targetHost = parse_url($target, PHP_URL_HOST);
        $currentHost = request()->getHost();

        if (!$targetHost || $targetHost === $currentHost) {
            return null;
        }

        return redirect()->away($target);
    }

    private function workspaceUrlFor($request, bool $appendDashboard = true): string
    {
        $user = $request->user();
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');
        $path = $appendDashboard ? '/dashboard' : '/login';

        if ($user && (int) $user->tenant_id === 1 && $user->hasRole('Super-admin')) {
            return $scheme . '://' . config('multitenancy.platform_admin_subdomain', 'dash') . '.' . config('multitenancy.saas_domain') . '/platform';
        }

        if ($user && $user->tenant) {
            return $scheme . '://' . $user->tenant->slug . '.' . config('multitenancy.saas_domain') . $path;
        }

        return route('workspace.finder');
    }
}
