<?php

namespace App\Modules\PointOfSale;

use App\Modules\PointOfSale\Actions\ResolveBarcodeToSellableAction;
use App\Modules\PointOfSale\Services\PosCartService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PointOfSaleServiceProvider extends ServiceProvider
{
    public const PERMISSIONS = [
        'pos.use',
        'pos.hold-cart',
        'pos.resume-cart',
        'pos.checkout',
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
            'pos.print-receipt',
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(ResolveBarcodeToSellableAction::class);
        $this->app->singleton(PosCartService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'pos');
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
