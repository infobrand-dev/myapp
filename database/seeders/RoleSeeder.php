<?php

namespace Database\Seeders;

use App\Modules\Inventory\InventoryServiceProvider;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (InventoryServiceProvider::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super-admin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);

        $superAdmin->givePermissionTo(InventoryServiceProvider::PERMISSIONS);
        $admin->givePermissionTo([
            'inventory.view-stock',
            'inventory.view-movement',
            'inventory.manage-opening-stock',
            'inventory.manage-stock-adjustment',
            'inventory.manage-stock-transfer',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
