<?php

namespace Tests\Feature\SocialMedia;

use App\Modules\SocialMedia\Services\XDirectMessageClient;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class XDirectMessageClientTest extends TestCase
{
    public function test_build_message_body_requires_text_or_media(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('X DM requires text or at least one media attachment.');

        app(XDirectMessageClient::class)->buildMessageBody('', []);
    }

    public function test_build_message_body_allows_single_media_attachment(): void
    {
        $payload = app(XDirectMessageClient::class)->buildMessageBody('Hello X', ['1583157113245011970']);

        $this->assertSame('Hello X', $payload['text']);
        $this->assertSame('1583157113245011970', data_get($payload, 'attachments.0.media_id'));
    }

    public function test_build_message_body_rejects_more_than_one_attachment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('X DM supports only one media attachment per message.');

        app(XDirectMessageClient::class)->buildMessageBody('Hello X', ['1', '2']);
    }

    public function test_send_text_to_participant_uses_official_x_dm_endpoint(): void
    {
        Http::fake([
            '*' => Http::response(['data' => ['dm_conversation_id' => '123']], 200),
        ]);

        $client = app(XDirectMessageClient::class);
        $response = $client->sendTextToParticipant('user-token', '9876543210', 'Hello!');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.x.com/2/dm_conversations/with/9876543210/messages'
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === 'Bearer user-token'
                && data_get($request->data(), 'text') === 'Hello!';
        });
    }

    public function test_send_to_conversation_uses_official_x_conversation_endpoint(): void
    {
        Http::fake([
            '*' => Http::response(['data' => ['event_id' => 'evt-1']], 200),
        ]);

        $client = app(XDirectMessageClient::class);
        $response = $client->sendToConversation('user-token', '1582103724607971328', 'Another message');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.x.com/2/dm_conversations/1582103724607971328/messages'
                && $request->method() === 'POST'
                && data_get($request->data(), 'text') === 'Another message';
        });
    }
}
