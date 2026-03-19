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
use App\Modules\Purchases\Repositories\PurchaseRepository;
use App\Modules\Purchases\Services\PurchaseIntegrationPayloadBuilder;
use App\Modules\Purchases\Services\PurchaseLookupService;
use App\Modules\Purchases\Services\PurchaseNumberService;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PurchasesServiceProvider extends ServiceProvider
{
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
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'purchases');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        Event::listen(PurchaseFinalized::class, DispatchFinalizedPurchaseHooks::class);
        Event::listen(PurchaseVoided::class, DispatchVoidedPurchaseHooks::class);

        $this->ensurePermissions();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        if (Schema::hasTable('roles')) {
            foreach (self::DEFAULT_ROLE_PERMISSIONS as $roleName => $permissions) {
                $role = Role::query()->firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);

                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
