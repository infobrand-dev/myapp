<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Actions\ApproveStockTransferAction;
use App\Modules\Inventory\Actions\CreateOpeningStockAction;
use App\Modules\Inventory\Actions\CreateStockAdjustmentAction;
use App\Modules\Inventory\Actions\CreateStockTransferAction;
use App\Modules\Inventory\Actions\ReceiveStockTransferAction;
use App\Modules\Inventory\Actions\SendStockTransferAction;
use App\Modules\Inventory\Repositories\InventoryDashboardRepository;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Inventory\Services\InventoryDashboardService;
use App\Modules\Inventory\Services\StockMutationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class InventoryServiceProvider extends ServiceProvider
{
    public const PERMISSIONS = [
        'inventory.view-stock',
        'inventory.view-movement',
        'inventory.manage-opening-stock',
        'inventory.manage-stock-adjustment',
        'inventory.manage-stock-transfer',
        'inventory.approve-stock-transfer',
        'inventory.view-all-locations',
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
        $this->app->singleton(CreateStockTransferAction::class);
        $this->app->singleton(ApproveStockTransferAction::class);
        $this->app->singleton(SendStockTransferAction::class);
        $this->app->singleton(ReceiveStockTransferAction::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'inventory');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

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
