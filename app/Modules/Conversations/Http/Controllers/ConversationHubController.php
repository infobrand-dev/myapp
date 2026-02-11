<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\Conversations\Models\ConversationActivityLog;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationHubController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $request->validate([
            'query' => ['required', 'string'],
        ]);

        $me = $request->user();
        $q = $request->input('query');
        $other = User::where('id', $q)
            ->orWhere('email', $q)
            ->orWhere('name', $q)
            ->first();

        if (!$other || $other->id === $me->id) {
            return back()->with('status', 'User tidak ditemukan / sama dengan diri sendiri.');
        }

        $otherId = $other->id;
        $otherName = $other->name;

        // Buat key unik berurutan agar percakapan internal tidak dobel
        $pair = collect([$me->id, $otherId])->sort()->implode('-');
        $contactKey = 'internal-' . $pair;

        $conversation = Conversation::firstOrCreate(
            [
                'channel' => 'internal',
                'instance_id' => null,
                'contact_wa_id' => $contactKey,
            ],
            [
                'contact_name' => $otherName,
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );

        ConversationParticipant::firstOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $me->id],
            ['role' => 'owner', 'invited_at' => now(), 'invited_by' => $me->id]
        );
        ConversationParticipant::firstOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $otherId],
            ['role' => 'collaborator', 'invited_at' => now(), 'invited_by' => $me->id]
        );

        $conversation->update([
            'owner_id' => $conversation->owner_id ?? $me->id,
            'claimed_at' => $conversation->claimed_at ?? now(),
            'locked_until' => now()->addMinutes(config('modules.whatsapp_api.lock_minutes', 30)),
        ]);

        $this->log($conversation, $me->id, 'start_internal', "Start with user {$otherId}");

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'Percakapan internal dibuat.');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);

        $query = $this->baseQuery($user);

        $first = (clone $query)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->first();

        if ($first) {
            return redirect()->route('conversations.show', $first);
        }

        $conversations = $query->orderByDesc('last_message_at')
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

        $conversationsList = $this->baseQuery($user)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);

        return view('conversations::show', [
            'conversation' => $conversation,
            'conversationsList' => $conversationsList,
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

        $this->log($conversation, $user->id, 'claim', 'Percakapan diklaim');

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

        $this->log($conversation, $user->id, 'release', 'Lock dilepas');

        return back()->with('status', 'Lock dilepas.');
    }

    public function invite(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();

        if ($conversation->owner_id !== $user->id && !$user->hasRole('Super-admin')) {
            return back()->with('status', 'Hanya pemilik atau super-admin yang dapat mengundang.');
        }

        $data = $request->validate([
            'query' => ['required', 'string'],
            'role' => ['nullable', 'string', 'max:50'],
        ]);

        $invitee = User::where('id', $data['query'])
            ->orWhere('email', $data['query'])
            ->orWhere('name', $data['query'])
            ->first();

        if (!$invitee) {
            return back()->with('status', 'User tidak ditemukan.');
        }

        $role = $data['role'] ?? 'collaborator';

        ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $invitee->id],
            ['role' => $role, 'invited_at' => now(), 'invited_by' => $user->id, 'left_at' => null]
        );

        $this->log($conversation, $user->id, 'invite', "Invite user {$invitee->id}");

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

        $this->log($conversation, $user->id, 'send', 'Kirim pesan');

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

    private function baseQuery(User $user)
    {
        return Conversation::with(['owner', 'instance'])
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id))
                        ->orWhereHas('instance.users', fn ($p) => $p->where('users.id', $user->id));
                });
            });
    }

    private function log(Conversation $conversation, ?int $userId, string $action, ?string $detail = null): void
    {
        ConversationActivityLog::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'action' => $action,
            'detail' => $detail,
        ]);
    }
}
