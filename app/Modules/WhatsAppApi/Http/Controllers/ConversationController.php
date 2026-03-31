<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\Presence\UserPresenceService;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\WhatsAppApi\Http\Requests\InviteConversationRequest;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function __construct(private readonly UserPresenceService $presenceService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $user = $request->user();
        $lockMinutes = config('modules.whatsapp_api.lock_minutes', 30);
        $selectedInstanceId = $request->integer('instance_id') ?: null;
        $search = trim((string) $request->input('q', ''));
        $assignment = trim((string) $request->input('assignment', ''));
        $presence = trim((string) $request->input('presence', ''));
        $presenceIds = $this->ownerIdsForPresence($presence);
        $nonOfflinePresenceIds = $this->ownerIdsForAnyPresence([
            UserPresence::STATUS_ONLINE,
            UserPresence::STATUS_AWAY,
            UserPresence::STATUS_BUSY,
        ]);

        $instancesQuery = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId());
        BooleanQuery::apply($instancesQuery, 'is_active', true);
        if (!$user->hasRole('Super-admin')) {
            $instancesQuery->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }
        $instances = $instancesQuery->orderBy('name')->get();

        $conversations = Conversation::with(['instance.users:id,name', 'owner'])
            ->where('tenant_id', $this->tenantId())
            ->where('channel', 'wa_api')
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->whereHas('instance.users', fn ($q) => $q->where('users.id', $user->id));
            })
            ->when($selectedInstanceId, fn ($query, $instanceId) => $query->where('instance_id', $instanceId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_external_id', 'like', "%{$search}%");
                });
            })
            ->when($assignment === 'mine', fn ($query) => $query->where('owner_id', $user->id))
            ->when($assignment === 'unassigned', fn ($query) => $query->where(function ($q) {
                $q->whereNull('owner_id')
                    ->orWhere('locked_until', '<=', now());
            }))
            ->when($assignment === 'assigned', fn ($query) => $query->whereNotNull('owner_id')->where(function ($q) {
                $q->whereNull('locked_until')
                    ->orWhere('locked_until', '>', now());
            }))
            ->when($assignment === 'bot_paused', fn ($query) => BooleanQuery::jsonFlag($query, 'metadata', 'auto_reply_paused', true))
            ->when(in_array($presence, [UserPresence::STATUS_ONLINE, UserPresence::STATUS_AWAY, UserPresence::STATUS_BUSY], true), function ($query) use ($presenceIds) {
                if (empty($presenceIds)) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn('owner_id', $presenceIds);
            })
            ->when($presence === UserPresence::STATUS_OFFLINE, fn ($query) => $query->whereNotNull('owner_id')->whereNotIn('owner_id', $nonOfflinePresenceIds))
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $ownerPresenceMap = $this->presenceService->statusMapForUsers($conversations->getCollection()->pluck('owner_id'));
        $myPresence = $this->presenceService->forUser($user);

        return view('whatsappapi::conversations.index', compact(
            'conversations',
            'instances',
            'lockMinutes',
            'selectedInstanceId',
            'search',
            'assignment',
            'presence',
            'ownerPresenceMap',
            'myPresence'
        ));
    }

    private function ownerIdsForPresence(string $presence): array
    {
        if ($presence === '') {
            return [];
        }

        return $this->ownerIdsForAnyPresence([$presence]);
    }

    private function ownerIdsForAnyPresence(array $statuses): array
    {
        return $this->presenceService->userIdsForStatuses($statuses);
    }

    public function claim(Request $request, Conversation $conversation): RedirectResponse
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

        ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['role' => 'owner', 'invited_at' => $now, 'invited_by' => $user->id]
        );

        return back()->with('status', 'Percakapan diklaim.');
    }

    public function release(Request $request, Conversation $conversation): RedirectResponse
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

    public function invite(InviteConversationRequest $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAccess($conversation, $user);

        if ($conversation->owner_id !== $user->id && !$user->hasRole('Super-admin')) {
            return back()->with('status', 'Hanya pemilik atau super-admin yang dapat mengundang.');
        }

        $data = $request->validated();

        $invitee = User::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($data['user_id']);
        $role = $data['role'] ?? 'collaborator';

        // Ensure invitee has access to the instance
        if (!$invitee->hasRole('Super-admin')) {
            $hasAccess = $conversation->instance->users()->where('users.id', $invitee->id)->exists();
            if (!$hasAccess) {
                return back()->with('status', 'Pengguna belum diizinkan untuk instance ini.');
            }
        }

        ConversationParticipant::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $invitee->id],
            ['role' => $role, 'invited_at' => now(), 'invited_by' => $user->id, 'left_at' => null]
        );

        return back()->with('status', 'Pengguna diundang ke percakapan.');
    }

    private function authorizeAccess(Conversation $conversation, User $user): void
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

    private function ensureAnyInstanceExists(Request $request): ?RedirectResponse
    {
        if (WhatsAppInstance::query()->where('tenant_id', $this->tenantId())->exists()) {
            return null;
        }

        if ($request->user()?->hasRole('Super-admin')) {
            return redirect()
                ->route('whatsapp-api.instances.create')
                ->with('status', 'Buat WA Instance terlebih dahulu sebelum mengakses Inbox WhatsApp API.');
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'WhatsApp API belum siap. Minta Super-admin membuat WA Instance terlebih dahulu.');
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
