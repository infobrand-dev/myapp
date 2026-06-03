<?php

namespace Tests\Feature\Conversations;

use App\Models\StorageBucket;
use App\Models\StorageProfile;
use App\Models\StorageServer;
use App\Models\Tenant;
use App\Models\TenantStorageTopology;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Services\InboundMediaCaptureService;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboundMediaCaptureServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureStorageReady();
        $this->ensureConversationTables();
        $this->ensureWhatsAppTables();

        Storage::fake('private');
        TenantContext::forget();
        TenantContext::setCurrentId(1);

        ConversationMessage::query()->delete();
        Conversation::query()->delete();
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
            'code' => 'private-default-profile',
        ], [
            'name' => 'PRIVATE DEFAULT PROFILE',
            'driver' => 'local',
            'visibility_scope' => 'private',
            'is_active' => true,
            'is_default' => true,
            'weight' => 100,
            'priority' => 10,
            'failure_mode' => 'mark_unreachable',
            'root_path' => storage_path('framework/testing/storage-profiles/private-default-profile'),
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_capture_provider_url_stores_private_file_and_links_message(): void
    {
        Http::fake([
            'https://cdn.example.test/*' => Http::response('provider-image-content', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $message = $this->makeInboundMessage('image', 'https://cdn.example.test/media/photo.jpg');

        $storedFile = app(InboundMediaCaptureService::class)->captureProviderUrl($message, 'https://cdn.example.test/media/photo.jpg', [
            'provider_origin' => 'instagram_meta',
            'provider_media_url' => 'https://cdn.example.test/media/photo.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertNotNull($storedFile);
        $message->refresh();
        $this->assertSame($storedFile->id, data_get($message->payload, 'stored_file_id'));
        $this->assertTrue((bool) data_get($message->payload, 'copied_locally'));
        $this->assertSame('instagram_meta', data_get($message->payload, 'provider_origin'));
        $this->assertStringContainsString('/files/', (string) $message->media_url);
    }

    public function test_capture_whatsapp_cloud_media_resolves_media_id_and_stores_file(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'url' => 'https://lookaside.whatsapp.test/file-123',
                'mime_type' => 'image/png',
            ], 200),
            'https://lookaside.whatsapp.test/*' => Http::response('wa-binary-content', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $instance = WhatsAppInstance::query()->create([
            'tenant_id' => 1,
            'name' => 'WA Cloud',
            'provider' => 'cloud',
            'status' => 'active',
            'is_active' => true,
            'phone_number_id' => 'phone-1',
            'cloud_business_account_id' => 'waba-1',
            'cloud_token' => 'secret-token',
        ]);

        $message = $this->makeInboundMessage('image', 'wa://media/abc123', [
            'provider_origin' => 'whatsapp_cloud',
            'provider_media_id' => 'abc123',
        ]);

        $storedFile = app(InboundMediaCaptureService::class)->captureWhatsAppCloudMedia(
            $message,
            $instance,
            'abc123',
            'image/png',
            'capture'
        );

        $this->assertNotNull($storedFile);
        $message->refresh();
        $this->assertSame($storedFile->id, data_get($message->payload, 'stored_file_id'));
        $this->assertSame('abc123', data_get($message->payload, 'provider_media_id'));
        $this->assertSame(true, data_get($message->payload, 'copied_locally'));
        $this->assertSame('whatsapp_cloud', data_get($message->payload, 'provider_origin'));
    }

    private function makeInboundMessage(string $type, string $mediaUrl, array $payload = []): ConversationMessage
    {
        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'instance_id' => 1,
            'channel' => 'social_dm',
            'contact_external_id' => 'contact-1',
            'contact_name' => 'Alice',
            'status' => 'open',
            'last_message_at' => now(),
            'metadata' => ['platform' => 'instagram'],
        ]);

        return ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => $type,
            'body' => '[' . $type . ' attachment]',
            'media_url' => $mediaUrl,
            'media_mime' => 'image/jpeg',
            'status' => 'delivered',
            'payload' => $payload,
        ]);
    }

    private function ensureStorageReady(): void
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

    private function ensureConversationTables(): void
    {
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('instance_id')->nullable();
                $table->string('channel');
                $table->string('external_id')->nullable();
                $table->string('contact_external_id');
                $table->string('contact_name')->nullable();
                $table->string('status')->default('open');
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('locked_until')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('last_incoming_at')->nullable();
                $table->timestamp('last_outgoing_at')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('conversation_messages')) {
            Schema::create('conversation_messages', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('direction', 10);
                $table->string('type', 30)->default('text');
                $table->text('body')->nullable();
                $table->text('media_url')->nullable();
                $table->string('media_mime')->nullable();
                $table->string('status')->nullable();
                $table->string('external_message_id')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureWhatsAppTables(): void
    {
        if (Schema::hasTable('whatsapp_instances')) {
            return;
        }

        $this->artisan('migrate', [
            '--path' => 'app/Modules/WhatsAppApi/database/migrations',
            '--force' => true,
        ]);
    }
}
