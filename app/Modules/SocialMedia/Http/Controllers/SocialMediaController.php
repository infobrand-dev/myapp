<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Http\Requests\ReplySocialConversationRequest;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialMediaController extends Controller
{
    public function index(): View
    {
        $conversations = Conversation::query()
            ->where('channel', 'social_dm')
            ->with('owner')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('socialmedia::conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        abort_unless($conversation->channel === 'social_dm', 404);

        $messages = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with('user')
            ->orderBy('id')
            ->get();

        $botPaused = !empty($conversation->metadata['auto_reply_paused']);

        return view('socialmedia::conversations.show', compact('conversation', 'messages', 'botPaused'));
    }

    public function reply(ReplySocialConversationRequest $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'social_dm', 404);

        $message = ConversationMessage::create([
            'tenant_id' => TenantContext::currentId(),
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => $request->input('body'),
            'status' => 'pending',
        ]);

        $conversation->update(['last_message_at' => now(), 'last_outgoing_at' => now()]);

        SendSocialMessage::dispatch($message->id);

        return redirect()->route('social-media.conversations.show', $conversation)
            ->with('success', 'Pesan terkirim.');
    }

    public function resumeBot(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'social_dm', 404);
        $this->authorizeBotControl($conversation);

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['needs_human'] = false;
        $metadata['auto_reply_paused'] = false;
        unset($metadata['handoff_reason'], $metadata['handoff_at']);

        $conversation->update([
            'metadata' => $metadata,
        ]);

        return back()->with('status', 'Bot dilanjutkan untuk conversation social ini.');
    }

    public function pauseBot(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->channel === 'social_dm', 404);
        $this->authorizeBotControl($conversation);

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['needs_human'] = true;
        $metadata['auto_reply_paused'] = true;
        $metadata['handoff_reason'] = 'manual_pause';
        $metadata['handoff_at'] = now()->toDateTimeString();

        $conversation->update([
            'metadata' => $metadata,
        ]);

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
