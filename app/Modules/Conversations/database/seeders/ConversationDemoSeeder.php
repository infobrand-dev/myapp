<?php

namespace App\Modules\Conversations\database\seeders;

use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

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

        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'channel' => 'internal',
                'instance_id' => 0,
                'contact_external_id' => 'internal-demo-' . $super->id,
            ],
            [
                'tenant_id' => $this->tenantId,
                'contact_name' => 'Demo Internal Chat',
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
            $this->command->info('Conversation demo data seeded.');
        }
    }
}


