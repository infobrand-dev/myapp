<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Company;
use App\Support\HookManager;
use App\Support\BranchContext;
use App\Support\CorePermissions;
use App\Support\CurrencySettingsResolver;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\TenantRoleProvisioner;
use App\Support\MoneyFormatter;
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
        $this->app->singleton(CurrencySettingsResolver::class);
        $this->app->singleton(MoneyFormatter::class);
        $this->app->singleton(TenantPlanManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->shouldSkipDatabaseBootstrap()) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->bootstrapPermissionTeamId());

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

        if ($this->app->runningInConsole()) {
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
    }

    private function schemaHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function schemaHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function shouldSkipDatabaseBootstrap(): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }

        try {
            $request = $this->app->make('request');

            return $request->is('install') || $request->is('install/*');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function bootstrapPermissionTeamId(): ?int
    {
        if ($this->app->runningInConsole()) {
            return 1;
        }

        try {
            $request = $this->app->make('request');

            $tenantId = $request->attributes->get('tenant_id');
            if ($tenantId) {
                return (int) $tenantId;
            }

            $userTenantId = optional(Auth::user())->tenant_id;
            if ($userTenantId) {
                return (int) $userTenantId;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
