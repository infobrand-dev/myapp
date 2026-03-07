<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialMediaController extends Controller
{
    public function index(): View
    {
        return view('socialmedia::index');
    }

    public function resumeBot(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'social_dm', 404);
        $this->authorizeBotControl($conversation);

        SocialWebhookController::resumeBotForConversation($conversation);

        return back()->with('status', 'Bot dilanjutkan untuk conversation social ini.');
    }

    public function pauseBot(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'social_dm', 404);
        $this->authorizeBotControl($conversation);

        SocialWebhookController::pauseBotForConversation($conversation, 'manual_pause');

        return back()->with('status', 'Bot dipause untuk conversation social ini.');
    }

    private function authorizeBotControl(Conversation $conversation): void
    {
        /** @var User $user */
        $user = auth()->user();
        $isOwner = (int) ($conversation->owner_id ?? 0) === (int) $user->id;
        $isSuperAdmin = $user->hasRole('Super-admin');
        abort_unless($isOwner || $isSuperAdmin, 403);
    }
}
