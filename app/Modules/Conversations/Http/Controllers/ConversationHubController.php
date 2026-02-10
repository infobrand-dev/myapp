<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationHubController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);

        $conversations = Conversation::with(['owner'])
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id))
                        ->orWhereHas('instance.users', fn ($p) => $p->where('users.id', $user->id));
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('conversations::index', compact('conversations', 'lockMinutes'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);

        $conversation->load(['owner', 'participants.user', 'messages' => function ($q) {
            $q->orderBy('created_at');
        }]);

        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);

        return view('conversations::show', [
            'conversation' => $conversation,
            'lockMinutes' => $lockMinutes,
        ]);
    }

    public function claim(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);
        $now = now();

        $isLocked = $conversation->owner_id && $conversation->owner_id !== $user->id && $conversation->locked_until && $conversation->locked_until->isFuture();
        if ($isLocked) {
            return back()->with('status', 'Percakapan sedang diklaim orang lain.');
        }

        $conversation->update([
            'owner_id' => $user->id,
            'claimed_at' => $now,
            'locked_until' => $now->copy()->addMinutes($lockMinutes),
        ]);

        ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['role' => 'owner', 'invited_at' => $now, 'invited_by' => $user->id]
        );

        return back()->with('status', 'Percakapan berhasil diklaim.');
    }

    public function release(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();

        if ($conversation->owner_id && $conversation->owner_id !== $user->id && !$user->hasRole('Super-admin')) {
            return back()->with('status', 'Hanya pemilik atau super-admin yang dapat merilis.');
        }

        $conversation->update([
            'owner_id' => null,
            'claimed_at' => null,
            'locked_until' => null,
        ]);

        return back()->with('status', 'Lock dilepas.');
    }

    public function invite(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();

        if ($conversation->owner_id !== $user->id && !$user->hasRole('Super-admin')) {
            return back()->with('status', 'Hanya pemilik atau super-admin yang dapat mengundang.');
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'string', 'max:50'],
        ]);

        $invitee = User::findOrFail($data['user_id']);
        $role = $data['role'] ?? 'collaborator';

        ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $invitee->id],
            ['role' => $role, 'invited_at' => now(), 'invited_by' => $user->id, 'left_at' => null]
        );

        return back()->with('status', 'Pengguna diundang.');
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeParticipant($conversation, $user);

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => $data['body'],
            'status' => $conversation->channel === 'wa_api' ? 'queued' : 'sent',
            'sent_at' => $conversation->channel === 'wa_api' ? null : now(),
        ]);

        $message = $conversation->messages()->latest()->first();

        $conversation->update([
            'last_message_at' => now(),
            'last_outgoing_at' => now(),
        ]);

        if ($message) {
            if ($conversation->channel === 'wa_api') {
                SendWhatsAppMessage::dispatch($message->id);
            } elseif ($conversation->channel === 'social_dm') {
                SendSocialMessage::dispatch($message->id);
            }
            broadcast(new ConversationMessageCreated($message))->toOthers();
        }

        return redirect()->route('conversations.show', $conversation)->with('status', 'Pesan terkirim.');
    }

    private function authorizeParticipant(Conversation $conversation, User $user): void
    {
        if ($user->hasRole('Super-admin')) {
            return;
        }

        $allowed = $conversation->owner_id === $user->id
            || $conversation->participants()
                ->where('user_id', $user->id)
                ->exists();

        abort_unless($allowed, 403);
    }

    private function authorizeView(Conversation $conversation, User $user): void
    {
        if ($user->hasRole('Super-admin')) {
            return;
        }

        $allowed = $conversation->owner_id === $user->id
            || $conversation->participants()->where('user_id', $user->id)->exists()
            || ($conversation->instance && $conversation->instance->users()->where('users.id', $user->id)->exists());

        abort_unless($allowed, 403);
    }
}
