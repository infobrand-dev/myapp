<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeView($conversation, $request->user());

        $logs = ConversationActivityLog::with('user')
            ->where('conversation_id', $conversation->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    private function authorizeView(Conversation $conversation, User $user): void
    {
        if ($user->hasRole('Super-admin')) {
            return;
        }

        $allowed = $conversation->owner_id === $user->id
            || $conversation->participants()->where('user_id', $user->id)->exists()
            || $this->hasInstanceAccess($conversation, (int) $user->id);

        abort_unless($allowed, 403);
    }

    private function hasInstanceAccess(Conversation $conversation, int $userId): bool
    {
        if ($conversation->channel !== 'wa_api' || !$conversation->instance_id) {
            return false;
        }

        if (!class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstance::class)
            || !Schema::hasTable('whatsapp_instances')
            || !Schema::hasTable('whatsapp_instance_user')) {
            return false;
        }

        return DB::table('whatsapp_instance_user')
            ->where('instance_id', (int) $conversation->instance_id)
            ->where('user_id', $userId)
            ->exists();
    }
}
