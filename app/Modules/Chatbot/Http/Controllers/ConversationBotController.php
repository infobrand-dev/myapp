<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Chatbot\Services\ConversationBotManager;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Http\RedirectResponse;

class ConversationBotController extends Controller
{
    public function pause(Conversation $conversation, ConversationBotManager $manager): RedirectResponse
    {
        abort_unless($manager->hasConnectedBot($conversation), 404);
        $this->authorizeBotControl($conversation);

        $manager->pause($conversation, 'manual_pause');

        return back()->with('status', 'AI bot dipause untuk conversation ini.');
    }

    public function resume(Conversation $conversation, ConversationBotManager $manager): RedirectResponse
    {
        abort_unless($manager->hasConnectedBot($conversation), 404);
        $this->authorizeBotControl($conversation);

        $manager->resume($conversation);

        return back()->with('status', 'AI bot dilanjutkan untuk conversation ini.');
    }

    private function authorizeBotControl(Conversation $conversation): void
    {
        /** @var User $user */
        $user = auth()->user();
        $isOwner = (int) ($conversation->owner_id ?? 0) === (int) $user->id;
        $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('Super-admin');

        abort_unless($isOwner || $isSuperAdmin, 403);
    }
}
