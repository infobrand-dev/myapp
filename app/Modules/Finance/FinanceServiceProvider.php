<?php

namespace App\Modules\Finance;

use App\Modules\Finance\Models\FinanceTransaction;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FinanceServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'finance.view',
        'finance.create',
        'finance.manage-categories',
        'finance.manage-coa',
        'finance.manage-tax',
        'finance.view-journal',
        'finance.manage-journal',
        'finance.manage-reconciliation',
        'finance.manage-period-locks',
        'finance.approve-sensitive-transactions',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Finance Staff' => self::PERMISSIONS,
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'finance');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'finance');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));

        $this->ensurePermissions();
        $this->registerDashboardHooks();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = false;

        foreach (self::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(TenantRoleProvisioner::class)->ensureForAllTenants();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function registerDashboardHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('dashboard.overview.cards', 'finance.dashboard.card', function (): string {
            $user = auth()->user();
            $canView = $user && ($user->hasAnyRole(['Super-admin', 'Admin']) || $user->can('finance.view'));

            if (!$user
                || !Schema::hasTable('finance_transactions')
                || !$canView
                || !app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::ACCOUNTING, TenantContext::currentId())) {
                return '';
            }

            $baseQuery = FinanceTransaction::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query));

            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            $cashIn = (float) ((clone $baseQuery)
                ->where('transaction_type', FinanceTransaction::TYPE_CASH_IN)
                ->whereNull('transfer_group_key')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount'));

            $cashOut = (float) ((clone $baseQuery)
                ->whereIn('transaction_type', [FinanceTransaction::TYPE_CASH_OUT, FinanceTransaction::TYPE_EXPENSE])
                ->whereNull('transfer_group_key')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount'));

            $metrics = [
                'entry_count' => (clone $baseQuery)
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->count(),
                'cash_in_month' => $cashIn,
                'cash_out_month' => $cashOut,
                'net_month' => $cashIn - $cashOut,
            ];

            return view('finance::dashboard.card', compact('metrics'))->render();
        });
    }
}
