<?php

namespace App\Modules\Conversations\Database\Seeders;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class ConversationSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        $conversation = Conversation::query()->updateOrCreate(
            [
                'channel' => 'internal',
                'instance_id' => null,
                'contact_external_id' => 'internal-demo-contact',
            ],
            [
                'external_id' => 'conv-demo-001',
                'contact_name' => 'Demo Internal Contact',
                'status' => 'open',
                'owner_id' => $userId,
                'claimed_at' => now()->subMinutes(15),
                'locked_until' => now()->addMinutes(30),
                'last_message_at' => now()->subMinutes(2),
                'last_incoming_at' => now()->subMinutes(5),
                'last_outgoing_at' => now()->subMinutes(2),
                'unread_count' => 0,
                'metadata' => ['source' => 'sample_data'],
            ]
        );

        if ($user) {
            ConversationParticipant::query()->updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'owner',
                    'invited_by' => $user->id,
                    'invited_at' => now()->subMinutes(20),
                ]
            );
        }

        ConversationMessage::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'direction' => 'in',
                'body' => 'Halo tim, saya ingin follow up proposal kerja sama.',
            ],
            [
                'type' => 'text',
                'status' => 'delivered',
                'payload' => ['seed' => true],
                'created_at' => now()->subMinutes(5),
            ]
        );

        ConversationMessage::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'direction' => 'out',
                'body' => 'Baik, proposal terbaru akan kami kirim hari ini.',
            ],
            [
                'user_id' => $userId,
                'type' => 'text',
                'status' => 'sent',
                'payload' => ['seed' => true],
                'created_at' => now()->subMinutes(2),
            ]
        );
    }
}
