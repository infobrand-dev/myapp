<?php

namespace App\Modules\Sales;

use App\Modules\Sales\Actions\CancelDraftSaleAction;
use App\Modules\Sales\Actions\CancelDraftReturnAction;
use App\Modules\Sales\Actions\CalculateReturnTotalsAction;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\CreateSalesReturnAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\FinalizeSalesReturnAction;
use App\Modules\Sales\Actions\IntegrateRefundToPaymentsAction;
use App\Modules\Sales\Actions\IntegrateReturnToInventoryAction;
use App\Modules\Sales\Actions\RecalculateSaleTotalsAction;
use App\Modules\Sales\Actions\RecordSalePaymentAction;
use App\Modules\Sales\Actions\SyncSalePaymentSummaryAction;
use App\Modules\Sales\Actions\SyncSaleReturnRefundSummaryAction;
use App\Modules\Sales\Actions\UpdateDraftSaleAction;
use App\Modules\Sales\Actions\ValidateReturnableItemsAction;
use App\Modules\Sales\Actions\VoidSaleAction;
use App\Modules\Sales\Events\SaleFinalized;
use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Listeners\DispatchFinalizedSaleHooks;
use App\Modules\Sales\Listeners\DispatchVoidedSaleHooks;
use App\Modules\Sales\Repositories\SaleRepository;
use App\Modules\Sales\Repositories\SaleReturnRepository;
use App\Modules\Sales\Services\SaleIntegrationPayloadBuilder;
use App\Modules\Sales\Services\SaleIdempotencyService;
use App\Modules\Sales\Services\SaleLookupService;
use App\Modules\Sales\Services\SaleNumberService;
use App\Modules\Sales\Services\SalePaymentSummaryService;
use App\Modules\Sales\Services\SaleReturnCalculationService;
use App\Modules\Sales\Services\SaleReturnLookupService;
use App\Modules\Sales\Services\SaleReturnNumberService;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SalesServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'sales.view',
        'sales.create',
        'sales.update-draft',
        'sales.finalize',
        'sales.cancel-draft',
        'sales.void',
        'sales.print',
        'sales_return.view',
        'sales_return.create',
        'sales_return.finalize',
        'sales_return.cancel_draft',
        'sales_return.print',
        'sales_return.view_all',
        'sales_return.view_own',
        'sales_return.process_refund',
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
            'sales_return.view',
            'sales_return.create',
            'sales_return.finalize',
            'sales_return.cancel_draft',
            'sales_return.print',
            'sales_return.view_all',
            'sales_return.process_refund',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(SaleRepository::class);
        $this->app->singleton(SaleReturnRepository::class);
        $this->app->singleton(SaleLookupService::class);
        $this->app->singleton(SaleReturnLookupService::class);
        $this->app->singleton(SaleIntegrationPayloadBuilder::class);
        $this->app->singleton(SaleIdempotencyService::class);
        $this->app->singleton(SaleNumberService::class);
        $this->app->singleton(SaleReturnNumberService::class);
        $this->app->singleton(SalePaymentSummaryService::class);
        $this->app->singleton(SaleReturnCalculationService::class);
        $this->app->singleton(SaleSnapshotService::class);
        $this->app->singleton(RecalculateSaleTotalsAction::class);
        $this->app->singleton(SyncSalePaymentSummaryAction::class);
        $this->app->singleton(SyncSaleReturnRefundSummaryAction::class);
        $this->app->singleton(RecordSalePaymentAction::class);
        $this->app->singleton(ValidateReturnableItemsAction::class);
        $this->app->singleton(CalculateReturnTotalsAction::class);
        $this->app->singleton(CreateDraftSaleAction::class);
        $this->app->singleton(UpdateDraftSaleAction::class);
        $this->app->singleton(FinalizeSaleAction::class);
        $this->app->singleton(VoidSaleAction::class);
        $this->app->singleton(CancelDraftSaleAction::class);
        $this->app->singleton(IntegrateReturnToInventoryAction::class);
        $this->app->singleton(IntegrateRefundToPaymentsAction::class);
        $this->app->singleton(CreateSalesReturnAction::class);
        $this->app->singleton(FinalizeSalesReturnAction::class);
        $this->app->singleton(CancelDraftReturnAction::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sales');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'sales');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        Event::listen(SaleFinalized::class, DispatchFinalizedSaleHooks::class);
        Event::listen(SaleVoided::class, DispatchVoidedSaleHooks::class);

        $this->ensurePermissions();
    }

    private function registerRoutes(): void
    {
        $this->registerModuleRoutes([
            __DIR__ . '/routes/web.php',
            __DIR__ . '/routes/api.php',
        ]);
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
