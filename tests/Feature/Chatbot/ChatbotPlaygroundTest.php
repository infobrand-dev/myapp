<?php

namespace Tests\Feature\Chatbot;

use App\Models\User;
use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Jobs\MirrorPlaygroundTurnToConversation;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotMessage;
use App\Modules\Chatbot\Models\ChatbotSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatbotPlaygroundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ChatbotServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Chatbot/database/migrations',
            '--force' => true,
        ]);
    }

    public function test_send_creates_session_and_assistant_reply()
    {
        Queue::fake();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Halo, ada yang bisa saya bantu?']],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 9,
                    'total_tokens' => 21,
                ],
            ], 200),
        ]);

        $user = $this->superAdminUser();
        $account = ChatbotAccount::create([
            'name' => 'Main Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test-key',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/chatbot/playground/send', [
            'chatbot_account_id' => $account->id,
            'message' => 'Halo bot',
        ]);

        $session = ChatbotSession::query()->first();

        $response->assertRedirect('/chatbot/playground/' . $session->id);
        $this->assertNotNull($session);

        $this->assertDatabaseHas('chatbot_sessions', [
            'id' => $session->id,
            'user_id' => $user->id,
            'chatbot_account_id' => $account->id,
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Halo bot',
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Halo, ada yang bisa saya bantu?',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_send_uses_fallback_when_openai_fails()
    {
        Queue::fake();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Rate limit hit'],
            ], 429),
        ]);

        $user = $this->superAdminUser();
        $account = ChatbotAccount::create([
            'name' => 'Fallback Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test-key',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/chatbot/playground/send', [
            'chatbot_account_id' => $account->id,
            'message' => 'Tes fallback',
        ]);

        $response->assertSessionHas('status');

        $assistant = ChatbotMessage::query()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $this->assertNotNull($assistant);
        $this->assertSame('Maaf, saya belum bisa memproses permintaan ini sekarang.', $assistant->content);

        Queue::assertNothingPushed();
    }

    public function test_user_cannot_send_to_other_users_session()
    {
        Queue::fake();

        $owner = $this->superAdminUser();
        $other = $this->superAdminUser();
        $account = ChatbotAccount::create([
            'name' => 'Private Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test-key',
            'status' => 'active',
        ]);

        $session = ChatbotSession::create([
            'chatbot_account_id' => $account->id,
            'user_id' => $owner->id,
            'title' => 'Owner Session',
            'last_message_at' => now(),
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Should not be used']],
                ],
            ], 200),
        ]);

        $this->actingAs($other)
            ->post('/chatbot/playground/send', [
                'chatbot_account_id' => $account->id,
                'session_id' => $session->id,
                'message' => 'Hack attempt',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('chatbot_messages', [
            'session_id' => $session->id,
            'content' => 'Hack attempt',
        ]);
    }

    public function test_send_dispatches_mirror_job_when_toggle_is_on()
    {
        Queue::fake();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Mirrored answer']],
                ],
            ], 200),
        ]);

        $user = $this->superAdminUser();
        $account = ChatbotAccount::create([
            'name' => 'Mirror Bot',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test-key',
            'status' => 'active',
            'mirror_to_conversations' => true,
        ]);

        $this->actingAs($user)->post('/chatbot/playground/send', [
            'chatbot_account_id' => $account->id,
            'message' => 'Mirror this',
        ])->assertRedirect();

        Queue::assertPushed(MirrorPlaygroundTurnToConversation::class, function ($job) use ($account, $user) {
            return (int) $job->chatbotAccountId === (int) $account->id
                && (int) $job->userId === (int) $user->id
                && count($job->chatbotMessageIds) === 2;
        });
    }

    private function superAdminUser(): User
    {
        $user = User::factory()->create();
        Role::findOrCreate('Super-admin');
        $user->assignRole('Super-admin');

        return $user;
    }
}
