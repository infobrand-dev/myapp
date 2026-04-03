<?php

namespace Tests\Feature\WhatsAppApi;

use App\Models\Tenant;
use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WhatsAppWebhookPayloadRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(WhatsAppApiServiceProvider::class);

        Artisan::call('migrate', [
            '--path' => 'app/Modules/WhatsAppApi/Database/Migrations',
            '--force' => true,
        ]);

        Tenant::query()->create([
            'id' => 1,
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'is_active' => true,
        ]);
    }

    public function test_prune_webhook_payloads_clears_old_headers_and_payload(): void
    {
        $event = WhatsAppWebhookEvent::query()->create([
            'tenant_id' => 1,
            'provider' => 'cloud',
            'event_key' => 'evt-1',
            'headers' => ['x-test' => '1'],
            'payload' => ['entry' => [['id' => '1']]],
            'process_status' => 'processed',
            'received_at' => now()->subDays(20),
        ]);

        $this->artisan('whatsapp:prune-webhook-payloads', ['--days' => 14])
            ->expectsOutputToContain('Pruned raw payload data')
            ->assertSuccessful();

        $event->refresh();

        $this->assertNull($event->headers);
        $this->assertNull($event->payload);
    }
}
