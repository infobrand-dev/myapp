<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Actions\RecalculatePaymentSummaryAction;
use App\Modules\Payments\Actions\ValidatePayableTransactionAction;
use App\Modules\Payments\Actions\VoidPaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Policies\PaymentPolicy;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Payments\Services\PaymentLookupService;
use App\Modules\Payments\Services\PaymentNumberService;
use App\Modules\Payments\Services\PaymentSummaryService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PaymentsServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'payments.view',
        'payments.view_all',
        'payments.view_own',
        'payments.create',
        'payments.assign_receiver',
        'payments.void',
        'payments.manage_methods',
        'payments.print',
        'payments.export',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'payments.view',
            'payments.view_own',
            'payments.create',
            'payments.void',
            'payments.print',
        ],
        'Cashier' => [
            'payments.view',
            'payments.view_own',
            'payments.create',
            'payments.print',
        ],
        'Finance Staff' => self::PERMISSIONS,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentRepository::class);
        $this->app->singleton(PaymentLookupService::class);
        $this->app->singleton(PaymentNumberService::class);
        $this->app->singleton(PaymentSummaryService::class);
        $this->app->singleton(ValidatePayableTransactionAction::class);
        $this->app->singleton(RecalculatePaymentSummaryAction::class);
        $this->app->singleton(CreatePaymentAction::class);
        $this->app->singleton(VoidPaymentAction::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'payments');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'payments');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));

        Gate::policy(Payment::class, PaymentPolicy::class);
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

        $hooks->register('dashboard.overview.cards', 'payments.dashboard.card', function (): string {
            $user = auth()->user();
            $canView = $user && ($user->hasAnyRole(['Super-admin', 'Admin']) || $user->can('payments.view'));

            if (!$user
                || !Schema::hasTable('payments')
                || !$canView
                || !app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::COMMERCE, TenantContext::currentId())) {
                return '';
            }

            $baseQuery = Payment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query));

            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            $metrics = [
                'posted_count' => (clone $baseQuery)
                    ->where('status', Payment::STATUS_POSTED)
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->count(),
                'collected_month' => (float) ((clone $baseQuery)
                    ->where('status', Payment::STATUS_POSTED)
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('amount')),
                'pending_count' => (clone $baseQuery)
                    ->where('status', Payment::STATUS_PENDING)
                    ->count(),
            ];

            return view('payments::dashboard.card', compact('metrics'))->render();
        });
    }
}
