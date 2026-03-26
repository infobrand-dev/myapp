<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Actions\ApproveStockTransferAction;
use App\Modules\Inventory\Actions\CreateOpeningStockAction;
use App\Modules\Inventory\Actions\CreateStockAdjustmentAction;
use App\Modules\Inventory\Actions\CreateStockOpnameAction;
use App\Modules\Inventory\Actions\CreateStockTransferAction;
use App\Modules\Inventory\Actions\FinalizeStockOpnameAction;
use App\Modules\Inventory\Actions\FinalizeStockAdjustmentAction;
use App\Modules\Inventory\Actions\ReceiveStockTransferAction;
use App\Modules\Inventory\Actions\SendStockTransferAction;
use App\Modules\Inventory\Actions\UpdateStockOpnameAction;
use App\Modules\Inventory\Repositories\InventoryDashboardRepository;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Inventory\Services\InventoryDashboardService;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InventoryServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'inventory.view-stock',
        'inventory.view-movement',
        'inventory.manage-opening-stock',
        'inventory.manage-stock-adjustment',
        'inventory.finalize-stock-adjustment',
        'inventory.manage-stock-opname',
        'inventory.finalize-stock-opname',
        'inventory.manage-stock-transfer',
        'inventory.approve-stock-transfer',
        'inventory.view-all-locations',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'inventory.view-stock',
            'inventory.view-movement',
            'inventory.manage-opening-stock',
            'inventory.manage-stock-adjustment',
            'inventory.finalize-stock-adjustment',
            'inventory.manage-stock-opname',
            'inventory.finalize-stock-opname',
            'inventory.manage-stock-transfer',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(InventoryDashboardRepository::class);
        $this->app->singleton(StockRepository::class);
        $this->app->singleton(StockMovementRepository::class);
        $this->app->singleton(StockMutationService::class);
        $this->app->singleton(InventoryDashboardService::class);
        $this->app->singleton(CreateOpeningStockAction::class);
        $this->app->singleton(CreateStockAdjustmentAction::class);
        $this->app->singleton(CreateStockOpnameAction::class);
        $this->app->singleton(FinalizeStockAdjustmentAction::class);
        $this->app->singleton(UpdateStockOpnameAction::class);
        $this->app->singleton(FinalizeStockOpnameAction::class);
        $this->app->singleton(CreateStockTransferAction::class);
        $this->app->singleton(ApproveStockTransferAction::class);
        $this->app->singleton(SendStockTransferAction::class);
        $this->app->singleton(ReceiveStockTransferAction::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'inventory');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'inventory');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
