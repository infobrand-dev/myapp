<?php

namespace Tests\Feature\Core;

use App\Multitenancy\TenantMigrationManifest;
use Tests\TestCase;

class TenantMigrationManifestTest extends TestCase
{
    public function test_tenant_manifest_includes_core_and_module_migrations(): void
    {
        $manifest = app(TenantMigrationManifest::class);
        $paths = $manifest->allPaths();

        $this->assertContains(database_path('migrations/2014_10_12_000000_create_users_table.php'), $paths);
        $this->assertContains(app_path('Modules/Sales/Database/Migrations'), $paths);
        $this->assertContains(app_path('Modules/Chatbot/Database/Migrations'), $paths);
    }

    public function test_tenant_manifest_excludes_central_registry_migrations(): void
    {
        $manifest = app(TenantMigrationManifest::class);
        $paths = $manifest->allPaths();

        $this->assertNotContains(database_path('migrations/2026_02_03_130000_create_tenants_table.php'), $paths);
        $this->assertNotContains(database_path('migrations/2026_06_02_090000_create_tenant_registry_topology_tables.php'), $paths);
        $this->assertNotContains(database_path('migrations/2026_03_28_120000_create_platform_plan_orders_table.php'), $paths);
        $this->assertNotContains(database_path('migrations/2026_06_01_160000_create_storage_profiles_and_extend_stored_files.php'), $paths);
    }
}
