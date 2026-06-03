<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Services\ConversationInboxIngester;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConversationInboxIngesterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureConversationTables();
        TenantContext::forget();
        TenantContext::setCurrentId(1);

        \DB::table('conversation_messages')->delete();
        \DB::table('conversation_participants')->delete();
        \DB::table('conversations')->delete();
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_ingester_persists_inbound_media_provenance(): void
    {
        $result = app(ConversationInboxIngester::class)->ingest(new InboxMessageEnvelope(
            channel: 'social_dm',
            instanceId: 10,
            conversationExternalId: 'conv-ext-1',
            contactExternalId: 'contact-1',
            contactName: 'Alice',
            direction: 'in',
            type: 'image',
            body: '[image attachment]',
            externalMessageId: 'mid-123',
            payload: ['source' => 'webhook'],
            conversationMetadata: ['platform' => 'instagram'],
            messageStatus: 'delivered',
            mediaUrl: 'https://cdn.example.test/media/image.jpg',
            mediaMime: 'image/jpeg',
            providerOrigin: 'instagram_meta',
            providerMediaUrl: 'https://cdn.example.test/media/image.jpg',
            copiedLocally: false,
            ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
            incrementUnread: true,
            writeActivityLog: false,
            broadcast: false,
        ));

        $this->assertTrue($result->messageWasCreated);

        $message = ConversationMessage::query()->find($result->message->id);
        $this->assertNotNull($message);
        $this->assertSame('image', $message->type);
        $this->assertSame('https://cdn.example.test/media/image.jpg', $message->media_url);
        $this->assertSame('instagram_meta', data_get($message->payload, 'provider_origin'));
        $this->assertSame('https://cdn.example.test/media/image.jpg', data_get($message->payload, 'provider_media_url'));
        $this->assertSame(false, data_get($message->payload, 'copied_locally'));
        $this->assertSame('important_only', data_get($message->payload, 'copy_policy'));
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

        if (!Schema::hasTable('conversation_participants')) {
            Schema::create('conversation_participants', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamp('last_read_at')->nullable();
                $table->timestamp('invited_at')->nullable();
                $table->unsignedBigInteger('invited_by')->nullable();
                $table->timestamp('left_at')->nullable();
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
}
