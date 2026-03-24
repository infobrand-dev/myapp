<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            SubscriptionPlanSeeder::class, // must run before TenantSeeder (TenantSeeder assigns internal-unlimited)
            TenantSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
