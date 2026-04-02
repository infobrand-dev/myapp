<?php

namespace Tests\Feature\Chatbot;

use App\Models\User;
use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use App\Services\AiUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatbotAutoReplyHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ConversationsServiceProvider::class);
        $this->app->register(ChatbotServiceProvider::class);
        $this->app->register(SocialMediaServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Conversations/database/migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/Chatbot/database/migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/SocialMedia/database/migrations',
            '--force' => true,
        ]);

        $this->mock(AiUsageService::class, function ($mock): void {
            $mock->shouldReceive('hasCreditsRemaining')->andReturn(true);
            $mock->shouldReceive('recordUsage')->andReturnNull();
        });
    }

    public function test_generate_ai_reply_handoffs_when_rag_has_no_context(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Ini jawaban yang tidak boleh langsung dikirim.']],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 10,
                    'total_tokens' => 20,
                ],
            ], 200),
        ]);

        $account = ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Support Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'rag_enabled' => true,
            'metadata' => [
                'bot_config' => [
                    'human_handoff_ack_enabled' => false,
                    'minimum_context_score' => 4,
                    'allowed_channels' => ['social_dm'],
                ],
            ],
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'cust-1',
            'contact_name' => 'Customer One',
            'status' => 'open',
        ]);

        $incoming = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => 'text',
            'body' => 'berapa harga paket yang cocok untuk outlet saya?',
            'status' => 'delivered',
        ]);

        (new GenerateAiReply($conversation->id, $incoming->id, $account->id))->handle();

        $conversation->refresh();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        $this->assertSame('handoff', $metadata['bot_last_decision'] ?? null);
        $this->assertSame('no_context', $metadata['handoff_reason'] ?? null);
        $this->assertTrue((bool) ($metadata['auto_reply_paused'] ?? false));
        $this->assertSame(1, ConversationMessage::query()->where('conversation_id', $conversation->id)->count());
        $this->assertDatabaseHas('chatbot_decision_logs', [
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account->id,
            'action' => 'handoff',
            'reason' => 'no_context',
        ]);
    }

    public function test_generate_ai_reply_sends_reply_when_relevant_knowledge_exists(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Paket Growth cocok untuk kebutuhan outlet Anda.']],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 14,
                    'total_tokens' => 26,
                ],
            ], 200),
        ]);

        $account = ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Sales Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'rag_enabled' => true,
            'metadata' => [
                'bot_config' => [
                    'human_handoff_ack_enabled' => false,
                    'minimum_context_score' => 4,
                    'allowed_channels' => ['social_dm'],
                ],
            ],
        ]);

        $document = ChatbotKnowledgeDocument::query()->create([
            'tenant_id' => 1,
            'chatbot_account_id' => $account->id,
            'title' => 'Paket Omnichannel Growth',
            'content' => 'Paket Growth cocok untuk outlet dan tim CS kecil yang butuh WhatsApp dan AI.',
            'metadata' => [
                'status' => 'active',
                'priority' => 10,
                'category' => 'pricing',
            ],
        ]);
        $document->chunks()->create([
            'chatbot_account_id' => $account->id,
            'chunk_index' => 0,
            'content' => 'Paket Growth cocok untuk outlet dan tim CS kecil yang butuh WhatsApp dan AI.',
            'content_length' => 76,
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'cust-2',
            'contact_name' => 'Customer Two',
            'status' => 'open',
        ]);

        $incoming = ConversationMessage::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => 'text',
            'body' => 'paket growth untuk outlet cocok?',
            'status' => 'delivered',
        ]);

        (new GenerateAiReply($conversation->id, $incoming->id, $account->id))->handle();

        $conversation->refresh();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        $reply = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'out')
            ->latest('id')
            ->first();

        $this->assertNotNull($reply);
        $this->assertSame('reply_sent', $metadata['bot_last_decision'] ?? null);
        $this->assertSame('ai_auto_reply', data_get($reply->payload, 'reply_source'));
        $this->assertSame([$document->id], data_get($reply->payload, 'knowledge_document_ids'));
        $this->assertDatabaseHas('chatbot_decision_logs', [
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account->id,
            'action' => 'reply_sent',
            'reason' => 'reply_ready',
        ]);
    }

    public function test_social_webhook_keyword_request_human_uses_same_handoff_policy(): void
    {
        Queue::fake();

        $account = ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Social Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'operation_mode' => 'ai_then_human',
            'metadata' => [
                'bot_config' => [
                    'human_handoff_ack_enabled' => false,
                    'allowed_channels' => ['social_dm'],
                ],
            ],
        ]);

        $socialAccount = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'facebook',
            'name' => 'Page A',
            'access_token' => 'valid-token',
            'status' => 'active',
        ]);

        SocialAccountChatbotIntegration::query()->create([
            'tenant_id' => 1,
            'social_account_id' => $socialAccount->id,
            'auto_reply' => true,
            'chatbot_account_id' => $account->id,
        ]);

        $this->postJson('/social-media/webhook', [
            'token' => 'valid-token',
            'platform' => 'facebook',
            'contact_id' => 'user-1',
            'message' => 'tolong hubungkan saya ke admin',
            'direction' => 'in',
        ])->assertStatus(200);

        $conversation = Conversation::query()
            ->where('channel', 'social_dm')
            ->where('contact_external_id', 'user-1')
            ->first();

        $this->assertNotNull($conversation);
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $this->assertSame('user_requested_human', $metadata['handoff_reason'] ?? null);
        $this->assertTrue((bool) ($metadata['auto_reply_paused'] ?? false));
        Queue::assertNotPushed(GenerateAiReply::class);
        $this->assertDatabaseHas('chatbot_decision_logs', [
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account->id,
            'action' => 'handoff',
            'reason' => 'user_requested_human',
        ]);
    }

    public function test_social_webhook_handoffs_when_max_bot_reply_limit_is_reached(): void
    {
        Queue::fake();

        $account = ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Limited Bot',
            'access_scope' => 'public',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'operation_mode' => 'ai_then_human',
            'metadata' => [
                'bot_config' => [
                    'human_handoff_ack_enabled' => false,
                    'allowed_channels' => ['social_dm'],
                    'max_bot_replies_per_conversation' => 1,
                ],
            ],
        ]);

        $socialAccount = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'facebook',
            'name' => 'Page B',
            'access_token' => 'valid-token-limit',
            'status' => 'active',
        ]);

        SocialAccountChatbotIntegration::query()->create([
            'tenant_id' => 1,
            'social_account_id' => $socialAccount->id,
            'auto_reply' => true,
            'chatbot_account_id' => $account->id,
        ]);

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => $socialAccount->id,
            'contact_external_id' => 'user-limit',
            'contact_name' => 'Customer Limit',
            'status' => 'open',
            'metadata' => [
                'bot_reply_count' => 1,
            ],
        ]);

        $this->postJson('/social-media/webhook', [
            'token' => 'valid-token-limit',
            'platform' => 'facebook',
            'contact_id' => 'user-limit',
            'message' => 'masih ada orang di sana?',
            'direction' => 'in',
        ])->assertStatus(200);

        $conversation->refresh();
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];

        $this->assertSame('max_replies_reached', $metadata['handoff_reason'] ?? null);
        $this->assertTrue((bool) ($metadata['auto_reply_paused'] ?? false));
        Queue::assertNotPushed(GenerateAiReply::class);
        $this->assertDatabaseHas('chatbot_decision_logs', [
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account->id,
            'action' => 'handoff',
            'reason' => 'max_replies_reached',
        ]);
    }

    public function test_social_webhook_skips_private_chatbot_even_if_integration_exists(): void
    {
        Queue::fake();

        $account = ChatbotAccount::query()->create([
            'tenant_id' => 1,
            'name' => 'Internal Bot',
            'access_scope' => 'private',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test',
            'status' => 'active',
            'metadata' => [
                'bot_config' => [
                    'human_handoff_ack_enabled' => false,
                    'allowed_channels' => ['social_dm'],
                ],
            ],
        ]);

        $socialAccount = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'facebook',
            'name' => 'Page Internal',
            'access_token' => 'valid-private-token',
            'status' => 'active',
        ]);

        SocialAccountChatbotIntegration::query()->create([
            'tenant_id' => 1,
            'social_account_id' => $socialAccount->id,
            'auto_reply' => true,
            'chatbot_account_id' => $account->id,
        ]);

        $this->postJson('/social-media/webhook', [
            'token' => 'valid-private-token',
            'platform' => 'facebook',
            'contact_id' => 'user-private',
            'message' => 'halo bot internal',
            'direction' => 'in',
        ])->assertStatus(200);

        $conversation = Conversation::query()
            ->where('channel', 'social_dm')
            ->where('contact_external_id', 'user-private')
            ->first();

        $this->assertNotNull($conversation);
        Queue::assertNotPushed(GenerateAiReply::class);
        $this->assertDatabaseMissing('chatbot_decision_logs', [
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account->id,
        ]);
    }
}
