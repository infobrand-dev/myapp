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
use Illuminate\Support\Facades\Crypt;
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

    public function test_send_social_message_blocks_non_live_platform_connector()
    {
        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'facebook',
            'name' => 'Legacy Account',
            'page_id' => 'page-x-1',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => 'x-user-1',
            'contact_name' => 'X User',
            'status' => 'open',
            'metadata' => [
                'platform' => 'threads',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => 'Halo X',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        $message->refresh();
        $this->assertSame('error', $message->status);
        $this->assertSame('Outbound connector untuk platform ini belum aktif.', $message->error_message);
    }

    public function test_send_social_message_dispatches_text_to_x_connector(): void
    {
        Http::fake([
            'https://api.x.com/*' => Http::response(['data' => ['event_id' => 'x-out-1']], 200),
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-user-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
            ],
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => '222',
            'contact_name' => 'X Contact',
            'status' => 'open',
            'metadata' => [
                'platform' => 'x',
                'x_dm_conversation_id' => '111-222',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => 'Halo dari outbound X',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.x.com/2/dm_conversations/111-222/messages'
                && data_get($request->data(), 'text') === 'Halo dari outbound X';
        });

        $message->refresh();
        $this->assertSame('sent', $message->status);
        $this->assertSame('x-out-1', $message->external_message_id);
    }

    public function test_send_social_message_refreshes_x_token_and_retries_once(): void
    {
        config([
            'services.x_api.client_id' => 'x-client-123',
            'services.x_api.client_secret' => 'x-secret-123',
            'services.x_api.token_url' => 'https://api.x.com/2/oauth2/token',
        ]);

        Http::fake([
            'https://api.x.com/2/dm_conversations/111-222/messages' => Http::sequence()
                ->push(['title' => 'Unauthorized'], 401)
                ->push(['data' => ['event_id' => 'x-out-2']], 200),
            'https://api.x.com/2/oauth2/token' => Http::response([
                'access_token' => 'x-access-token-new',
                'refresh_token' => 'x-refresh-token-new',
            ], 200),
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-access-token-old',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
                'x_refresh_token_enc' => Crypt::encryptString('x-refresh-token-old'),
            ],
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => '222',
            'contact_name' => 'X Contact',
            'status' => 'open',
            'metadata' => [
                'platform' => 'x',
                'x_dm_conversation_id' => '111-222',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => 'Halo setelah refresh',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        Http::assertSentCount(3);

        $message->refresh();
        $account->refresh();

        $this->assertSame('sent', $message->status);
        $this->assertSame('x-out-2', $message->external_message_id);
        $this->assertSame('x-access-token-new', $account->access_token);
        $this->assertNotEmpty(data_get($account->metadata, 'oauth_refreshed_at'));
        $this->assertSame('ok', data_get($account->metadata, 'last_token_refresh_status'));
    }

    public function test_send_social_message_uploads_image_for_x(): void
    {
        config([
            'app.url' => 'https://example.test',
        ]);

        $path = 'social_messages/2026/04/x-image.jpg';
        $absolutePath = public_path('storage/' . $path);
        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }
        file_put_contents($absolutePath, 'fake-image-binary');

        Http::fake([
            'https://api.x.com/2/media/upload' => Http::response([
                'data' => ['id' => 'media-123'],
            ], 200),
            'https://api.x.com/2/dm_conversations/111-222/messages' => Http::response([
                'data' => ['event_id' => 'x-out-media-1'],
            ], 200),
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
            ],
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => '222',
            'contact_name' => 'X Contact',
            'status' => 'open',
            'metadata' => [
                'platform' => 'x',
                'x_dm_conversation_id' => '111-222',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'image',
            'body' => 'Foto produk',
            'media_url' => 'https://example.test/storage/' . $path,
            'media_mime' => 'image/jpeg',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.x.com/2/media/upload';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.x.com/2/dm_conversations/111-222/messages'
                && data_get($request->data(), 'attachments.0.media_id') === 'media-123';
        });

        $message->refresh();
        $this->assertSame('sent', $message->status);
        $this->assertSame('x-out-media-1', $message->external_message_id);
    }

    public function test_send_social_message_uploads_video_for_x_using_chunked_flow(): void
    {
        config([
            'app.url' => 'https://example.test',
        ]);

        $path = 'social_messages/2026/04/x-video.mp4';
        $absolutePath = public_path('storage/' . $path);
        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }
        file_put_contents($absolutePath, 'fake-video-binary');

        Http::fake(function ($request) {
            $url = $request->url();

            if ($url === 'https://api.x.com/2/media/upload/media-video-1/append') {
                return Http::response([], 200);
            }

            if ($url === 'https://api.x.com/2/media/upload/media-video-1/finalize') {
                return Http::response([
                    'data' => ['processing_info' => ['state' => 'pending'], 'id' => 'media-video-1'],
                ], 200);
            }

            if ($url === 'https://api.x.com/2/dm_conversations/111-222/messages') {
                return Http::response([
                    'data' => ['event_id' => 'x-out-video-1'],
                ], 200);
            }

            if (str_starts_with($url, 'https://api.x.com/2/media/upload?command=STATUS')) {
                return Http::response([
                    'data' => ['processing_info' => ['state' => 'succeeded'], 'id' => 'media-video-1'],
                ], 200);
            }

            if ($url === 'https://api.x.com/2/media/upload') {
                return Http::response([
                    'data' => ['id' => 'media-video-1'],
                ], 200);
            }

            return Http::response([], 404);
        });

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
            ],
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $account->id,
            'contact_external_id' => '222',
            'contact_name' => 'X Contact',
            'status' => 'open',
            'metadata' => [
                'platform' => 'x',
                'x_dm_conversation_id' => '111-222',
            ],
        ]);

        $message = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'video',
            'body' => 'Video demo',
            'media_url' => 'https://example.test/storage/' . $path,
            'media_mime' => 'video/mp4',
            'status' => 'pending',
        ]);

        (new SendSocialMessage($message->id))->handle();

        $message->refresh();
        $this->assertSame('sent', $message->status);
        $this->assertSame('x-out-video-1', $message->external_message_id);
    }
}
