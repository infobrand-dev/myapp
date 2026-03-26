<?php

namespace App\Modules\PointOfSale;

use App\Modules\PointOfSale\Actions\ResolveBarcodeToSellableAction;
use App\Modules\PointOfSale\Services\PosCartService;
use App\Modules\PointOfSale\Services\PosCashSessionService;
use App\Modules\PointOfSale\Services\PosCheckoutOrchestrator;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PointOfSaleServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'pos.use',
        'pos.hold-cart',
        'pos.resume-cart',
        'pos.checkout',
        'pos.open-shift',
        'pos.close-shift',
        'pos.view-shift',
        'pos.record-cash-movement',
        'pos.manage-all-shifts',
        'pos.print-receipt',
        'pos.reprint-receipt',
        'pos.override-price',
        'pos.override-discount',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [
            'pos.use',
            'pos.hold-cart',
            'pos.resume-cart',
            'pos.checkout',
            'pos.open-shift',
            'pos.close-shift',
            'pos.view-shift',
            'pos.record-cash-movement',
            'pos.manage-all-shifts',
            'pos.print-receipt',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(ResolveBarcodeToSellableAction::class);
        $this->app->singleton(PosCartService::class);
        $this->app->singleton(PosCashSessionService::class);
        $this->app->singleton(PosCheckoutOrchestrator::class);
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'pos');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'pos');
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
