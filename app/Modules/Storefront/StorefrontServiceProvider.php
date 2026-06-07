<?php

namespace App\Modules\Storefront;

use App\Contracts\CommercePendingOrderExpirer;
use App\Contracts\PublicStorefrontResponder;
use App\Modules\Storefront\Adapters\StorefrontPendingOrderExpirer;
use App\Modules\Storefront\Adapters\StorefrontPublicRootResponder;
use App\Modules\Storefront\Services\StorefrontOrderSettlementService;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantRoleProvisioner;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class StorefrontServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'storefront.view',
        'storefront.manage',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Sales' => ['storefront.view', 'storefront.manage'],
        'Customer Service' => ['storefront.view'],
    ];

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'storefront');
        $this->ensurePermissions();
        $this->registerHooks();
    }

    public function register(): void
    {
        $this->app->bind(PublicStorefrontResponder::class, StorefrontPublicRootResponder::class);
        $this->app->bind(CommercePendingOrderExpirer::class, StorefrontPendingOrderExpirer::class);
    }

    private function registerHooks(): void
    {
        app(HookManager::class)->register('payments.posted', 'storefront.commerce-order-settlement', function (array $context) {
            return app(StorefrontOrderSettlementService::class)->handle(
                $context['payment'] ?? null,
                $context['payables'] ?? collect(),
                $context['allocations'] ?? collect()
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
