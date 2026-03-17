<?php

namespace Database\Seeders;

use App\Modules\Discounts\DiscountsServiceProvider;
use App\Modules\Inventory\InventoryServiceProvider;
use App\Modules\Products\ProductsServiceProvider;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $modulePermissionMaps = [
            ProductsServiceProvider::DEFAULT_ROLE_PERMISSIONS,
            InventoryServiceProvider::DEFAULT_ROLE_PERMISSIONS,
            DiscountsServiceProvider::DEFAULT_ROLE_PERMISSIONS,
        ];

        $allPermissions = array_unique(array_merge(
            ProductsServiceProvider::PERMISSIONS,
            InventoryServiceProvider::PERMISSIONS,
            DiscountsServiceProvider::PERMISSIONS
        ));

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super-admin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);

        $roles = [
            'Super-admin' => $superAdmin,
            'Admin' => $admin,
        ];

        foreach ($modulePermissionMaps as $defaults) {
            foreach ($defaults as $roleName => $permissions) {
                if (!isset($roles[$roleName])) {
                    $roles[$roleName] = Role::firstOrCreate(['name' => $roleName]);
                }

                $roles[$roleName]->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
