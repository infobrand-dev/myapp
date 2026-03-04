<?php

use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::query()
        ->select(['id', 'owner_id', 'channel', 'instance_id'])
        ->find((int) $conversationId);

    if (!$conversation) {
        return false;
    }

    if ($user->hasRole('Super-admin')) {
        return true;
    }

    if ((int) $conversation->owner_id === (int) $user->id) {
        return true;
    }

    $isParticipant = DB::table('conversation_participants')
        ->where('conversation_id', (int) $conversation->id)
        ->where('user_id', (int) $user->id)
        ->exists();

    if ($isParticipant) {
        return true;
    }

    if ($conversation->channel === 'wa_api'
        && $conversation->instance_id
        && class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstance::class)
        && Schema::hasTable('whatsapp_instances')
        && Schema::hasTable('whatsapp_instance_user')) {
        return DB::table('whatsapp_instance_user')
            ->where('instance_id', (int) $conversation->instance_id)
            ->where('user_id', (int) $user->id)
            ->exists();
    }

    return false;
});
