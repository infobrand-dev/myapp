<?php

namespace Tests\Feature\LiveChat;

use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\LiveChat\LiveChatServiceProvider;
use App\Modules\LiveChat\Models\LiveChatWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ConversationsServiceProvider::class);
        $this->app->register(LiveChatServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Conversations/database/migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/LiveChat/database/migrations',
            '--force' => true,
        ]);
    }

    public function test_bootstrap_creates_or_resolves_visitor_key()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-1',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $response = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/pricing',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['visitor_key', 'visitor_token', 'conversation' => ['id', 'status'], 'widget' => ['name']]);

        $this->assertNotEmpty($response->json('visitor_key'));
        $this->assertNotEmpty($response->json('visitor_token'));
    }

    public function test_incoming_widget_message_is_saved_to_conversation()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-2',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'visitor_name' => 'Budi',
                'page_url' => 'https://example.com/contact',
            ])->assertOk();

        $visitorKey = (string) $bootstrap->json('visitor_key');
        $visitorToken = (string) $bootstrap->json('visitor_token');

        $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/messages', [
                'visitor_key' => $visitorKey,
                'visitor_token' => $visitorToken,
                'visitor_name' => 'Budi',
                'body' => 'Halo admin',
                'page_url' => 'https://example.com/contact',
            ])->assertOk()
            ->assertJson(['stored' => true]);

        $conversation = Conversation::query()
            ->where('channel', 'live_chat')
            ->where('instance_id', $widget->id)
            ->where('contact_external_id', $visitorKey)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame('Budi', $conversation->contact_name);
        $this->assertSame(
            1,
            ConversationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('direction', 'in')
                ->where('body', 'Halo admin')
                ->count()
        );
    }

    public function test_widget_can_poll_agent_replies()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-3',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/help',
            ])->assertOk();

        $visitorKey = (string) $bootstrap->json('visitor_key');
        $visitorToken = (string) $bootstrap->json('visitor_token');

        $conversation = Conversation::query()
            ->where('channel', 'live_chat')
            ->where('instance_id', $widget->id)
            ->where('contact_external_id', $visitorKey)
            ->firstOrFail();

        $reply = ConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => 'Halo, kami bantu ya.',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->withHeader('Origin', 'https://example.com')
            ->getJson('/live-chat/api/' . $widget->widget_token . '/messages?visitor_key=' . urlencode($visitorKey) . '&visitor_token=' . urlencode($visitorToken))
            ->assertOk()
            ->assertJson([
                'latest_id' => $reply->id,
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => 'open',
                ],
                'messages' => [
                    [
                        'id' => $reply->id,
                        'direction' => 'out',
                        'body' => 'Halo, kami bantu ya.',
                    ],
                ],
            ]);
    }

    public function test_messages_require_valid_visitor_token()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-4',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/help',
            ])->assertOk();

        $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/messages', [
                'visitor_key' => $bootstrap->json('visitor_key'),
                'visitor_token' => 'invalid-token',
                'body' => 'Halo',
            ])->assertStatus(403);
    }

    public function test_new_visitor_message_reopens_closed_conversation()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-6',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/help',
            ])->assertOk();

        $conversation = Conversation::query()
            ->where('channel', 'live_chat')
            ->where('instance_id', $widget->id)
            ->where('contact_external_id', (string) $bootstrap->json('visitor_key'))
            ->firstOrFail();

        $conversation->update(['status' => 'closed']);

        $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/messages', [
                'visitor_key' => $bootstrap->json('visitor_key'),
                'visitor_token' => $bootstrap->json('visitor_token'),
                'body' => 'Halo, buka lagi ya',
            ])->assertOk()
            ->assertJson([
                'stored' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => 'open',
                ],
            ]);

        $conversation->refresh();
        $this->assertSame('open', $conversation->status);
    }

    public function test_bootstrap_rejects_origin_when_allowed_domains_missing()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-5',
            'is_active' => true,
        ]);

        $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/help',
            ])->assertStatus(403);
    }

    public function test_widget_can_receive_sse_updates()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-7',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/sse',
            ])->assertOk();

        $conversation = Conversation::query()
            ->where('channel', 'live_chat')
            ->where('instance_id', $widget->id)
            ->where('contact_external_id', (string) $bootstrap->json('visitor_key'))
            ->firstOrFail();

        $reply = ConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => 'Update realtime',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->withHeader('Origin', 'https://example.com')
            ->get('/live-chat/api/' . $widget->widget_token . '/events?visitor_key=' . urlencode((string) $bootstrap->json('visitor_key')) . '&visitor_token=' . urlencode((string) $bootstrap->json('visitor_token')) . '&after_id=0&wait_seconds=0');

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('content-type'));
        $response->assertSee('conversation.update', false);
        $response->assertSee('Update realtime', false);
        $response->assertSee((string) $reply->id, false);
    }

    public function test_typing_endpoint_marks_visitor_typing_in_event_payload()
    {
        $widget = LiveChatWidget::query()->create([
            'name' => 'Main Website',
            'widget_token' => 'widget-token-8',
            'allowed_domains' => ['example.com'],
            'is_active' => true,
        ]);

        $bootstrap = $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/bootstrap', [
                'page_url' => 'https://example.com/typing',
            ])->assertOk();

        $this->withHeader('Origin', 'https://example.com')
            ->postJson('/live-chat/api/' . $widget->widget_token . '/typing', [
                'visitor_key' => $bootstrap->json('visitor_key'),
                'visitor_token' => $bootstrap->json('visitor_token'),
            ])->assertOk()
            ->assertJson(['ok' => true]);

        $response = $this->withHeader('Origin', 'https://example.com')
            ->get('/live-chat/api/' . $widget->widget_token . '/events?visitor_key=' . urlencode((string) $bootstrap->json('visitor_key')) . '&visitor_token=' . urlencode((string) $bootstrap->json('visitor_token')) . '&after_id=0&wait_seconds=0');

        $response->assertOk();
        $response->assertSee('"visitor":true', false);
    }
}
