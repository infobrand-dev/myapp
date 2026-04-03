<?php

namespace Tests\Feature\SocialMedia;

use App\Modules\SocialMedia\Services\XAccountActivityPayloadParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class XAccountActivityPayloadParserTest extends TestCase
{
    public function test_parses_incoming_x_message_create_event(): void
    {
        $payload = [
            'for_user_id' => '111',
            'direct_message_events' => [
                [
                    'event_type' => 'MessageCreate',
                    'id' => 'evt-1',
                    'dm_conversation_id' => '111-222',
                    'sender_id' => '222',
                    'text' => 'Halo dari X',
                    'attachments' => [
                        'media_keys' => ['3_123'],
                    ],
                ],
            ],
        ];

        $events = app(XAccountActivityPayloadParser::class)->parse($payload);

        $this->assertCount(1, $events);
        $this->assertSame('x', $events[0]['provider']);
        $this->assertSame('in', $events[0]['direction']);
        $this->assertSame('222', $events[0]['contact_id']);
        $this->assertSame('Halo dari X', $events[0]['text']);
        $this->assertSame(['3_123'], $events[0]['attachment_media_keys']);
    }

    public function test_parses_outgoing_x_message_create_event_using_for_user_id(): void
    {
        $payload = [
            'for_user_id' => '111',
            'direct_message_events' => [
                [
                    'event_type' => 'MessageCreate',
                    'id' => 'evt-2',
                    'dm_conversation_id' => '111-333',
                    'sender_id' => '111',
                    'text' => 'Pesan keluar',
                ],
            ],
        ];

        $events = app(XAccountActivityPayloadParser::class)->parse($payload);

        $this->assertCount(1, $events);
        $this->assertSame('out', $events[0]['direction']);
        $this->assertSame('333', $events[0]['contact_id']);
    }

    public function test_rejects_payload_without_supported_dm_events(): void
    {
        $this->expectException(ValidationException::class);

        app(XAccountActivityPayloadParser::class)->parse([
            'for_user_id' => '111',
            'favorite_events' => [
                ['id' => 'fav-1'],
            ],
        ]);
    }
}
