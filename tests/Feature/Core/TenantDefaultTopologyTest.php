<?php

namespace Tests\Feature\Core;

use App\Models\Tenant;
use App\Models\TenantRuntimeTopology;
use App\Models\TenantStorageTopology;
use App\Models\TenantTopology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDefaultTopologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_topology_defaults_to_tenant_id_public_main_primary_shape(): void
    {
        $tenant = Tenant::query()->first();
        $this->assertNotNull($tenant);

        $topology = TenantTopology::query()->where('tenant_id', $tenant->id)->first();

        $this->assertNotNull($topology);
        $this->assertSame('tenant_id', $topology->isolation_mode);
        $this->assertSame('public', $topology->schema_name);
        $this->assertSame('main', $topology->database_key);
        $this->assertSame('primary', $topology->server_key);
    }

    public function test_new_tenant_has_default_runtime_and_storage_topology_shape(): void
    {
        $tenant = Tenant::query()->first();
        $this->assertNotNull($tenant);

        $runtime = TenantRuntimeTopology::query()->where('tenant_id', $tenant->id)->first();
        $storage = TenantStorageTopology::query()->where('tenant_id', $tenant->id)->orderBy('id')->get();

        $this->assertNotNull($runtime);
        $this->assertSame('primary-app', $runtime->app_server_key);
        $this->assertSame('default', $runtime->queue_cluster);

        $this->assertCount(2, $storage);
        $this->assertTrue($storage->contains(fn ($item) => $item->visibility === 'public' && $item->is_default));
        $this->assertTrue($storage->contains(fn ($item) => $item->visibility === 'private' && $item->is_default));
    }
}
