<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Services\WorkspaceMediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Artisan::call('migrate', [
            '--path' => 'app/Modules/Conversations/Database/Migrations',
            '--force' => true,
        ]);

        Tenant::query()->create([
            'id' => 1,
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'is_active' => true,
        ]);
    }

    public function test_workspace_media_storage_deduplicates_same_upload_content(): void
    {
        $service = app(WorkspaceMediaStorageService::class);

        $first = $service->storeUploadedFile(UploadedFile::fake()->createWithContent('a.jpg', 'same-bytes'), 'wa_messages');
        $second = $service->storeUploadedFile(UploadedFile::fake()->createWithContent('b.jpg', 'same-bytes'), 'wa_messages');

        $this->assertSame($first['path'], $second['path']);
        $this->assertFalse($first['deduplicated']);
        $this->assertTrue($second['deduplicated']);
        Storage::disk('public')->assertExists($first['path']);
    }

    public function test_cleanup_orphan_media_deletes_unreferenced_public_files_only(): void
    {
        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'wa_api',
            'instance_id' => 1,
            'contact_external_id' => 'contact-1',
            'contact_name' => 'Contact 1',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        Storage::disk('public')->put('wa_messages/aa/aa/kept.jpg', 'keep');
        Storage::disk('public')->put('wa_messages/bb/bb/orphan.jpg', 'orphan');

        ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'image',
            'body' => 'kept',
            'media_url' => url('storage/wa_messages/aa/aa/kept.jpg'),
            'status' => 'sent',
            'payload' => [
                'storage_disk' => 'public',
                'storage_path' => 'wa_messages/aa/aa/kept.jpg',
            ],
        ]);

        $this->artisan('media:cleanup-orphans')
            ->expectsOutputToContain('orphan.jpg')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('wa_messages/aa/aa/kept.jpg');
        Storage::disk('public')->assertMissing('wa_messages/bb/bb/orphan.jpg');
    }
}
