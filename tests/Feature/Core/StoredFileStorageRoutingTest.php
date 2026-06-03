<?php

namespace Tests\Feature\Core;

use App\Models\StoredFile;
use App\Models\StorageBucket;
use App\Models\StorageProfile;
use App\Models\StorageServer;
use App\Models\Tenant;
use App\Models\TenantStorageTopology;
use App\Services\SharedFileAccessService;
use App\Services\StoredFileService;
use App\Services\StorageAccessService;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredFileStorageRoutingTest extends TestCase
{
    private static bool $storageTopologyReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureStorageTopologyReady();

        config()->set('workspace-files.origin_system', 'test-suite');
        config()->set('workspace-files.origin_owner', 'first_party');

        Storage::fake('private');
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

        StorageProfile::query()->updateOrCreate([
            'code' => 'public-default-profile',
        ], [
            'name' => 'PUBLIC DEFAULT PROFILE',
            'driver' => 'local',
            'visibility_scope' => 'public',
            'is_active' => true,
            'is_default' => true,
            'weight' => 100,
            'priority' => 10,
            'failure_mode' => 'mark_unreachable',
            'root_path' => storage_path('framework/testing/storage-profiles/public-default-profile'),
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

    public function test_stored_file_service_uses_active_storage_profile_for_new_uploads(): void
    {
        $profile = $this->makeLocalProfile('private-active', 'private');
        $file = UploadedFile::fake()->createWithContent('proof.pdf', 'hello-private-profile');

        $storedFile = app(StoredFileService::class)->storeUploadedFile($file, 'finance_attachment', [
            'tenant_id' => 1,
            'source_module' => 'finance',
            'source_context' => 'test',
        ]);

        $this->assertSame($profile->id, $storedFile->storage_profile_id);
        $this->assertSame($profile->code, $storedFile->disk);
        $this->assertSame('available', $storedFile->availability_status);
        $this->assertStringStartsWith('tenants/1/private/finance/attachments/', $storedFile->path);
        $this->assertNotNull(data_get($storedFile->storage_snapshot, 'tenant_storage_topology_id'));
        $this->assertSame('private-default', data_get($storedFile->storage_snapshot, 'storage_bucket_key'));
        $this->assertFileExists($profile->root_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedFile->path));
    }

    public function test_stored_file_service_skips_inactive_profiles_and_falls_back_to_legacy_disk(): void
    {
        $this->makeLocalProfile('private-inactive', 'private', false);
        $file = UploadedFile::fake()->createWithContent('receipt.pdf', 'legacy-fallback');

        $storedFile = app(StoredFileService::class)->storeUploadedFile($file, 'finance_attachment', [
            'tenant_id' => 1,
        ]);

        $this->assertNull($storedFile->storage_profile_id);
        $this->assertSame('private', $storedFile->disk);
        $this->assertTrue((bool) data_get($storedFile->meta, 'storage_topology_degraded'));
        Storage::disk('private')->assertExists($storedFile->path);
    }

    public function test_storage_access_service_supports_legacy_downloads(): void
    {
        Storage::disk('private')->put('legacy/test.txt', 'legacy-content');

        $storedFile = StoredFile::query()->create([
            'tenant_id' => 1,
            'disk' => 'private',
            'directory' => 'legacy',
            'path' => 'legacy/test.txt',
            'visibility' => 'private',
            'availability_status' => 'available',
            'category' => 'finance_attachment',
            'storage_driver' => 'local',
            'original_name' => 'test.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 14,
            'meta' => [],
        ]);

        $result = app(StorageAccessService::class)->download($storedFile);

        $this->assertSame('legacy_success', $result['result']);
        $this->assertSame(200, $result['response']->getStatusCode());
    }

    public function test_storage_access_service_marks_profile_file_deleted_when_backend_object_is_missing(): void
    {
        $profile = $this->makeLocalProfile('private-missing', 'private');

        $storedFile = StoredFile::query()->create([
            'tenant_id' => 1,
            'storage_profile_id' => $profile->id,
            'disk' => $profile->code,
            'directory' => 'finance/attachments',
            'path' => 'finance/attachments/missing.pdf',
            'visibility' => 'private',
            'availability_status' => 'available',
            'category' => 'finance_attachment',
            'storage_driver' => 'local',
            'storage_root' => $profile->root_path,
            'original_name' => 'missing.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 0,
            'meta' => [],
        ]);

        $result = app(StorageAccessService::class)->download($storedFile->fresh());

        $this->assertSame('missing_object', $result['result']);
        $this->assertSame(404, $result['response']->getStatusCode());
        $this->assertSame('deleted', $storedFile->fresh()->availability_status);
    }

    public function test_channel_shared_media_issues_signed_share_url_and_serves_content(): void
    {
        $this->makeLocalProfile('private-channel', 'private');
        $file = UploadedFile::fake()->createWithContent('share.txt', 'signed-share-content');

        $storedFile = app(StoredFileService::class)->storeUploadedFile($file, 'channel_shared_media', [
            'tenant_id' => 1,
            'source_module' => 'conversations',
            'source_context' => 'test',
        ]);

        $issued = app(SharedFileAccessService::class)->issueShareUrl($storedFile, 'provider', 900, null, [
            'channel' => 'test-suite',
        ]);

        $this->assertStringContainsString('/files/shared/', $issued['url']);
        $this->assertStringNotContainsString('/storage/', $issued['url']);

        $response = $this->get($issued['url']);

        $response->assertOk();
        $this->assertSame('signed-share-content', $response->streamedContent());
        $this->assertDatabaseHas('stored_file_access_logs', [
            'stored_file_id' => $storedFile->id,
            'action' => 'signed_url_issued',
        ]);
        $this->assertDatabaseHas('stored_file_access_logs', [
            'stored_file_id' => $storedFile->id,
            'action' => 'provider_fetch',
        ]);
    }

    private function makeLocalProfile(string $code, string $scope, bool $active = true): StorageProfile
    {
        $root = storage_path('framework/testing/storage-profiles/' . $code);
        File::ensureDirectoryExists($root);

        return tap(StorageProfile::query()->updateOrCreate([
            'code' => $code,
        ], [
            'name' => strtoupper($code),
            'driver' => 'local',
            'visibility_scope' => $scope,
            'is_active' => $active,
            'is_default' => true,
            'weight' => 100,
            'priority' => 10,
            'failure_mode' => 'mark_unreachable',
            'root_path' => $root,
        ]), static function (StorageProfile $profile) use ($scope, $code): void {
            StorageProfile::query()
                ->where('visibility_scope', $scope)
                ->where('code', '!=', $code)
                ->update(['is_default' => false]);
        });
    }

    private function ensureStorageTopologyReady(): void
    {
        if (self::$storageTopologyReady) {
            return;
        }

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

        self::$storageTopologyReady = true;
    }
}
