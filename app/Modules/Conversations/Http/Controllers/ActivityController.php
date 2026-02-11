<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationActivityLog;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function index(Conversation $conversation): JsonResponse
    {
        $logs = ConversationActivityLog::with('user')
            ->where('conversation_id', $conversation->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($logs);
    }
}
