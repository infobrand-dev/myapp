<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Default tenant',
                'slug' => 'default',
                'is_active' => true,
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
