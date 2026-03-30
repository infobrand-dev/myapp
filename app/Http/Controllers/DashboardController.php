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
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(ModuleManager $modules, TenantPlanManager $plans, AiUsageService $aiUsage, AiCreditPricingService $aiPricing)
    {
        if (request()->attributes->get('platform_admin_host')) {
            return redirect()->route('platform.dashboard');
        }

        $user = auth()->user();
        $isPrivileged = $user->hasAnyRole(['Super-admin', 'Admin']);
        $allModules = collect($modules->all());
        $tenantId = TenantContext::currentId();
        $visibleModules = $allModules->filter(function ($module) use ($plans, $tenantId) {
            $feature = PlanFeature::moduleFeatureForSlug((string) ($module['slug'] ?? ''));

            return $feature ? $plans->hasFeature($feature, $tenantId) : true;
        });
        $activeModules = $visibleModules->filter(fn ($module) => $module['installed'] && $module['active']);
        $installedModules = $visibleModules->filter(fn ($module) => $module['installed']);

        $stats = $isPrivileged
            ? [
                [
                    'label' => 'Active Modules',
                    'value' => $activeModules->count(),
                    'meta' => $visibleModules->count() . ' available',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Installed Modules',
                    'value' => $installedModules->count(),
                    'meta' => max($visibleModules->count() - $installedModules->count(), 0) . ' pending',
                    'tone' => 'azure',
                ],
                [
                    'label' => 'Users',
                    'value' => User::query()->where('tenant_id', TenantContext::currentId())->count(),
                    'meta' => User::query()->where('tenant_id', TenantContext::currentId())->whereDate('created_at', today())->count() . ' joined today',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Roles',
                    'value' => Role::query()->where('tenant_id', TenantContext::currentId())->where('guard_name', 'web')->count(),
                    'meta' => $user->getRoleNames()->join(', ') ?: 'No role',
                    'tone' => 'orange',
                ],
            ]
            : [
                [
                    'label' => 'Active Modules',
                    'value' => $activeModules->count(),
                    'meta' => 'Workspace features currently available',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Your Roles',
                    'value' => $user->getRoleNames()->count(),
                    'meta' => $user->getRoleNames()->join(', ') ?: 'No role assigned',
                    'tone' => 'azure',
                ],
                [
                    'label' => 'Member Since',
                    'value' => optional($user->created_at)->diffInDays(now()) ?? 0,
                    'meta' => optional($user->created_at)->format('d M Y') ?: 'Unknown',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Email Status',
                    'value' => $user->email_verified_at ? 'OK' : 'Pending',
                    'meta' => $user->email_verified_at ? 'Email verified' : 'Verification still pending',
                    'tone' => $user->email_verified_at ? 'orange' : 'red',
                ],
            ];

        $recentUsers = $isPrivileged
            ? User::query()
                ->where('tenant_id', TenantContext::currentId())
                ->latest()
                ->limit(6)
                ->get(['id', 'name', 'email', 'created_at', 'avatar'])
            : collect([$user]);

        $moduleHighlights = $activeModules
            ->map(fn ($module) => [
                'name' => $module['name'],
                'items' => count($module['navigation'] ?? []),
                'description' => $module['description'] ?: 'Module active',
            ])
            ->take(5)
            ->values();

        $aiCredits = $aiUsage->summary($tenantId);
        $aiState = $plans->usageState(PlanLimit::AI_CREDITS_MONTHLY, $tenantId);
        $aiAdvice = $plans->limitActionAdvice(PlanLimit::AI_CREDITS_MONTHLY, $aiState['status'], $tenantId);

        return view('dashboard', [
            'isPrivileged' => $isPrivileged,
            'stats' => $stats,
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
}
