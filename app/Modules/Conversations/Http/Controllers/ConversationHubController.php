<?php

namespace App\Modules\Conversations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Conversations\Models\ConversationParticipant;
use App\Modules\Conversations\Models\ConversationActivityLog;
use App\Modules\Conversations\Events\ConversationMessageCreated;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ConversationHubController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $request->validate([
            'query' => ['required', 'string'],
        ]);

        $me = $request->user();
        $q = $request->input('query');
        $other = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where(function ($query) use ($q) {
                $query->where('id', $q)
                    ->orWhere('email', $q)
                    ->orWhere('name', $q);
            })
            ->first();

        if (!$other || $other->id === $me->id) {
            return back()->with('status', 'User tidak ditemukan / sama dengan diri sendiri.');
        }

        $otherId = $other->id;
        $otherName = $other->name;

        // Buat key unik berurutan agar percakapan internal tidak dobel
        $pair = collect([$me->id, $otherId])->sort()->implode('-');
        $contactKey = 'internal-' . $pair;

        $conversation = Conversation::query()
            ->where('tenant_id', $this->tenantId())
            ->where('channel', 'internal')
            ->where('contact_external_id', $contactKey)
            ->where('instance_id', 0)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'tenant_id' => $this->tenantId(),
                'channel' => 'internal',
                'instance_id' => 0,
                'contact_external_id' => $contactKey,
                'contact_name' => $otherName,
                'status' => 'open',
                'last_message_at' => now(),
            ]);
        }

        ConversationParticipant::firstOrCreate(
            ['tenant_id' => $this->tenantId(), 'conversation_id' => $conversation->id, 'user_id' => $me->id],
            ['role' => 'owner', 'invited_at' => now(), 'invited_by' => $me->id]
        );
        ConversationParticipant::firstOrCreate(
            ['tenant_id' => $this->tenantId(), 'conversation_id' => $conversation->id, 'user_id' => $otherId],
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
        $filters = [
            'search' => trim((string) $request->string('search')->toString()),
            'channel' => $request->string('channel')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'assignment' => $request->string('assignment')->toString() ?: null,
            'unread_only' => $request->boolean('unread_only'),
        ];

        $query = $this->filteredQuery($user, $filters);

        $conversations = $query->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $summaryBase = $this->baseQuery($user);
        $summary = [
            'total' => (clone $summaryBase)->count(),
            'unread' => (clone $summaryBase)->where('unread_count', '>', 0)->count(),
            'unassigned' => (clone $summaryBase)->whereNull('owner_id')->count(),
            'mine' => (clone $summaryBase)->where('owner_id', $user->id)->count(),
            'locked' => (clone $summaryBase)->whereNotNull('locked_until')->where('locked_until', '>', now())->count(),
        ];

        return view('conversations::index', compact('conversations', 'lockMinutes', 'waModuleReady', 'filters', 'summary'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);

        if ((int) ($conversation->unread_count ?? 0) > 0) {
            $conversation->update(['unread_count' => 0]);
            $conversation->refresh();
        }

        $conversation->load(['owner', 'participants.user']);

        $initialMessages = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->with('user:id,name,avatar')
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $conversation->setRelation('messages', $initialMessages);
        $oldestMessageId = $initialMessages->first()->id ?? null;
        $latestMessageId = $initialMessages->last()->id ?? null;
        $hasMoreMessages = $oldestMessageId
            ? $this->hasOlderMessages($conversation, $oldestMessageId)
            : false;

        $conversationsList = $this->baseQuery($user)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $lockMinutes = (int) config('conversations.lock_minutes', 30);
        $waModuleReady = $this->isWhatsAppApiReady();
        $channelManager = app(ConversationChannelManager::class);
        $waTemplates = $channelManager->templatesFor($conversation);
        $channelUi = [
            'show_ai_bot' => $channelManager->hasUiFeature($conversation, 'show_ai_bot'),
            'show_media_composer' => $channelManager->hasUiFeature($conversation, 'show_media_composer'),
            'show_template_composer' => $channelManager->hasUiFeature($conversation, 'show_template_composer'),
            'show_contact_crm' => $channelManager->hasUiFeature($conversation, 'show_contact_crm'),
        ];
        $relatedContact = $this->findRelatedContact($conversation);

        return view('conversations::show', [
            'conversation' => $conversation,
            'conversationsList' => $conversationsList,
            'lockMinutes' => $lockMinutes,
            'waTemplates' => $waTemplates,
            'waModuleReady' => $waModuleReady,
            'oldestMessageId' => $oldestMessageId,
            'latestMessageId' => $latestMessageId,
            'hasMoreMessages' => $hasMoreMessages,
            'channelUi' => $channelUi,
            'relatedContact' => $relatedContact,
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->integer('page', 1));
        $limit = max(10, min(25, (int) $request->integer('limit', 15)));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'items' => [],
                'has_more' => false,
                'page' => $page,
            ]);
        }

        $users = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->simplePaginate($limit, ['id', 'name', 'email'], 'page', $page);

        return response()->json([
            'items' => collect($users->items())->map(fn ($u) => [
                'id' => $u->id,
                'text' => "{$u->name} ({$u->email})",
            ])->values(),
            'has_more' => $users->hasMorePages(),
            'page' => $page,
        ]);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);

        if ((int) ($conversation->unread_count ?? 0) > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        return response()->json(['ok' => true]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);

        $beforeId = (int) $request->integer('before_id', 0);
        $limit = (int) $request->integer('limit', 30);
        $limit = max(10, min(50, $limit));

        $query = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->with('user:id,name,avatar')
            ->where('conversation_id', $conversation->id);

        if ($beforeId > 0) {
            $anchor = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->find($beforeId);

            if ($anchor) {
                $query->where(function ($subQuery) use ($anchor): void {
                    $subQuery->where('created_at', '<', $anchor->created_at)
                        ->orWhere(function ($sameTimeQuery) use ($anchor): void {
                            $sameTimeQuery->where('created_at', $anchor->created_at)
                                ->where('id', '<', $anchor->id);
                        });
                });
            }
        }

        $messages = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $messages = $messages->sortBy([
            ['created_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        $oldestId = $messages->first()?->id;
        $hasMore = $oldestId ? $this->hasOlderMessages($conversation, $oldestId) : false;

        $payload = $messages->map(function (ConversationMessage $msg) {
            return [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'type' => $msg->type,
                'body' => $msg->body,
                'status' => $msg->status,
                'created_at' => optional($msg->created_at)->format('d M H:i') ?? '',
                'user' => $msg->user ? [
                    'name' => $msg->user->name,
                    'avatar' => $msg->user->avatar,
                ] : null,
            ];
        });

        return response()->json([
            'messages' => $payload,
            'has_more' => $hasMore,
            'oldest_id' => $oldestId,
        ]);
    }

    public function messagesSince(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);

        $afterId = (int) $request->integer('after_id', 0);
        $limit = (int) $request->integer('limit', 30);
        $limit = max(5, min(50, $limit));

        $query = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->with('user:id,name,avatar')
            ->where('conversation_id', $conversation->id);

        if ($afterId > 0) {
            $anchor = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->find($afterId);

            if ($anchor) {
                $query->where(function ($subQuery) use ($anchor): void {
                    $subQuery->where('created_at', '>', $anchor->created_at)
                        ->orWhere(function ($sameTimeQuery) use ($anchor): void {
                            $sameTimeQuery->where('created_at', $anchor->created_at)
                                ->where('id', '>', $anchor->id);
                        });
                });
            }
        }

        $messages = $query->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $latestId = $messages->last()?->id ?? $afterId;

        $payload = $messages->map(function (ConversationMessage $msg) {
            return $this->messagePayload($msg);
        })->values();

        return response()->json([
            'messages' => $payload,
            'latest_id' => $latestId,
        ]);
    }

    public function claim(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeView($conversation, $user);
        $lockMinutes = (int) config('conversations.lock_minutes', 30);
        $now = now();

        $lockedByOther = false;

        DB::transaction(function () use ($conversation, $user, $lockMinutes, $now, &$lockedByOther) {
            $current = Conversation::query()
                ->where('tenant_id', $this->tenantId())
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                $lockedByOther = true;
                return;
            }

            $isLocked = $current->owner_id
                && $current->owner_id !== $user->id
                && $current->locked_until
                && $current->locked_until->isFuture();

            if ($isLocked) {
                $lockedByOther = true;
                return;
            }

            $current->update([
                'owner_id' => $user->id,
                'claimed_at' => $now,
                'locked_until' => $now->copy()->addMinutes($lockMinutes),
            ]);

            ConversationParticipant::updateOrCreate(
                ['tenant_id' => $this->tenantId(), 'conversation_id' => $current->id, 'user_id' => $user->id],
                ['role' => 'owner', 'invited_at' => $now, 'invited_by' => $user->id]
            );

            $this->log($current, $user->id, 'claim', 'Percakapan diklaim');
        });

        if ($lockedByOther) {
            return back()->with('status', 'Percakapan sedang diklaim orang lain.');
        }

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

        $invitee = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where(function ($query) use ($data) {
                $query->where('id', $data['query'])
                    ->orWhere('email', $data['query'])
                    ->orWhere('name', $data['query']);
            })
            ->first();

        if (!$invitee) {
            return back()->with('status', 'User tidak ditemukan.');
        }

        $role = $data['role'] ?? 'collaborator';

        ConversationParticipant::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'conversation_id' => $conversation->id, 'user_id' => $invitee->id],
            ['role' => $role, 'invited_at' => now(), 'invited_by' => $user->id, 'left_at' => null]
        );

        $this->log($conversation, $user->id, 'invite', "Invite user {$invitee->id}");

        return back()->with('status', 'Pengguna diundang.');
    }

    public function close(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        if ($conversation->channel !== 'live_chat') {
            return back()->with('status', 'Close manual saat ini hanya tersedia untuk live chat.');
        }

        $conversation->update([
            'status' => 'closed',
            'metadata' => array_merge($conversation->metadata ?? [], [
                'closed_at' => now()->toIso8601String(),
                'closed_by' => $user->id,
            ]),
        ]);

        $this->log($conversation, $user->id, 'close', 'Conversation live chat ditutup');

        return back()->with('status', 'Conversation ditutup.');
    }

    public function reopen(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        if ($conversation->channel !== 'live_chat') {
            return back()->with('status', 'Reopen manual saat ini hanya tersedia untuk live chat.');
        }

        $metadata = $conversation->metadata ?? [];
        unset($metadata['closed_at'], $metadata['closed_by']);

        $conversation->update([
            'status' => 'open',
            'metadata' => $metadata ?: null,
        ]);

        $this->log($conversation, $user->id, 'reopen', 'Conversation live chat dibuka kembali');

        return back()->with('status', 'Conversation dibuka kembali.');
    }

    public function updateContactNote(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user);

        $contact = $this->findRelatedContact($conversation);
        if (!$contact) {
            return back()->with('status', 'Contact terkait tidak ditemukan.');
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $contact->update([
            'notes' => $data['notes'] ?? null,
        ]);

        $this->log($conversation, $user->id, 'update_contact_note', "Update notes untuk contact {$contact->id}");

        return back()->with('status', 'Catatan contact diperbarui.');
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $this->authorizeParticipant($conversation, $user);

        if ($conversation->channel === 'live_chat' && $conversation->status === 'closed') {
            $metadata = $conversation->metadata ?? [];
            unset($metadata['closed_at'], $metadata['closed_by']);
            $conversation->update([
                'status' => 'open',
                'metadata' => $metadata ?: null,
            ]);
        }

        $preflightError = app(ConversationChannelManager::class)->preflightSendError($conversation);
        if ($preflightError !== null) {
            return $this->sendErrorResponse($request, $preflightError);
        }

        $mode = $request->input('message_type', app(ConversationChannelManager::class)->defaultMessageType($conversation));

        if ($mode === 'template') {
            if (!app(ConversationChannelManager::class)->supportsTemplates($conversation)) {
                return $this->sendErrorResponse($request, 'Template WA belum tersedia. Aktifkan module WhatsApp API terlebih dahulu.');
            }

            $data = $request->validate([
                'template_id' => ['required', Rule::exists('wa_templates', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))],
                'template_params' => ['array'],
                'template_params.*' => ['nullable', 'string', 'max:250'],
            ]);

            if (!$this->waTemplateModelClass()::query()->where('tenant_id', $this->tenantId())->find($data['template_id'])) {
                return $this->sendErrorResponse($request, 'Template tidak ditemukan untuk tenant aktif.');
            }

            $template = $this->waTemplateModelClass()::query()
                ->where('tenant_id', $this->tenantId())
                ->find($data['template_id']);
            if (!$template) {
                return $this->sendErrorResponse($request, 'Template tidak ditemukan.');
            }
            $payload = $this->buildTemplatePayload($template, $request->input('template_params', []));

            // Pastikan semua placeholder terisi
            foreach ($payload['placeholders'] as $idx) {
                if (!isset($request->input('template_params')[$idx]) || trim($request->input('template_params')[$idx]) === '') {
                    return $this->sendErrorResponse($request, "Nilai untuk placeholder {{$idx}} wajib diisi.");
                }
            }

            $message = ConversationMessage::create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'out',
                'type' => 'template',
                'body' => $template->body,
                'payload' => $payload,
                'status' => 'queued',
            ]);
        } elseif ($mode === 'media') {
            $data = $request->validate([
                'media_file' => ['required', 'file', 'max:20480'],
                'body' => ['nullable', 'string', 'max:1000'],
            ]);

            $mediaError = app(ConversationChannelManager::class)->validateTextSend($conversation);
            if ($mediaError !== null) {
                return $this->sendErrorResponse($request, $mediaError);
            }

            /** @var UploadedFile $uploaded */
            $uploaded = $request->file('media_file');
            [$mediaType, $mediaMime] = $this->resolveWaMediaType($uploaded);
            if (!$mediaType) {
                return $this->sendErrorResponse($request, 'Tipe file tidak didukung untuk WhatsApp.');
            }

            $path = $uploaded->store('wa_messages/' . now()->format('Y/m'), 'public');
            $publicUrl = $this->publicStorageUrl($path);

            $mediaValidationError = app(ConversationChannelManager::class)->validateMediaSend($conversation, $publicUrl);
            if ($mediaValidationError !== null) {
                Storage::disk('public')->delete($path);

                return $this->sendErrorResponse($request, $mediaValidationError);
            }

            $filename = $uploaded->getClientOriginalName();
            $caption = trim((string) ($data['body'] ?? ''));
            $bodyText = $caption !== '' ? $caption : $filename;

            $outboundDefaults = app(ConversationChannelManager::class)->outboundPersistenceDefaults($conversation);
            $message = ConversationMessage::create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'out',
                'type' => $mediaType,
                'body' => $bodyText,
                'media_url' => $publicUrl,
                'media_mime' => $mediaMime,
                'payload' => [
                    'link' => $publicUrl,
                    'filename' => $filename,
                ],
                'status' => $outboundDefaults['status'],
                'sent_at' => $outboundDefaults['sent_at'],
            ]);
        } else {
            $data = $request->validate([
                'body' => ['required', 'string'],
            ]);

            $textError = app(ConversationChannelManager::class)->validateTextSend($conversation);
            if ($textError !== null) {
                return $this->sendErrorResponse($request, $textError);
            }

            $outboundDefaults = app(ConversationChannelManager::class)->outboundPersistenceDefaults($conversation);
            $message = ConversationMessage::create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'out',
                'type' => 'text',
                'body' => $data['body'],
                'status' => $outboundDefaults['status'],
                'sent_at' => $outboundDefaults['sent_at'],
            ]);
        }

        $conversation->update([
            'last_message_at' => now(),
            'last_outgoing_at' => now(),
        ]);

        if ($message) {
            $this->dispatchOutboundMessage($message);
            $this->safeBroadcastMessageCreated($message);
        }

        $this->log($conversation, $user->id, 'send', 'Kirim pesan');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $this->messagePayload($message),
            ]);
        }

        return redirect()->route('conversations.show', $conversation)->with('status', 'Pesan terkirim.');
    }

    private function resolveWaMediaType(UploadedFile $file): array
    {
        $mime = strtolower((string) ($file->getMimeType() ?? ''));
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (str_starts_with($mime, 'image/')) {
            return ['image', $mime];
        }
        if (str_starts_with($mime, 'video/')) {
            return ['video', $mime];
        }
        if (str_starts_with($mime, 'audio/')) {
            return ['audio', $mime];
        }

        $allowedDocExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];
        if (in_array($ext, $allowedDocExt, true)) {
            return ['document', $mime ?: 'application/octet-stream'];
        }

        return [null, $mime ?: 'application/octet-stream'];
    }

    private function sendErrorResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('status', $message);
    }

    private function messagePayload(ConversationMessage $message): array
    {
        $message->loadMissing('user:id,name,avatar');

        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'type' => $message->type,
            'body' => $message->body,
            'media_url' => $message->media_url,
            'media_mime' => $message->media_mime,
            'filename' => data_get($message->payload, 'filename'),
            'status' => $message->status,
            'created_at' => optional($message->created_at)->format('d M H:i') ?? '',
            'user' => $message->user ? [
                'name' => $message->user->name,
                'avatar' => $message->user->avatar,
            ] : null,
        ];
    }

    private function hasOlderMessages(Conversation $conversation, int $oldestMessageId): bool
    {
        $anchor = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('conversation_id', $conversation->id)
            ->find($oldestMessageId);

        if (!$anchor) {
            return false;
        }

        return ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('conversation_id', $conversation->id)
            ->where(function ($query) use ($anchor): void {
                $query->where('created_at', '<', $anchor->created_at)
                    ->orWhere(function ($sameTimeQuery) use ($anchor): void {
                        $sameTimeQuery->where('created_at', $anchor->created_at)
                            ->where('id', '<', $anchor->id);
                    });
            })
            ->exists();
    }

    private function authorizeParticipant(Conversation $conversation, User $user): void
    {
        if ($user->hasRole('Super-admin')) {
            return;
        }

        $allowed = $conversation->owner_id === $user->id
            || $conversation->participants()
                ->where('user_id', $user->id)
                ->exists()
            || app(ConversationAccessRegistry::class)->canParticipate($conversation, $user);

        abort_unless($allowed, 403);
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

    private function baseQuery(User $user)
    {
        $query = Conversation::with([
            'owner',
            'latestMessage' => function ($q) {
                $q->select([
                    'conversation_messages.id',
                    'conversation_messages.conversation_id',
                    'conversation_messages.body',
                    'conversation_messages.type',
                    'conversation_messages.created_at',
                ]);
            },
        ]);
        if ($this->isWhatsAppApiReady()) {
            $query->with('instance');
        }

        return $query
            ->where('tenant_id', $this->tenantId())
            ->when(!$user->hasRole('Super-admin'), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id));
                    app(ConversationAccessRegistry::class)->applyVisibilityScope($q, $user);
                });
            });
    }

    private function filteredQuery(User $user, array $filters)
    {
        return $this->baseQuery($user)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($nested) use ($search) {
                    $nested->where('contact_name', 'like', '%' . $search . '%')
                        ->orWhere('contact_external_id', 'like', '%' . $search . '%')
                        ->orWhereHas('latestMessage', fn ($message) => $message->where('body', 'like', '%' . $search . '%'));
                });
            })
            ->when($filters['channel'], fn ($query, $channel) => $query->where('channel', $channel))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['unread_only'], fn ($query) => $query->where('unread_count', '>', 0))
            ->when($filters['assignment'], function ($query, $assignment) use ($user) {
                if ($assignment === 'mine') {
                    $query->where('owner_id', $user->id);
                }

                if ($assignment === 'unassigned') {
                    $query->whereNull('owner_id');
                }

                if ($assignment === 'others') {
                    $query->whereNotNull('owner_id')->where('owner_id', '!=', $user->id);
                }
            });
    }

    private function log(Conversation $conversation, ?int $userId, string $action, ?string $detail = null): void
    {
        ConversationActivityLog::create([
            'tenant_id' => $this->tenantId(),
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
            'meta_name' => method_exists($template, 'metaTemplateName') ? $template->metaTemplateName() : ($template->meta_name ?: $template->name),
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

    private function isContactsReady(): bool
    {
        return class_exists(Contact::class)
            && class_exists(ContactPhoneNormalizer::class)
            && Schema::hasTable('contacts');
    }

    private function findRelatedContact(Conversation $conversation): ?Contact
    {
        if (!$this->isContactsReady()) {
            return null;
        }

        $phone = ContactPhoneNormalizer::normalize((string) ($conversation->contact_external_id ?? ''));
        if (!$phone) {
            return null;
        }

        return Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->where(function ($query) use ($phone) {
                $query->where('mobile', $phone)
                    ->orWhere('phone', $phone);
            })
            ->orderByRaw('CASE WHEN mobile = ? THEN 0 ELSE 1 END', [$phone])
            ->orderBy('name')
            ->first();
    }

    private function waTemplateModelClass(): string
    {
        return \App\Modules\WhatsAppApi\Models\WATemplate::class;
    }

    private function publicStorageUrl(string $path): string
    {
        return url(Storage::disk('public')->url($path));
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        return is_dir(public_path('storage'));
    }

    private function dispatchOutboundMessage(ConversationMessage $message): void
    {
        app(ConversationOutboundDispatcher::class)->dispatch($message);
    }

    private function safeBroadcastMessageCreated(ConversationMessage $message): void
    {
        try {
            broadcast(new ConversationMessageCreated($message))->toOthers();
        } catch (Throwable $e) {
            // Realtime is optional; do not block message send when broadcaster is misconfigured.
            Log::warning('Broadcast skipped: ' . $e->getMessage(), [
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
                'broadcast_driver' => (string) config('broadcasting.default'),
            ]);
        }
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}

