<?php

namespace Tests\Feature\Conversations;

use App\Models\User;
use App\Http\Middleware\EnsureTenantFeature;
use App\Modules\Conversations\ConversationsServiceProvider;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class ConversationInboxHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ConversationsServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Conversations/database/migrations',
            '--force' => true,
        ]);

        $this->withoutMiddleware(PermissionMiddleware::class);
        $this->withoutMiddleware(EnsureTenantFeature::class);

        Role::findOrCreate('Super-admin', 'web');
    }

    public function test_mark_read_clears_only_current_user_unread_count(): void
    {
        $owner = User::query()->create([
            'tenant_id' => 1,
            'name' => 'Owner Agent',
            'email' => 'owner@example.test',
            'password' => bcrypt('secret'),
        ]);
        $owner->assignRole('Super-admin');

        $collaborator = User::query()->create([
            'tenant_id' => 1,
            'name' => 'Collaborator Agent',
            'email' => 'collab@example.test',
            'password' => bcrypt('secret'),
        ]);
        $collaborator->assignRole('Super-admin');

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'social_dm',
            'instance_id' => 1,
            'contact_external_id' => 'cust-1',
            'contact_name' => 'Customer One',
            'status' => 'open',
            'owner_id' => $owner->id,
            'unread_count' => 3,
        ]);

        ConversationParticipant::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'unread_count' => 2,
            'invited_at' => now(),
        ]);

        ConversationParticipant::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'user_id' => $collaborator->id,
            'role' => 'collaborator',
            'unread_count' => 1,
            'invited_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('conversations.read', $conversation))
            ->assertOk();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'unread_count' => 0,
        ]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $collaborator->id,
            'unread_count' => 1,
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'unread_count' => 3,
        ]);
    }

    public function test_send_marks_message_failed_when_channel_has_no_outbound_dispatcher(): void
    {
        $user = User::query()->create([
            'tenant_id' => 1,
            'name' => 'Inbox Agent',
            'email' => 'agent@example.test',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole('Super-admin');

        $conversation = Conversation::query()->create([
            'tenant_id' => 1,
            'channel' => 'internal',
            'instance_id' => 0,
            'contact_external_id' => 'internal-1-2',
            'contact_name' => 'Internal Chat',
            'status' => 'open',
            'owner_id' => $user->id,
        ]);

        ConversationParticipant::query()->create([
            'tenant_id' => 1,
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'invited_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('conversations.send', $conversation), [
                'body' => 'Halo internal',
            ])
            ->assertStatus(302);

        $message = ConversationMessage::query()->where('conversation_id', $conversation->id)->latest('id')->first();

        $this->assertNotNull($message);
        $this->assertSame('failed', $message->status);
        $this->assertSame('Outbound dispatcher tidak tersedia untuk channel ini.', $message->error_message);
    }
}
