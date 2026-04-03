<?php

namespace Tests\Feature\SocialMedia;

use App\Modules\SocialMedia\Services\XWebhookSecurity;
use Tests\TestCase;

class XWebhookSecurityTest extends TestCase
{
    public function test_build_crc_response_matches_x_hmac_contract(): void
    {
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $response = app(XWebhookSecurity::class)->buildCrcResponse('challenge_string');

        $this->assertSame(
            'sha256=' . base64_encode(hash_hmac('sha256', 'challenge_string', 'consumer-secret', true)),
            $response['response_token']
        );
    }

    public function test_verify_signature_matches_x_signature_contract(): void
    {
        config(['services.x_api.client_secret' => 'consumer-secret']);

        $rawBody = '{"for_user_id":"123","direct_message_events":[{"id":"evt-1"}]}';
        $signature = 'sha256=' . base64_encode(hash_hmac('sha256', $rawBody, 'consumer-secret', true));

        $this->assertTrue(app(XWebhookSecurity::class)->verifySignature($rawBody, $signature));
        $this->assertFalse(app(XWebhookSecurity::class)->verifySignature($rawBody, 'sha256=invalid'));
    }
}
