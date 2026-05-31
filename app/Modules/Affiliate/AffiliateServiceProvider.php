<?php

namespace App\Modules\Affiliate;

use App\Modules\Affiliate\Services\TenantAffiliateConversionService;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AffiliateServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'affiliate.view',
        'affiliate.manage',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Sales' => ['affiliate.view'],
        'Customer Service' => ['affiliate.view'],
    ];

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'affiliate');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->ensurePermissions();
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        app(HookManager::class)->register('payments.posted', 'affiliate.tenant-conversion', function (array $context) {
            return app(TenantAffiliateConversionService::class)->handle(
                $context['payment'] ?? null,
                $context['payables'] ?? collect()
            );
        });
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
