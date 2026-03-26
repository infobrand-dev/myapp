<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Company;
use App\Support\HookManager;
use App\Support\BranchContext;
use App\Support\CorePermissions;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\TenantRoleProvisioner;
use App\Modules\LiveChat\Support\LiveChatRealtimeState;
use App\Support\ModuleIconRegistry;
use App\Support\ModuleManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager());
        $this->app->singleton(ModuleIconRegistry::class, fn () => new ModuleIconRegistry());
        $this->app->singleton(HookManager::class, fn () => new HookManager());
        $this->app->singleton(LiveChatRealtimeState::class, fn () => new LiveChatRealtimeState());
        $this->app->singleton(TenantPlanManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(TenantContext::currentId());

        View::composer('layouts.admin', function ($view): void {
            $tenant = TenantContext::currentTenant();
            $currentCompany = CompanyContext::currentCompany();
            $currentBranch = BranchContext::currentBranch();
            $companies = collect();
            $branches = collect();
            $user = Auth::user();
            $userAccessManager = app(\App\Support\UserAccessManager::class);
            $allowedCompanyIds = $userAccessManager->companyIdsFor($user);
            $allowedBranchIds = $userAccessManager->branchIdsFor($user, optional($currentCompany)->id);

            if (Auth::check() && $this->schemaHasTable('companies')) {
                $companies = Company::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->when($allowedCompanyIds, fn ($query) => $query->whereIn('id', $allowedCompanyIds->all()))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'code']);
            }

            if (Auth::check() && $this->schemaHasTable('branches') && $currentCompany) {
                $branches = Branch::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', $currentCompany->id)
                    ->when($allowedBranchIds, fn ($query) => $query->whereIn('id', $allowedBranchIds->all()))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'company_id', 'name', 'slug', 'code']);
            }

            $view->with([
                'topbarTenant' => $tenant,
                'topbarCurrentCompany' => $currentCompany,
                'topbarCurrentBranch' => $currentBranch,
                'topbarCompanies' => $companies,
                'topbarBranches' => $branches,
            ]);
        });

        if (!$this->schemaHasTable('permissions')) {
            return;
        }

        if (config('permission.teams') && (
            !$this->schemaHasTable('roles')
            || !$this->schemaHasColumn('roles', config('permission.column_names.team_foreign_key', 'tenant_id'))
        )) {
            return;
        }

        $created = false;
        foreach (CorePermissions::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(TenantRoleProvisioner::class)->ensureForAllTenants();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function schemaHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function schemaHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
