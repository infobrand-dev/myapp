<?php

namespace Tests\Feature\SocialMedia;

use App\Models\User;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class SocialWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ConversationsServiceProvider::class);
        $this->app->register(SocialMediaServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Conversations/database/migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/SocialMedia/database/migrations',
            '--force' => true,
        ]);

        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    public function test_webhook_rejects_account_id_without_matching_token()
    {
        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'name' => 'Page A',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $this->postJson('/social-media/webhook', [
            'token' => 'invalid-token',
            'platform' => 'facebook',
            'contact_id' => 'user-1',
            'message' => 'Halo',
            'direction' => 'in',
            'account_id' => $account->id,
        ])->assertStatus(401);
    }

    public function test_webhook_deduplicates_external_message_id()
    {
        SocialAccount::query()->create([
            'platform' => 'facebook',
            'name' => 'Page A',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $payload = [
            'token' => 'valid-token',
            'platform' => 'facebook',
            'contact_id' => 'user-1',
            'message' => 'Pesan sama',
            'direction' => 'in',
            'external_message_id' => 'ext-123',
        ];

        $this->postJson('/social-media/webhook', $payload)
            ->assertStatus(200)
            ->assertJson(['stored' => true]);

        $this->postJson('/social-media/webhook', $payload)
            ->assertStatus(200)
            ->assertJson(['stored' => true, 'deduplicated' => true]);

        $conversation = Conversation::query()
            ->where('channel', 'social_dm')
            ->where('contact_external_id', 'user-1')
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame(
            1,
            ConversationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', 'ext-123')
                ->count()
        );
    }

    public function test_native_meta_webhook_creates_conversation_for_facebook_page_message()
    {
        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'name' => 'Page A',
            'page_id' => 'page-123',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page-123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user-meta-1'],
                            'recipient' => ['id' => 'page-123'],
                            'timestamp' => 1710000000000,
                            'message' => [
                                'mid' => 'mid.meta.123',
                                'text' => 'Halo dari Meta',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/social-media/webhook', $payload)
            ->assertStatus(200)
            ->assertJson([
                'stored' => true,
                'processed' => 1,
                'deduplicated' => false,
            ]);

        $conversation = Conversation::query()
            ->where('channel', 'social_dm')
            ->where('contact_external_id', 'user-meta-1')
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame('facebook', data_get($conversation->metadata, 'platform'));
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'external_message_id' => 'mid.meta.123',
            'body' => 'Halo dari Meta',
        ]);
        $account->refresh();
        $this->assertNotNull($account->lastInboundAt());
    }

    public function test_native_meta_webhook_summarizes_attachments()
    {
        SocialAccount::query()->create([
            'platform' => 'instagram',
            'name' => 'IG A',
            'ig_business_id' => 'ig-123',
            'page_id' => 'page-ig-123',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig-123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig-user-1'],
                            'recipient' => ['id' => 'ig-123'],
                            'message' => [
                                'mid' => 'mid.ig.attachment',
                                'attachments' => [
                                    ['type' => 'image'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/social-media/webhook', $payload)
            ->assertStatus(200)
            ->assertJson([
                'stored' => true,
                'processed' => 1,
            ]);

        $this->assertDatabaseHas('conversations', [
            'channel' => 'social_dm',
            'contact_external_id' => 'ig-user-1',
        ]);
        $this->assertDatabaseHas('conversation_messages', [
            'external_message_id' => 'mid.ig.attachment',
            'body' => '[image attachment]',
        ]);
    }

    public function test_verify_returns_plain_text_challenge()
    {
        config(['services.meta.verify_token' => 'verify-123']);

        $response = $this->get('/social-media/webhook?hub_verify_token=verify-123&hub_challenge=abc123');

        $response->assertStatus(200);
        $response->assertSeeText('abc123');
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('content-type'));
    }

    public function test_resume_bot_endpoint_clears_handoff_flags()
    {
        $owner = User::factory()->create();

        $conversation = Conversation::query()->create([
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'user-2',
            'contact_name' => 'User 2',
            'status' => 'open',
            'owner_id' => $owner->id,
            'metadata' => [
                'needs_human' => true,
                'auto_reply_paused' => true,
                'handoff_reason' => 'keyword_request_human',
                'handoff_at' => now()->toDateTimeString(),
            ],
        ]);

        $this->actingAs($owner)
            ->post('/social-media/conversations/' . $conversation->id . '/resume-bot')
            ->assertRedirect();

        $conversation->refresh();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        $this->assertFalse((bool) ($metadata['needs_human'] ?? true));
        $this->assertFalse((bool) ($metadata['auto_reply_paused'] ?? true));
    }

    public function test_non_owner_cannot_resume_bot()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::query()->create([
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'user-9',
            'contact_name' => 'User 9',
            'status' => 'open',
            'owner_id' => $owner->id,
            'metadata' => [
                'needs_human' => true,
                'auto_reply_paused' => true,
            ],
        ]);

        $this->actingAs($other)
            ->post('/social-media/conversations/' . $conversation->id . '/resume-bot')
            ->assertStatus(403);
    }

    public function test_owner_can_pause_bot()
    {
        $owner = User::factory()->create();

        $conversation = Conversation::query()->create([
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'user-11',
            'contact_name' => 'User 11',
            'status' => 'open',
            'owner_id' => $owner->id,
            'metadata' => [
                'needs_human' => false,
                'auto_reply_paused' => false,
            ],
        ]);

        $this->actingAs($owner)
            ->post('/social-media/conversations/' . $conversation->id . '/pause-bot')
            ->assertRedirect();

        $conversation->refresh();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $this->assertTrue((bool) ($metadata['needs_human'] ?? false));
        $this->assertTrue((bool) ($metadata['auto_reply_paused'] ?? false));
    }

    public function test_non_owner_cannot_pause_bot()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::query()->create([
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'user-12',
            'contact_name' => 'User 12',
            'status' => 'open',
            'owner_id' => $owner->id,
            'metadata' => [
                'needs_human' => false,
                'auto_reply_paused' => false,
            ],
        ]);

        $this->actingAs($other)
            ->post('/social-media/conversations/' . $conversation->id . '/pause-bot')
            ->assertStatus(403);
    }

    public function test_owner_can_reply_with_media_file()
    {
        Storage::fake('public');
        Queue::fake();
        config(['app.url' => 'https://example.test']);
        if (!is_dir(public_path('storage'))) {
            mkdir(public_path('storage'), 0777, true);
        }

        $owner = User::factory()->create();
        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'user-media-1',
            'contact_name' => 'User Media',
            'status' => 'open',
            'owner_id' => $owner->id,
            'metadata' => [
                'platform' => 'facebook',
            ],
        ]);

        $this->actingAs($owner)
            ->post('/social-media/conversations/' . $conversation->id . '/reply', [
                'body' => 'Lihat gambar ini',
                'media_file' => UploadedFile::fake()->create('social-test.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect();

        $message = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertSame('image', $message->type);
        $this->assertNotEmpty($message->media_url);
        $this->assertSame('Lihat gambar ini', $message->body);

        Queue::assertPushed(SendSocialMessage::class);
    }

    public function test_send_social_message_uses_attachment_payload_for_media()
    {
        Http::fake([
            '*' => Http::response(['message_id' => 'meta-out-1'], 200),
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'instagram',
            'name' => 'IG A',
            'page_id' => 'page-123',
            'ig_business_id' => 'ig-123',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => 'ig-user-out-1',
            'contact_name' => 'IG User',
            'status' => 'open',
            'metadata' => [
                'platform' => 'instagram',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'image',
            'body' => 'Foto produk',
            'media_url' => 'https://cdn.example.com/social/image.jpg',
            'media_mime' => 'image/jpeg',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains((string) $request->url(), '/ig-123/messages')
                && data_get($data, 'message.attachment.type') === 'image'
                && data_get($data, 'message.attachment.payload.url') === 'https://cdn.example.com/social/image.jpg';
        });

        $message->refresh();
        $this->assertSame('sent', $message->status);
        $this->assertSame('meta-out-1', $message->external_message_id);
        $account->refresh();
        $this->assertNotNull($account->lastOutboundAt());
        $this->assertNull($account->lastOutboundErrorAt());
    }
}
