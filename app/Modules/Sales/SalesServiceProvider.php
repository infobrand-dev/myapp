<?php

namespace App\Modules\Sales;

use App\Modules\Sales\Actions\CancelDraftSaleAction;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\RecalculateSaleTotalsAction;
use App\Modules\Sales\Actions\RecordSalePaymentAction;
use App\Modules\Sales\Actions\SyncSalePaymentSummaryAction;
use App\Modules\Sales\Actions\UpdateDraftSaleAction;
use App\Modules\Sales\Actions\VoidSalePaymentAction;
use App\Modules\Sales\Actions\VoidSaleAction;
use App\Modules\Sales\Events\SaleFinalized;
use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Listeners\DispatchFinalizedSaleHooks;
use App\Modules\Sales\Listeners\DispatchVoidedSaleHooks;
use App\Modules\Sales\Repositories\SaleRepository;
use App\Modules\Sales\Services\SaleIntegrationPayloadBuilder;
use App\Modules\Sales\Services\SaleLookupService;
use App\Modules\Sales\Services\SaleNumberService;
use App\Modules\Sales\Services\SalePaymentSummaryService;
use App\Modules\Sales\Services\SaleSnapshotService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SalesServiceProvider extends ServiceProvider
{
    public const PERMISSIONS = [
        'sales.view',
        'sales.create',
        'sales.update-draft',
        'sales.finalize',
        'sales.cancel-draft',
        'sales.void',
        'sales.print',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'sales.view',
            'sales.create',
            'sales.update-draft',
            'sales.finalize',
            'sales.cancel-draft',
            'sales.print',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(SaleRepository::class);
        $this->app->singleton(SaleLookupService::class);
        $this->app->singleton(SaleIntegrationPayloadBuilder::class);
        $this->app->singleton(SaleNumberService::class);
        $this->app->singleton(SalePaymentSummaryService::class);
        $this->app->singleton(SaleSnapshotService::class);
        $this->app->singleton(RecalculateSaleTotalsAction::class);
        $this->app->singleton(SyncSalePaymentSummaryAction::class);
        $this->app->singleton(RecordSalePaymentAction::class);
        $this->app->singleton(CreateDraftSaleAction::class);
        $this->app->singleton(UpdateDraftSaleAction::class);
        $this->app->singleton(FinalizeSaleAction::class);
        $this->app->singleton(VoidSaleAction::class);
        $this->app->singleton(VoidSalePaymentAction::class);
        $this->app->singleton(CancelDraftSaleAction::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sales');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        Event::listen(SaleFinalized::class, DispatchFinalizedSaleHooks::class);
        Event::listen(SaleVoided::class, DispatchVoidedSaleHooks::class);

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
