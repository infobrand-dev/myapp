<?php

namespace App\Modules\Fulfillment;

use App\Support\RegistersModuleRoutes;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FulfillmentServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'fulfillment.view',
        'fulfillment.manage',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Sales' => ['fulfillment.view'],
        'Inventory Staff' => self::PERMISSIONS,
    ];

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'fulfillment');
        $this->ensurePermissions();
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
}
