<?php

namespace Tests\Feature\Core;

use App\Models\StoredFile;
use App\Models\StorageBucket;
use App\Models\StorageProfile;
use App\Models\StorageServer;
use App\Models\Tenant;
use App\Models\TenantStorageTopology;
use App\Models\User;
use App\Services\StoredFileService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StoredFileAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureStoredFilesReady();

        Storage::fake('public');
        TenantContext::forget();
        TenantContext::setCurrentId(1);

        StoredFile::query()->delete();
        StorageProfile::query()->delete();
        TenantStorageTopology::query()->where('tenant_id', 1)->delete();

        Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Default tenant',
            'slug' => 'default',
            'is_active' => true,
            'status' => 'active',
            'schema_name' => 'public',
        ]);

        $storageServer = StorageServer::query()->firstOrCreate([
            'key' => 'primary-storage',
        ], [
            'name' => 'Primary Storage',
            'driver' => 'local',
            'provider' => 'local',
            'region' => null,
            'endpoint' => null,
            'status' => 'active',
        ]);

        $publicBucket = StorageBucket::query()->firstOrCreate([
            'key' => 'public-default',
        ], [
            'storage_server_id' => $storageServer->id,
            'name' => 'public',
            'disk' => 'public',
            'visibility' => 'public',
            'region' => null,
            'base_path' => '/',
            'status' => 'active',
        ]);

        $privateBucket = StorageBucket::query()->firstOrCreate([
            'key' => 'private-default',
        ], [
            'storage_server_id' => $storageServer->id,
            'name' => 'private',
            'disk' => 'private',
            'visibility' => 'private',
            'region' => null,
            'base_path' => '/',
            'status' => 'active',
        ]);

        TenantStorageTopology::query()->updateOrCreate([
            'tenant_id' => 1,
            'visibility' => 'public',
        ], [
            'storage_server_id' => $storageServer->id,
            'storage_bucket_id' => $publicBucket->id,
            'storage_server_key' => 'primary-storage',
            'storage_bucket_key' => 'public-default',
            'disk' => 'public',
            'base_path' => 'tenants/1/public',
            'is_default' => true,
            'status' => 'active',
        ]);

        TenantStorageTopology::query()->updateOrCreate([
            'tenant_id' => 1,
            'visibility' => 'private',
        ], [
            'storage_server_id' => $storageServer->id,
            'storage_bucket_id' => $privateBucket->id,
            'storage_server_key' => 'primary-storage',
            'storage_bucket_key' => 'private-default',
            'disk' => 'private',
            'base_path' => 'tenants/1/private',
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        StoredFile::query()->delete();
        StorageProfile::query()->delete();
        TenantStorageTopology::query()->where('tenant_id', 1)->delete();
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_user_can_access_rejects_sensitive_private_document_without_permission(): void
    {
        $storedFile = StoredFile::query()->create([
            'tenant_id' => 1,
            'disk' => 'private',
            'directory' => 'finance/attachments',
            'path' => 'finance/attachments/proof.pdf',
            'visibility' => 'private',
            'availability_status' => 'available',
            'category' => 'finance_attachment',
            'access_class' => 'private_document',
            'storage_driver' => 'local',
            'original_name' => 'proof.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 12,
            'meta' => [],
        ]);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('Super-admin')->andReturnFalse();
        $user->shouldReceive('hasAnyPermission')->once()->andReturnFalse();

        $this->assertFalse(app(StoredFileService::class)->userCanAccess($storedFile, $user));
    }

    public function test_legacy_sensitive_download_url_only_allows_the_issued_user(): void
    {
        Storage::disk('public')->put('legacy-sensitive/proof.txt', 'legacy-sensitive-content');

        $issuedUser = User::factory()->create(['tenant_id' => 1]);
        $otherUser = User::factory()->create(['tenant_id' => 1]);

        $this->actingAs($issuedUser);

        $url = app(\App\Services\StorageAccessService::class)->legacySensitiveDownloadUrl(
            'public',
            'legacy-sensitive/proof.txt',
            'channel_inbound_evidence',
            'proof.txt'
        );

        $this->assertNotNull($url);

        $this->get($url)->assertOk();

        auth()->logout();
        $this->actingAs($otherUser);
        $this->get($url)->assertForbidden();
    }

    public function test_storage_audit_command_reports_legacy_sensitive_exposures_without_profiles(): void
    {
        StoredFile::query()->create([
            'tenant_id' => 1,
            'disk' => 'public',
            'directory' => 'legacy-sensitive',
            'path' => 'legacy-sensitive/audit.txt',
            'visibility' => 'private',
            'availability_status' => 'legacy_exposed',
            'category' => 'payment_proof',
            'access_class' => 'private_document',
            'storage_driver' => 'local',
            'original_name' => 'audit.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 5,
            'origin_owner' => 'legacy_server_storage',
            'meta' => ['legacy_public_exposed' => true],
        ]);

        StorageProfile::query()->delete();

        $this->artisan('storage:audit-profiles')
            ->expectsOutput('No storage profiles configured.')
            ->expectsOutputToContain('Legacy public-sensitive files detected.')
            ->assertSuccessful();
    }

    private function ensureStoredFilesReady(): void
    {
        if (!Schema::hasTable('stored_files')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_06_01_150000_create_stored_files_tables.php',
                '--force' => true,
            ]);

            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_06_01_160000_create_storage_profiles_and_extend_stored_files.php',
                '--force' => true,
            ]);
        }

        if (!Schema::hasColumn('stored_files', 'access_class')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_06_03_090000_extend_stored_files_for_private_first_storage.php',
                '--force' => true,
            ]);
        }

        if (!Schema::connection('central')->hasTable('storage_profiles')) {
            Artisan::call('migrate', [
                '--database' => 'central',
                '--path' => 'database/migrations/2026_06_02_102000_create_central_storage_profiles_table.php',
                '--force' => true,
            ]);
        }

        if (!Schema::connection('central')->hasTable('storage_servers')) {
            foreach ([
                'database/migrations/2026_06_02_090000_create_tenant_registry_topology_tables.php',
                'database/migrations/2026_06_02_090100_expand_tenants_for_schema_registry.php',
                'database/migrations/2026_06_02_100000_create_tenant_topologies_table.php',
                'database/migrations/2026_06_02_100100_add_key_to_tenant_databases_table.php',
                'database/migrations/2026_06_02_101000_create_runtime_and_storage_topology_tables.php',
            ] as $path) {
                Artisan::call('migrate', [
                    '--database' => 'central',
                    '--path' => $path,
                    '--force' => true,
                ]);
            }
        }
    }
}
