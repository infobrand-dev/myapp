<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\WhatsAppApi\Models\WhatsAppConversation;
use App\Modules\WhatsAppApi\Models\WhatsAppConversationParticipant;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $lockMinutes = config('modules.whatsapp_api.lock_minutes', 30);

        $instancesQuery = WhatsAppInstance::query()->where('is_active', true);
        if (!$user->hasRole('Super-admin')) {
            $instancesQuery->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }
        $instances = $instancesQuery->orderBy('name')->get();

        $conversations = WhatsAppConversation::with(['instance', 'owner'])
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->whereHas('instance.users', fn ($q) => $q->where('users.id', $user->id));
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('whatsappapi::conversations.index', compact('conversations', 'instances', 'lockMinutes'));
    }

    public function claim(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($conversation, $user);

        $lockMinutes = (int) config('modules.whatsapp_api.lock_minutes', 30);
        $now = now();

        $lockedUntil = $conversation->locked_until;
        $isLockedByOther = $conversation->owner_id && $conversation->owner_id !== $user->id && $lockedUntil && $lockedUntil->isFuture();
        if ($isLockedByOther) {
            return back()->with('status', 'Percakapan sedang diklaim oleh pengguna lain.');
        }

        $conversation->update([
            'owner_id' => $user->id,
            'claimed_at' => $now,
            'locked_until' => $now->copy()->addMinutes($lockMinutes),
        ]);

        WhatsAppConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['role' => 'owner', 'invited_at' => $now, 'invited_by' => $user->id]
        );

        return back()->with('status', 'Percakapan berhasil diklaim.');
    }

    public function release(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($conversation, $user);

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

    public function invite(Request $request, WhatsAppConversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($conversation, $user);

        if ($conversation->owner_id !== $user->id && !$user->hasRole('Super-admin')) {
            return back()->with('status', 'Hanya pemilik atau super-admin yang dapat mengundang.');
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'string', 'max:50'],
        ]);

        $invitee = User::findOrFail($data['user_id']);
        $role = $data['role'] ?? 'collaborator';

        // Ensure invitee has access to the instance
        if (!$invitee->hasRole('Super-admin')) {
            $hasAccess = $conversation->instance->users()->where('users.id', $invitee->id)->exists();
            if (!$hasAccess) {
                return back()->with('status', 'Pengguna belum diizinkan untuk instance ini.');
            }
        }

        WhatsAppConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $invitee->id],
            ['role' => $role, 'invited_at' => now(), 'invited_by' => $user->id, 'left_at' => null]
        );

        return back()->with('status', 'Pengguna diundang ke percakapan.');
    }

    private function authorizeAccess(WhatsAppConversation $conversation, User $user): void
    {
        if ($user->hasRole('Super-admin')) {
            return;
        }

        $allowed = $conversation->instance
            ->users()
            ->where('users.id', $user->id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
