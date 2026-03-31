<?php

namespace Tests\Feature\SocialMedia;

use App\Models\User;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
