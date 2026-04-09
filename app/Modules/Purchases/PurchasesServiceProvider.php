<?php

namespace App\Modules\Purchases;

use App\Modules\Purchases\Actions\CancelDraftPurchaseAction;
use App\Modules\Purchases\Actions\CreateDraftPurchaseAction;
use App\Modules\Purchases\Actions\FinalizePurchaseAction;
use App\Modules\Purchases\Actions\RecalculatePurchaseTotalsAction;
use App\Modules\Purchases\Actions\ReceivePurchaseGoodsAction;
use App\Modules\Purchases\Actions\SyncPurchasePaymentSummaryAction;
use App\Modules\Purchases\Actions\UpdateDraftPurchaseAction;
use App\Modules\Purchases\Actions\VoidPurchaseAction;
use App\Modules\Purchases\Events\PurchaseFinalized;
use App\Modules\Purchases\Events\PurchaseVoided;
use App\Modules\Purchases\Listeners\DispatchFinalizedPurchaseHooks;
use App\Modules\Purchases\Listeners\DispatchVoidedPurchaseHooks;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Repositories\PurchaseRepository;
use App\Modules\Purchases\Services\PurchaseIntegrationPayloadBuilder;
use App\Modules\Purchases\Services\PurchaseLookupService;
use App\Modules\Purchases\Services\PurchaseNumberService;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\HookManager;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PurchasesServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'purchases.view',
        'purchases.create',
        'purchases.edit_draft',
        'purchases.finalize',
        'purchases.receive',
        'purchases.void',
        'purchases.print',
        'purchases.view_all',
        'purchases.view_own',
        'purchases.export',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'purchases.view',
            'purchases.create',
            'purchases.edit_draft',
            'purchases.finalize',
            'purchases.receive',
            'purchases.print',
            'purchases.view_all',
            'purchases.export',
        ],
        'Inventory Staff' => [
            'purchases.view',
            'purchases.create',
            'purchases.edit_draft',
            'purchases.finalize',
            'purchases.receive',
            'purchases.print',
            'purchases.view_own',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(PurchaseRepository::class);
        $this->app->singleton(PurchaseNumberService::class);
        $this->app->singleton(PurchaseSnapshotService::class);
        $this->app->singleton(PurchaseLookupService::class);
        $this->app->singleton(PurchaseIntegrationPayloadBuilder::class);
        $this->app->singleton(RecalculatePurchaseTotalsAction::class);
        $this->app->singleton(SyncPurchasePaymentSummaryAction::class);
        $this->app->singleton(CreateDraftPurchaseAction::class);
        $this->app->singleton(UpdateDraftPurchaseAction::class);
        $this->app->singleton(FinalizePurchaseAction::class);
        $this->app->singleton(ReceivePurchaseGoodsAction::class);
        $this->app->singleton(CancelDraftPurchaseAction::class);
        $this->app->singleton(VoidPurchaseAction::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'purchases');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'purchases');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));

        Event::listen(PurchaseFinalized::class, DispatchFinalizedPurchaseHooks::class);
        Event::listen(PurchaseVoided::class, DispatchVoidedPurchaseHooks::class);

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

        $hooks->register('dashboard.overview.cards', 'purchases.dashboard.card', function (): string {
            $planManager = app(\App\Support\TenantPlanManager::class);
            $user = auth()->user();
            $canView = $user && ($user->hasAnyRole(['Super-admin', 'Admin']) || $user->can('purchases.view'));

            if (!$user
                || !Schema::hasTable('purchases')
                || !$canView
                || !$planManager->hasFeature(PlanFeature::ACCOUNTING, TenantContext::currentId())
                || !$planManager->hasFeature(PlanFeature::PURCHASES, TenantContext::currentId())) {
                return '';
            }

            $baseQuery = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query));

            $liveStatuses = [
                Purchase::STATUS_CONFIRMED,
                Purchase::STATUS_PARTIAL_RECEIVED,
                Purchase::STATUS_RECEIVED,
            ];

            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            $metrics = [
                'open_count' => (clone $baseQuery)
                    ->whereIn('status', [Purchase::STATUS_CONFIRMED, Purchase::STATUS_PARTIAL_RECEIVED])
                    ->count(),
                'spend_month' => (float) ((clone $baseQuery)
                    ->whereIn('status', $liveStatuses)
                    ->whereBetween('purchase_date', [$monthStart, $monthEnd])
                    ->sum('grand_total')),
                'payable_count' => (clone $baseQuery)
                    ->whereIn('status', $liveStatuses)
                    ->whereIn('payment_status', [Purchase::PAYMENT_UNPAID, Purchase::PAYMENT_PARTIAL])
                    ->count(),
            ];

            return view('purchases::dashboard.card', compact('metrics'))->render();
        });
    }
}
