<?php

namespace App\Modules\Conversations\Database\Seeders;

use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConversationDemoSeeder extends Seeder
{
    private int $tenantId;

    public function run(): void
    {
        $this->tenantId = TenantContext::currentId();
        $super = User::first();
        if (!$super) {
            if ($this->command) {
                $this->command->warn('No user found, skipping conversation demo seeder.');
            }
            return;
        }

        $instance = WhatsAppInstance::firstOrCreate(
            ['tenant_id' => $this->tenantId, 'name' => 'Demo WA API'],
            [
                'tenant_id' => $this->tenantId,
                'provider' => 'demo',
                'api_base_url' => 'https://wa-demo.test/api',
                'api_token' => Str::random(32),
                'status' => 'connected',
                'is_active' => true,
                'created_by' => $super->id,
                'updated_by' => $super->id,
            ]
        );

        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'channel' => 'wa_api',
                'instance_id' => $instance->id,
                'contact_external_id' => '628123456789',
            ],
            [
                'tenant_id' => $this->tenantId,
                'contact_name' => 'Demo Contact',
                'status' => 'open',
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'unread_count' => 1,
            ]
        );

        ConversationMessage::firstOrCreate(
            ['tenant_id' => $this->tenantId, 'conversation_id' => $conversation->id, 'direction' => 'in', 'body' => 'Hai, ini pesan demo!'],
            [
                'tenant_id' => $this->tenantId,
                'status' => 'delivered',
                'type' => 'text',
                'payload' => ['seed' => true],
                'created_at' => now()->subMinutes(3),
            ]
        );

        ConversationMessage::firstOrCreate(
            ['tenant_id' => $this->tenantId, 'conversation_id' => $conversation->id, 'direction' => 'out', 'body' => 'Halo, ada yang bisa dibantu?'],
            [
                'tenant_id' => $this->tenantId,
                'status' => 'sent',
                'type' => 'text',
                'user_id' => $super->id,
                'created_at' => now()->subMinute(),
            ]
        );

        $conversation->update([
            'owner_id' => $super->id,
            'claimed_at' => now()->subMinutes(5),
            'locked_until' => now()->addMinutes(25),
            'last_outgoing_at' => now()->subMinute(),
            'last_message_at' => now()->subMinute(),
            'unread_count' => 0,
        ]);

        if ($this->command) {
            $this->command->info('Conversation demo data seeded. Token: ' . $instance->api_token);
        }
    }
}
