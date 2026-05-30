<?php

namespace Tests\Feature\Platform;

use App\Contracts\UtasWebhookNotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UtasWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_webhook_is_stored_and_acknowledged(): void
    {
        config()->set('services.utas.webhook_secret', 'secret-utas');
        config()->set('services.utas.notify_email', 'abugahwa.com@gmail.com');

        $sender = new class implements UtasWebhookNotificationSender
        {
            public array $sent = [];

            public function sendPaidNotification(string $to, array $payload): void
            {
                $this->sent[] = compact('to', 'payload');
            }
        };

        $this->app->instance(UtasWebhookNotificationSender::class, $sender);

        $payload = [
            'state' => 'paid',
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'address' => 'Jakarta',
            'store' => 'Toko Alpha',
            'items' => [
                [
                    'item_name' => 'Produk A',
                    'item_qty' => '2',
                    'item_price' => '150000',
                    'item_data' => 'catatan',
                ],
            ],
            'total' => '300000',
            'store_link' => 'https://utas.co/store-alpha',
        ];

        $this->withHeader('X-Webhook-Secret', 'secret-utas')
            ->postJson(route('webhooks.utas'), $payload)
            ->assertOk()
            ->assertJson([
                'message' => 'OK',
                'state' => 'paid',
                'notified' => true,
            ]);

        $this->assertCount(1, $sender->sent);
        $this->assertSame('abugahwa.com@gmail.com', $sender->sent[0]['to']);
    }

    public function test_complete_webhook_is_supported_without_sending_email(): void
    {
        config()->set('services.utas.notify_email', 'abugahwa.com@gmail.com');

        $sender = new class implements UtasWebhookNotificationSender
        {
            public array $sent = [];

            public function sendPaidNotification(string $to, array $payload): void
            {
                $this->sent[] = compact('to', 'payload');
            }
        };

        $this->app->instance(UtasWebhookNotificationSender::class, $sender);

        $payload = [
            'state' => 'complete',
            'name' => 'Sari',
            'email' => 'sari@example.com',
            'store' => 'Toko Beta',
            'items' => [],
            'total' => '125000',
        ];

        $this->postJson(route('webhooks.utas'), $payload)
            ->assertOk()
            ->assertJson([
                'message' => 'OK',
                'state' => 'complete',
                'notified' => false,
            ]);

        $this->assertSame([], $sender->sent);
    }

    public function test_unsupported_state_is_ignored(): void
    {
        $sender = new class implements UtasWebhookNotificationSender
        {
            public array $sent = [];

            public function sendPaidNotification(string $to, array $payload): void
            {
                $this->sent[] = compact('to', 'payload');
            }
        };

        $this->app->instance(UtasWebhookNotificationSender::class, $sender);

        $this->postJson(route('webhooks.utas'), [
            'state' => 'shipping',
            'name' => 'Beni',
        ])->assertStatus(202)
            ->assertJson([
                'message' => 'State webhook diabaikan.',
                'state' => 'shipping',
            ]);

        $this->assertSame([], $sender->sent);
    }

    public function test_secret_is_enforced_when_configured(): void
    {
        config()->set('services.utas.webhook_secret', 'secret-utas');

        $this->postJson(route('webhooks.utas'), [
            'state' => 'paid',
        ])->assertUnauthorized();
    }
}
