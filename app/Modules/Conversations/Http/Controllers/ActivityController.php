<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationActivityLog;
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
            || app(ConversationAccessRegistry::class)->canView($conversation, $user);

        abort_unless($allowed, 403);
    }
}
