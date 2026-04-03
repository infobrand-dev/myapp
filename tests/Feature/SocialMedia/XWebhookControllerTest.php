<?php

namespace Tests\Feature\SocialMedia;

use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class XWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ChatbotServiceProvider::class);
        $this->app->register(ConversationsServiceProvider::class);
        $this->app->register(SocialMediaServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Chatbot/database/migrations',
            '--force' => true,
        ]);
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

    public function test_x_crc_returns_expected_response_token(): void
    {
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $response = $this->getJson('/social-media/webhook/x?crc_token=challenge');

        $response->assertOk()->assertJson([
            'response_token' => 'sha256=' . base64_encode(hash_hmac('sha256', 'challenge', 'consumer-secret', true)),
        ]);
    }

    public function test_x_webhook_ingests_message_create_event_into_social_dm_conversation(): void
    {
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-access-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
            ],
        ]);

        $payload = [
            'for_user_id' => '111',
            'direct_message_events' => [
                [
                    'event_type' => 'MessageCreate',
                    'id' => 'evt-x-1',
                    'dm_conversation_id' => '111-222',
                    'sender_id' => '222',
                    'text' => 'Halo dari X inbound',
                ],
            ],
        ];

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256=' . base64_encode(hash_hmac('sha256', (string) $rawBody, 'consumer-secret', true));

        $response = $this->withHeaders([
            'x-twitter-webhooks-signature' => $signature,
        ])->postJson('/social-media/webhook/x', $payload);

        $response->assertOk()->assertJson([
            'stored' => true,
            'processed' => 1,
            'deduplicated' => false,
        ]);

        $conversation = Conversation::query()
            ->where('channel', 'social_dm')
            ->where('contact_external_id', '222')
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame('x', data_get($conversation->metadata, 'platform'));
        $this->assertSame('111-222', data_get($conversation->metadata, 'x_dm_conversation_id'));
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'external_message_id' => 'evt-x-1',
            'body' => 'Halo dari X inbound',
        ]);

        $account->refresh();
        $this->assertNotNull($account->lastInboundAt());
    }

    public function test_x_webhook_dispatches_chatbot_auto_reply_for_inbound_message(): void
    {
        Queue::fake();
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $chatbotAccountId = \DB::table('chatbot_accounts')->insertGetId([
            'tenant_id' => 1,
            'name' => 'X Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'system_prompt' => 'Jawab singkat.',
            'focus_scope' => null,
            'response_style' => 'balanced',
            'operation_mode' => 'ai_only',
            'api_key' => encrypt('dummy-key'),
            'status' => 'active',
            'mirror_to_conversations' => false,
            'rag_enabled' => false,
            'rag_top_k' => 3,
            'metadata' => json_encode([
                'bot_config' => [
                    'auto_reply_enabled' => true,
                    'allowed_channels' => ['social_dm'],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
            'automation_mode' => 'ai_first',
            'access_scope' => 'public',
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-access-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => '111',
            ],
        ]);

        SocialAccountChatbotIntegration::query()->create([
            'social_account_id' => $account->id,
            'auto_reply' => true,
            'chatbot_account_id' => $chatbotAccountId,
        ]);

        $payload = [
            'for_user_id' => '111',
            'direct_message_events' => [
                [
                    'event_type' => 'MessageCreate',
                    'id' => 'evt-x-bot-1',
                    'dm_conversation_id' => '111-222',
                    'sender_id' => '222',
                    'text' => 'Halo bot X',
                ],
            ],
        ];

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256=' . base64_encode(hash_hmac('sha256', (string) $rawBody, 'consumer-secret', true));

        $this->withHeaders([
            'x-twitter-webhooks-signature' => $signature,
        ])->postJson('/social-media/webhook/x', $payload)->assertOk();

        Queue::assertPushed(GenerateAiReply::class);
    }

    public function test_x_webhook_rejects_invalid_signature(): void
    {
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $response = $this->withHeaders([
            'x-twitter-webhooks-signature' => 'sha256=invalid',
        ])->postJson('/social-media/webhook/x', [
            'for_user_id' => '111',
            'direct_message_events' => [],
        ]);

        $response->assertStatus(401);
    }
}
