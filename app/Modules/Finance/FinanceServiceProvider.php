<?php

namespace App\Modules\Finance;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FinanceServiceProvider extends ServiceProvider
{
    public const PERMISSIONS = [
        'finance.view',
        'finance.create',
        'finance.manage-categories',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'finance');
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
