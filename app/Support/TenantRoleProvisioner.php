<?php

namespace App\Support;

use App\Models\Tenant;
use App\Modules\Discounts\DiscountsServiceProvider;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\PointOfSale\PointOfSaleServiceProvider;
use App\Modules\Products\ProductsServiceProvider;
use App\Modules\Purchases\PurchasesServiceProvider;
use App\Modules\Reports\ReportsServiceProvider;
use App\Modules\Sales\SalesServiceProvider;
use App\Modules\SampleData\SampleDataServiceProvider;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleProvisioner
{
    /**
     * @var array<int, class-string>
     */
    private const ROLE_SOURCES = [
        ProductsServiceProvider::class,
        InventoryServiceProvider::class,
        DiscountsServiceProvider::class,
        FinanceServiceProvider::class,
        PaymentsServiceProvider::class,
        PointOfSaleServiceProvider::class,
        PurchasesServiceProvider::class,
        ReportsServiceProvider::class,
        SalesServiceProvider::class,
        SampleDataServiceProvider::class,
    ];

    public function ensureForTenant(?int $tenantId = null): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $tenantId ??= TenantContext::currentId();
        $registrar = app(PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();
        $availablePermissions = Permission::query()->pluck('name')->flip();

        $registrar->setPermissionsTeamId($tenantId);

        try {
            foreach ($this->defaultRolePermissions() as $roleName => $permissions) {
                $role = Role::findOrCreate($roleName, 'web');

                $role->syncPermissions(
                    collect($permissions)
                        ->filter(fn (string $permission) => $availablePermissions->has($permission))
                        ->unique()
                        ->values()
                        ->all()
                );
            }
        } finally {
            $registrar->setPermissionsTeamId($originalTeamId);
            $registrar->forgetCachedPermissions();
        }
    }

    public function ensureForAllTenants(): void
    {
        if (!Schema::hasTable('tenants')) {
            $this->ensureForTenant(1);

            return;
        }

        $tenantIds = Tenant::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');

        if ($tenantIds->isEmpty()) {
            $this->ensureForTenant(1);

            return;
        }

        foreach ($tenantIds as $tenantId) {
            $this->ensureForTenant((int) $tenantId);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function defaultRolePermissions(): array
    {
        $definitions = CorePermissions::DEFAULT_ROLE_PERMISSIONS;

        foreach (self::ROLE_SOURCES as $source) {
            if (!defined($source . '::DEFAULT_ROLE_PERMISSIONS')) {
                continue;
            }

            foreach ($source::DEFAULT_ROLE_PERMISSIONS as $roleName => $permissions) {
                $definitions[$roleName] = array_values(array_unique(array_merge(
                    $definitions[$roleName] ?? [],
                    $permissions
                )));
            }
        }

        return $definitions;
    }
}
