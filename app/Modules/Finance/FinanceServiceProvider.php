<?php

namespace App\Modules\Finance;

use App\Support\RegistersModuleRoutes;
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
