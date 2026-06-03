<?php

namespace Tests\Feature\Core;

use App\Models\Tenant;
use App\Models\TenantDatabase;
use App\Models\TenantDomain;
use App\Models\TenantTopology;
use App\Models\TenantServer;
use App\Multitenancy\TenantRegistry;
use App\Multitenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistryTopologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_resolves_tenant_by_domain(): void
    {
        $server = TenantServer::query()->create([
            'key' => 'server-a',
            'host' => '127.0.0.1',
            'port' => 5432,
            'status' => 'active',
        ]);

        $database = TenantDatabase::query()->create([
            'server_id' => $server->id,
            'key' => 'main',
            'database_name' => 'myapp',
            'username' => 'postgres',
            'password' => 'secret',
            'status' => 'active',
            'max_schemas' => 100,
            'current_schemas' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
            'status' => 'active',
            'server_id' => $server->id,
            'database_id' => $database->id,
            'schema_name' => 'public',
        ]);

        TenantTopology::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_server_id' => $server->id,
            'tenant_database_id' => $database->id,
            'server_key' => 'primary',
            'database_key' => 'main',
            'schema_name' => 'public',
            'isolation_mode' => 'tenant_id',
            'status' => 'active',
        ]);

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => 'acme.example.test',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $resolvedTenant = app(TenantRegistry::class)->findByDomain('acme.example.test');

        $this->assertNotNull($resolvedTenant);
        $this->assertSame($tenant->id, $resolvedTenant->id);
    }

    public function test_tenant_resolver_builds_runtime_topology_payload(): void
    {
        $server = TenantServer::query()->create([
            'key' => 'server-b',
            'host' => '10.0.0.10',
            'port' => 5433,
            'status' => 'active',
        ]);

        $database = TenantDatabase::query()->create([
            'server_id' => $server->id,
            'key' => 'enterprise-a',
            'database_name' => 'tenant_cluster',
            'username' => 'tenant_user',
            'password' => 'super-secret',
            'status' => 'active',
            'sslmode' => 'require',
            'max_schemas' => 100,
            'current_schemas' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Beta',
            'slug' => 'beta',
            'is_active' => true,
            'status' => 'active',
            'server_id' => $server->id,
            'database_id' => $database->id,
            'schema_name' => 'tenant_beta',
        ]);

        TenantTopology::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_server_id' => $server->id,
            'tenant_database_id' => $database->id,
            'server_key' => 'server-b',
            'database_key' => 'enterprise-a',
            'schema_name' => 'tenant_beta',
            'isolation_mode' => 'schema',
            'status' => 'active',
        ]);

        $resolved = app(TenantResolver::class)->resolve($tenant->fresh(['topology.database.server']));

        $this->assertSame('tenant_beta', $resolved->schemaName);
        $this->assertSame('tenant_cluster', $resolved->databaseName);
        $this->assertSame('10.0.0.10', $resolved->host);
        $this->assertSame(5433, $resolved->port);
        $this->assertSame('tenant_user', $resolved->username);
        $this->assertSame('super-secret', $resolved->password);
        $this->assertSame('require', $resolved->sslmode);
    }
}
