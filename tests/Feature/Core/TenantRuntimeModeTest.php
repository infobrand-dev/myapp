<?php

namespace Tests\Feature\Core;

use App\Models\Tenant;
use App\Multitenancy\ResolvedTenant;
use App\Multitenancy\TenantConnectionManager;
use Tests\TestCase;

class TenantRuntimeModeTest extends TestCase
{
    public function test_column_mode_does_not_switch_default_database_connection(): void
    {
        config()->set('multitenancy.runtime_mode', 'column');

        $tenant = new Tenant([
            'id' => 99,
            'name' => 'Column Tenant',
            'slug' => 'column-tenant',
            'schema_name' => 'tenant_column',
        ]);
        $tenant->exists = true;

        $manager = app(TenantConnectionManager::class);
        $default = config('database.default');

        $manager->initialize(new ResolvedTenant(
            $tenant,
            'tenant',
            'tenant_column',
            'myapp',
            '127.0.0.1',
            5432,
            'postgres',
            null
        ));

        $this->assertSame($default, config('database.default'));
        $this->assertFalse($manager->shouldSwitchRuntimeConnection());
    }
}
