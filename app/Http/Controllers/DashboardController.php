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
use Spatie\Permission\Models\Role;

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
            $feature = PlanFeature::moduleFeatureForSlug((string) ($module['slug'] ?? ''));

            return $feature ? $plans->hasFeature($feature, $tenantId) : true;
        });
        $activeModules = $visibleModules->filter(fn ($module) => $module['installed'] && $module['active']);
        $installedModules = $visibleModules->filter(fn ($module) => $module['installed']);

        $totalUsers = User::query()->where('tenant_id', $tenantId)->count();
        $usersToday = User::query()->where('tenant_id', $tenantId)->whereDate('created_at', today())->count();
        $roleCount = Role::query()->where('tenant_id', $tenantId)->where('guard_name', 'web')->count();

        $stats = $isPrivileged
            ? [
                [
                    'label' => 'Modul Aktif',
                    'value' => $activeModules->count(),
                    'meta' => $visibleModules->count() . ' tersedia di paket',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Pengguna',
                    'value' => $totalUsers,
                    'meta' => $usersToday > 0 ? $usersToday . ' bergabung hari ini' : 'Tidak ada yang baru hari ini',
                    'tone' => 'azure',
                ],
                [
                    'label' => 'Role',
                    'value' => $roleCount,
                    'meta' => 'role tersedia di workspace',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Akun Anda',
                    'value' => $user->getRoleNames()->first() ?: '—',
                    'meta' => $user->email,
                    'tone' => 'orange',
                ],
            ]
            : [
                [
                    'label' => 'Fitur Aktif',
                    'value' => $activeModules->count(),
                    'meta' => 'fitur tersedia untuk Anda',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Role Anda',
                    'value' => $user->getRoleNames()->count(),
                    'meta' => $user->getRoleNames()->join(', ') ?: 'Belum ada role',
                    'tone' => 'azure',
                ],
                [
                    'label' => 'Bergabung',
                    'value' => optional($user->created_at)->diffInDays(now()) ?? 0,
                    'meta' => 'hari yang lalu, ' . (optional($user->created_at)->format('d M Y') ?: '—'),
                    'tone' => 'green',
                ],
                [
                    'label' => 'Email',
                    'value' => $user->email_verified_at ? 'Terverifikasi' : 'Belum',
                    'meta' => $user->email_verified_at ? 'Akun aktif dan aman' : 'Cek kotak masuk email Anda',
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
