<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\Conversations\Models\ConversationActivityLog;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                'contact_external_id' => $contactKey,
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
            'locked_until' => now()->addMinutes(config('conversations.lock_minutes', 30)),
        ]);

        $this->log($conversation, $me->id, 'start_internal', "Start with user {$otherId}");

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'Percakapan internal dibuat.');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $lockMinutes = (int) config('conversations.lock_minutes', 30);
        $waModuleReady = $this->isWhatsAppApiReady();

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

        return view('conversations::index', compact('conversations', 'lockMinutes', 'waModuleReady'));
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

        $lockMinutes = (int) config('conversations.lock_minutes', 30);
        $waModuleReady = $this->isWhatsAppApiReady();
        $waTemplates = ($conversation->channel === 'wa_api' && $this->isWaTemplateReady())
            ? $this->waTemplateModelClass()::where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('conversations::show', [
            'conversation' => $conversation,
            'conversationsList' => $conversationsList,
            'lockMinutes' => $lockMinutes,
            'waTemplates' => $waTemplates,
            'waModuleReady' => $waModuleReady,
        ]);
    }

    public function claim(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $lockMinutes = (int) config('conversations.lock_minutes', 30);
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

        if ($conversation->channel === 'wa_api' && (!$conversation->instance_id || !$this->waInstanceExists((int) $conversation->instance_id))) {
            return back()->with('status', 'Instance untuk percakapan WA API tidak ditemukan. Pastikan WA Instance masih aktif.');
        }

        $mode = $conversation->channel === 'wa_api'
            ? $request->input('message_type', 'text')
            : 'text';

        if ($mode === 'template') {
            if (!$this->isWaTemplateReady()) {
                return back()->with('status', 'Template WA belum tersedia. Aktifkan module WhatsApp API terlebih dahulu.');
            }

            $data = $request->validate([
                'template_id' => ['required', 'exists:wa_templates,id'],
                'template_params' => ['array'],
                'template_params.*' => ['nullable', 'string', 'max:250'],
            ]);

            $template = $this->waTemplateModelClass()::find($data['template_id']);
            if (!$template) {
                return back()->with('status', 'Template tidak ditemukan.');
            }
            $payload = $this->buildTemplatePayload($template, $request->input('template_params', []));

            // Pastikan semua placeholder terisi
            foreach ($payload['placeholders'] as $idx) {
                if (!isset($request->input('template_params')[$idx]) || trim($request->input('template_params')[$idx]) === '') {
                    return back()->with('status', "Nilai untuk placeholder {{$idx}} wajib diisi.");
                }
            }

            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'out',
                'type' => 'template',
                'body' => $template->body,
                'payload' => $payload,
                'status' => 'queued',
            ]);
        } else {
            $data = $request->validate([
                'body' => ['required', 'string'],
            ]);

            if ($conversation->channel === 'wa_api' && !$this->isWithinWaCustomerCareWindow($conversation)) {
                return back()->with('status', 'Di luar jendela 24 jam. Gunakan template message untuk mengirim pesan.');
            }

            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'out',
                'type' => 'text',
                'body' => $data['body'],
                'status' => $conversation->channel === 'wa_api' ? 'queued' : 'sent',
                'sent_at' => $conversation->channel === 'wa_api' ? null : now(),
            ]);
        }

        $conversation->update([
            'last_message_at' => now(),
            'last_outgoing_at' => now(),
        ]);

        if ($message) {
            $this->dispatchOutboundJob($conversation->channel, (int) $message->id);
            broadcast(new ConversationMessageCreated($message))->toOthers();
        }

        $this->log($conversation, $user->id, 'send', 'Kirim pesan');

        return redirect()->route('conversations.show', $conversation)->with('status', 'Pesan terkirim.');
    }

    private function isWithinWaCustomerCareWindow(Conversation $conversation): bool
    {
        $lastIncomingAt = $conversation->last_incoming_at;
        if (!$lastIncomingAt) {
            return false;
        }

        return $lastIncomingAt->greaterThanOrEqualTo(now()->subHours(24));
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
            || $this->hasInstanceAccess($conversation, (int) $user->id);

        abort_unless($allowed, 403);
    }

    private function baseQuery(User $user)
    {
        $query = Conversation::with(['owner']);
        if ($this->isWhatsAppApiReady()) {
            $query->with('instance');
        }

        return $query
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id));

                    if ($this->isWhatsAppApiReady()) {
                        $instanceIds = $this->accessibleWhatsAppInstanceIds((int) $user->id);
                        if (!empty($instanceIds)) {
                            $q->orWhere(function ($waQ) use ($instanceIds) {
                                $waQ->where('channel', 'wa_api')
                                    ->whereIn('instance_id', $instanceIds);
                            });
                        }
                    }
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

    private function buildTemplatePayload(object $template, array $params): array
    {
        $header = collect($template->components ?? [])->firstWhere('type', 'header');
        $headerText = strtolower(data_get($header, 'format')) === 'text'
            ? data_get($header, 'parameters.0.text')
            : null;

        $bodyIndexes = $this->placeholderIndexes($template->body);
        $headerIndexes = $this->placeholderIndexes($headerText);
        $allIndexes = array_values(array_unique(array_merge($bodyIndexes, $headerIndexes)));
        sort($allIndexes);

        $bodyParams = [];
        foreach ($bodyIndexes as $idx) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => $params[$idx] ?? '',
            ];
        }

        $headerParams = [];
        if ($headerText) {
            foreach ($headerIndexes as $idx) {
                $headerParams[] = [
                    'type' => 'text',
                    'text' => $params[$idx] ?? '',
                ];
            }
        } elseif ($header && data_get($header, 'parameters.0.link')) {
            $linkType = strtolower(data_get($header, 'parameters.0.type', 'image'));
            $headerParams[] = [
                'type' => $linkType,
                'link' => data_get($header, 'parameters.0.link'),
            ];
        }

        $components = [];
        if ($headerParams) {
            $components[] = [
                'type' => 'header',
                'parameters' => $headerParams,
            ];
        }
        if ($bodyParams) {
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }
        // Buttons/components from template (CTA/quick reply) do not require parameters for send payload
        return [
            'template_id' => $template->id,
            'name' => $template->name,
            'language' => $template->language,
            'components' => $components,
            'placeholders' => $allIndexes,
        ];
    }

    private function placeholderIndexes(?string $text): array
    {
        if (!$text) {
            return [];
        }
        preg_match_all('/\\{\\{(\\d+)\\}\\}/', $text, $matches);
        $indexes = array_map('intval', $matches[1] ?? []);
        $unique = array_values(array_unique($indexes));
        sort($unique);
        return $unique;
    }

    private function isWhatsAppApiReady(): bool
    {
        return class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstance::class)
            && Schema::hasTable('whatsapp_instances')
            && Schema::hasTable('whatsapp_instance_user');
    }

    private function isWaTemplateReady(): bool
    {
        return class_exists($this->waTemplateModelClass())
            && Schema::hasTable('wa_templates');
    }

    private function waTemplateModelClass(): string
    {
        return \App\Modules\WhatsAppApi\Models\WATemplate::class;
    }

    private function waInstanceExists(int $instanceId): bool
    {
        if (!$this->isWhatsAppApiReady()) {
            return false;
        }

        return DB::table('whatsapp_instances')->where('id', $instanceId)->exists();
    }

    private function accessibleWhatsAppInstanceIds(int $userId): array
    {
        if (!$this->isWhatsAppApiReady()) {
            return [];
        }

        return DB::table('whatsapp_instance_user')
            ->where('user_id', $userId)
            ->pluck('instance_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function hasInstanceAccess(Conversation $conversation, int $userId): bool
    {
        if ($conversation->channel !== 'wa_api' || !$conversation->instance_id) {
            return false;
        }

        if (!$this->isWhatsAppApiReady()) {
            return false;
        }

        return DB::table('whatsapp_instance_user')
            ->where('instance_id', (int) $conversation->instance_id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function dispatchOutboundJob(string $channel, int $messageId): void
    {
        if ($channel === 'wa_api') {
            $waJobClass = \App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage::class;
            if (class_exists($waJobClass)) {
                $waJobClass::dispatch($messageId);
            }
            return;
        }

        if ($channel === 'social_dm') {
            $socialJobClass = \App\Modules\SocialMedia\Jobs\SendSocialMessage::class;
            if (class_exists($socialJobClass)) {
                $socialJobClass::dispatch($messageId);
            }
        }
    }
}

