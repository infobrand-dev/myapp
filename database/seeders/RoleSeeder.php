<?php

namespace Database\Seeders;

use App\Support\TenantRoleProvisioner;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(TenantRoleProvisioner::class)->ensureForAllTenants();
    }
}
