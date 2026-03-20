<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\ModuleManager;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function show(ModuleManager $modules, TenantPlanManager $planManager, string $section = 'general'): View
    {
        $tenantId = TenantContext::currentId();
        $tenant = TenantContext::currentTenant();
        $currentCompanyId = CompanyContext::currentId();
        $currentBranchId = BranchContext::currentId();

        $companies = Company::query()
            ->where('tenant_id', $tenantId)
            ->withCount([
                'branches',
                'branches as active_branches_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->with('company:id,name')
            ->when($currentCompanyId, fn ($query) => $query->where('company_id', $currentCompanyId))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->when(
                config('permission.teams'),
                fn ($query) => $query->where(config('permission.column_names.team_foreign_key', 'tenant_id'), $tenantId)
            )
            ->orderBy('name')
            ->get();

        $subscription = $planManager->currentSubscription($tenantId);
        $plan = $subscription ? $subscription->plan : null;
        $allModules = collect($modules->all());
        $activeModules = $allModules->where('installed', true)->where('active', true)->values();
        $installedModules = $allModules->where('installed', true)->values();
        $availableFeatures = collect(($plan && is_array($plan->features)) ? $plan->features : [])
            ->map(fn ($enabled, $key) => [
                'key' => $key,
                'enabled' => (bool) $enabled,
            ])
            ->values();

        $limitDefinitions = [
            ['key' => PlanLimit::COMPANIES, 'label' => 'Companies'],
            ['key' => PlanLimit::USERS, 'label' => 'Users'],
            ['key' => PlanLimit::PRODUCTS, 'label' => 'Products'],
            ['key' => PlanLimit::CONTACTS, 'label' => 'Contacts'],
            ['key' => PlanLimit::WHATSAPP_INSTANCES, 'label' => 'WhatsApp Instances'],
            ['key' => PlanLimit::EMAIL_CAMPAIGNS, 'label' => 'Email Campaigns'],
        ];

        $limitSummaries = collect($limitDefinitions)
            ->map(fn (array $definition) => [
                'label' => $definition['label'],
                'limit' => $planManager->limit($definition['key'], $tenantId),
                'usage' => $planManager->usage($definition['key'], $tenantId),
            ])
            ->values();

        return view('settings.index', [
            'currentSection' => $section,
            'sections' => $this->sections(),
            'tenant' => $tenant,
            'currentCompany' => CompanyContext::currentCompany(),
            'currentBranch' => BranchContext::currentBranch(),
            'companies' => $companies,
            'branches' => $branches,
            'users' => $users,
            'roles' => $roles,
            'subscription' => $subscription,
            'plan' => $plan,
            'availableFeatures' => $availableFeatures,
            'limitSummaries' => $limitSummaries,
            'allModules' => $allModules,
            'activeModules' => $activeModules,
            'installedModules' => $installedModules,
            'settingsStats' => $this->stats($companies, $branches, $users, $activeModules, $currentCompanyId, $currentBranchId),
        ]);
    }

    private function sections(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'route' => 'settings.general',
                'icon' => 'ti ti-settings',
                'description' => 'Profil workspace, konteks aktif, dan ringkasan tenant.',
            ],
            'company' => [
                'label' => 'Company',
                'route' => 'settings.company',
                'icon' => 'ti ti-building',
                'description' => 'Entitas bisnis internal di bawah tenant.',
            ],
            'branch' => [
                'label' => 'Branch',
                'route' => 'settings.branch',
                'icon' => 'ti ti-building-store',
                'description' => 'Outlet atau lokasi operasional di bawah company aktif.',
            ],
            'documents' => [
                'label' => 'Documents',
                'route' => 'settings.documents',
                'icon' => 'ti ti-file-description',
                'description' => 'Arah pengaturan invoice, receipt, dan numbering.',
            ],
            'subscription' => [
                'label' => 'Subscription',
                'route' => 'settings.subscription',
                'icon' => 'ti ti-credit-card',
                'description' => 'Plan aktif, fitur, dan quota tenant.',
            ],
            'access' => [
                'label' => 'Users & Access',
                'route' => 'settings.access',
                'icon' => 'ti ti-shield-lock',
                'description' => 'User tenant dan role yang sedang aktif.',
            ],
            'modules' => [
                'label' => 'Modules',
                'route' => 'settings.modules',
                'icon' => 'ti ti-layout-grid',
                'description' => 'Ringkasan module aktif dan arah entitlement tenant.',
            ],
        ];
    }

    private function stats(
        Collection $companies,
        Collection $branches,
        Collection $users,
        Collection $activeModules,
        ?int $currentCompanyId,
        ?int $currentBranchId
    ): array {
        return [
            [
                'label' => 'Companies',
                'value' => $companies->count(),
                'meta' => $currentCompanyId ? 'Active company selected' : 'No active company',
            ],
            [
                'label' => 'Branches',
                'value' => $branches->count(),
                'meta' => $currentBranchId ? 'Active branch selected' : 'Branch scope optional',
            ],
            [
                'label' => 'Users',
                'value' => $users->count(),
                'meta' => 'Tenant-scoped accounts',
            ],
            [
                'label' => 'Active Modules',
                'value' => $activeModules->count(),
                'meta' => 'Installed and active',
            ],
        ];
    }
}
