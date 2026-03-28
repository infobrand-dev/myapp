<?php

namespace App\Modules\LiveChat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\LiveChat\Models\LiveChatVisitorSession;
use App\Modules\LiveChat\Models\LiveChatWidget;
use App\Modules\LiveChat\Http\Requests\BootstrapLiveChatRequest;
use App\Modules\LiveChat\Http\Requests\LiveChatEventsRequest;
use App\Modules\LiveChat\Http\Requests\LiveChatMessagesRequest;
use App\Modules\LiveChat\Http\Requests\LiveChatTypingRequest;
use App\Modules\LiveChat\Http\Requests\StoreLiveChatVisitorMessageRequest;
use App\Modules\LiveChat\Support\LiveChatRealtimeState;
use App\Models\UserPresence;
use App\Support\PlanFeature;
use App\Support\TenantPlanManager;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class LiveChatPublicController extends Controller
{
    public function __construct(
        private readonly InboxMessageIngester $ingester,
        private readonly LiveChatRealtimeState $realtimeState,
        private readonly TenantPlanManager $plans,
    )
    {
    }

    public function script(string $token): Response
    {
        $widget = $this->resolveWidget($token);

        $content = view('livechat::public.widget-script', [
            'widget' => $widget,
            'bootstrapUrl' => route('live-chat.api.bootstrap', $widget->widget_token),
            'messageUrl' => route('live-chat.api.messages.store', $widget->widget_token),
            'pollUrl' => route('live-chat.api.messages.index', $widget->widget_token),
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function options(Request $request, string $token): Response
    {
        $widget = $this->resolveWidget($token);

        return response('', 204)->withHeaders($this->corsHeaders($request, $widget));
    }

    public function bootstrap(BootstrapLiveChatRequest $request, string $token): JsonResponse
    {
        $widget = $this->resolveWidget($token);
        $this->ensureOriginAllowed($request, $widget);
        $tenantId = $this->resolvedTenantId($widget);

        $data = $request->validated();

        [$visitorKey, $session] = $this->resolveOrIssueVisitorSession($request, $widget, $data);

        $lockKey = sprintf('live-chat:bootstrap:%d:%s', (int) $widget->id, sha1($visitorKey));
        try {
            $conversation = Cache::lock($lockKey, 10)->block(3, function () use ($tenantId, $widget, $visitorKey, $data, $request, $session) {
                $conversation = Conversation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('channel', 'live_chat')
                    ->where('instance_id', $widget->id)
                    ->where('contact_external_id', $visitorKey)
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::query()->create([
                        'tenant_id' => $tenantId,
                        'channel' => 'live_chat',
                        'instance_id' => $widget->id,
                        'contact_external_id' => $visitorKey,
                        'contact_name' => $data['visitor_name'] ?? 'Website Visitor',
                        'status' => 'open',
                        'last_message_at' => now(),
                        'metadata' => $this->conversationMetadata($request, $widget, $data),
                    ]);
                } else {
                    $conversation->update([
                        'contact_name' => $data['visitor_name'] ?: $conversation->contact_name,
                        'metadata' => array_merge($conversation->metadata ?? [], $this->conversationMetadata($request, $widget, $data)),
                    ]);
                }

                $session->forceFill([
                    'tenant_id' => $tenantId,
                    'conversation_id' => $conversation->id,
                    'last_seen_at' => now(),
                    'expires_at' => now()->addDays(30),
                ])->save();

                return $conversation;
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Sesi chat sedang dipersiapkan. Coba ulang beberapa detik lagi.'], 409)
                ->withHeaders($this->corsHeaders($request, $widget));
        }

        return response()->json([
            'visitor_key' => $visitorKey,
            'visitor_token' => $session->session_token_plain,
            'poll_interval_ms' => 8000,
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
            ],
            'presence' => [
                'agent' => $this->agentPresence($conversation),
            ],
            'typing' => [
                'visitor' => false,
                'agent' => false,
            ],
            'widget' => [
                'name' => $widget->name,
                'website_name' => $widget->website_name,
                'welcome_text' => $widget->welcome_text,
                'theme_color' => $widget->theme_color,
            ],
        ])->withHeaders($this->corsHeaders($request, $widget));
    }

    public function index(LiveChatMessagesRequest $request, string $token): JsonResponse
    {
        $widget = $this->resolveWidget($token);
        $this->ensureOriginAllowed($request, $widget);

        $data = $request->validated();

        $session = $this->authorizeVisitorSession($request, $widget, $data['visitor_key'], $data['visitor_token']);
        $conversation = $this->resolveConversation($widget, $session->visitor_key);
        if (!$conversation) {
            return response()->json($this->liveEventPayload(null, (int) ($data['after_id'] ?? 0)))
                ->withHeaders($this->corsHeaders($request, $widget));
        }

        return response()->json($this->liveEventPayload($conversation, (int) ($data['after_id'] ?? 0)))
            ->withHeaders($this->corsHeaders($request, $widget));
    }

    public function events(LiveChatEventsRequest $request, string $token): Response
    {
        $widget = $this->resolveWidget($token);
        $this->ensureOriginAllowed($request, $widget);

        $data = $request->validated();

        $session = $this->authorizeVisitorSession($request, $widget, $data['visitor_key'], $data['visitor_token']);
        $conversation = $this->resolveConversation($widget, $session->visitor_key);
        $afterId = (int) ($data['after_id'] ?? 0);
        $waitSeconds = (int) ($data['wait_seconds'] ?? 10);

        $payload = $this->liveEventPayload($conversation, $afterId);

        if (empty($payload['messages']) && $waitSeconds > 0) {
            $startedAt = time();
            while ((time() - $startedAt) < $waitSeconds) {
                usleep(1000 * 1000);
                $conversation?->refresh();
                $payload = $this->liveEventPayload($conversation, $afterId);
                if (!empty($payload['messages'])) {
                    break;
                }
            }
        }

        $stream = "retry: 3000\n";
        $stream .= "event: conversation.update\n";
        $stream .= 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

        return response($stream, 200, array_merge([
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ], $this->corsHeaders($request, $widget)));
    }

    public function store(StoreLiveChatVisitorMessageRequest $request, string $token): JsonResponse
    {
        $widget = $this->resolveWidget($token);
        $this->ensureOriginAllowed($request, $widget);

        $data = $request->validated();

        $session = $this->authorizeVisitorSession($request, $widget, $data['visitor_key'], $data['visitor_token']);
        $conversation = $this->resolveConversation($widget, $session->visitor_key);
        if ($conversation && $conversation->status === 'closed') {
            $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
            unset($metadata['closed_at'], $metadata['closed_by']);
            $conversation->update([
                'status' => 'open',
                'metadata' => $metadata ?: null,
            ]);
        }

        $result = $this->ingester->ingest(new InboxMessageEnvelope(
            channel: 'live_chat',
            instanceId: (int) $widget->id,
            conversationExternalId: null,
            contactExternalId: $session->visitor_key,
            contactName: $data['visitor_name'] ?? 'Website Visitor',
            direction: 'in',
            type: 'text',
            body: $data['body'],
            externalMessageId: null,
            payload: [
                'origin_host' => $this->originHost($request),
                'page_url' => $data['page_url'] ?? null,
            ],
            conversationMetadata: $this->conversationMetadata($request, $widget, $data),
            messageStatus: 'delivered',
            ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
            incrementUnread: true,
            writeActivityLog: false,
            broadcast: false,
        ));

        return response()->json([
            'stored' => true,
            'conversation_id' => $result->conversation->id,
            'conversation' => [
                'id' => $result->conversation->id,
                'status' => $result->conversation->status,
            ],
            'message' => $this->messagePayload($result->message),
        ])->withHeaders($this->corsHeaders($request, $widget));
    }

    public function typing(LiveChatTypingRequest $request, string $token): JsonResponse
    {
        $widget = $this->resolveWidget($token);
        $this->ensureOriginAllowed($request, $widget);

        $data = $request->validated();

        $session = $this->authorizeVisitorSession($request, $widget, $data['visitor_key'], $data['visitor_token']);
        $this->realtimeState->markVisitorTyping((int) $widget->id, $session->visitor_key);

        return response()->json(['ok' => true])->withHeaders($this->corsHeaders($request, $widget));
    }

    private function resolveWidget(string $token): LiveChatWidget
    {
        $widget = LiveChatWidget::query()
            ->where('widget_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($this->plans->hasFeature(PlanFeature::LIVE_CHAT, (int) $widget->tenant_id), 403);

        return $widget;
    }

    private function resolveConversation(LiveChatWidget $widget, string $visitorKey): ?Conversation
    {
        return Conversation::query()
            ->where('tenant_id', $this->resolvedTenantId($widget))
            ->where('channel', 'live_chat')
            ->where('instance_id', $widget->id)
            ->where('contact_external_id', $visitorKey)
            ->first();
    }

    private function conversationMetadata(Request $request, LiveChatWidget $widget, array $data): array
    {
        return array_filter([
            'tenant_id' => $this->resolvedTenantId($widget),
            'widget_id' => $widget->id,
            'widget_name' => $widget->name,
            'source' => 'website_widget',
            'visitor_email' => $data['visitor_email'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'origin_host' => $this->originHost($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'ip_hash' => $this->clientIpHash($request),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function messagePayload(ConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'type' => $message->type,
            'body' => $message->body,
            'status' => $message->status,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }

    private function liveEventPayload(?Conversation $conversation, int $afterId): array
    {
        if (!$conversation) {
            return [
                'messages' => [],
                'latest_id' => $afterId,
                'conversation' => null,
                'presence' => null,
                'typing' => [
                    'visitor' => false,
                    'agent' => false,
                ],
            ];
        }

        $messages = ConversationMessage::query()
            ->where('tenant_id', (int) (($conversation->metadata['tenant_id'] ?? null) ?: LiveChatWidget::DEFAULT_TENANT_ID))
            ->where('conversation_id', $conversation->id)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get();

        return [
            'messages' => $messages->map(fn (ConversationMessage $message) => $this->messagePayload($message))->values()->all(),
            'latest_id' => (int) ($messages->last()?->id ?? $afterId),
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
            ],
            'presence' => [
                'agent' => $this->agentPresence($conversation),
            ],
            'typing' => [
                'visitor' => $this->realtimeState->isVisitorTyping((int) ($conversation->instance_id ?? 0), (string) ($conversation->contact_external_id ?? '')),
                'agent' => $this->realtimeState->isAgentTyping((int) $conversation->id, $conversation->owner_id),
            ],
        ];
    }

    private function agentPresence(Conversation $conversation): string
    {
        if (!$conversation->owner_id) {
            return UserPresence::STATUS_OFFLINE;
        }

        $presence = UserPresence::query()->where('user_id', (int) $conversation->owner_id)->first();

        return $presence ? $presence->effectiveStatus() : UserPresence::STATUS_OFFLINE;
    }

    private function ensureOriginAllowed(Request $request, LiveChatWidget $widget): void
    {
        $origin = trim((string) $request->headers->get('Origin', ''));
        if ($origin === '') {
            return;
        }

        $allowedDomains = $widget->allowed_domains ?? [];
        if (empty($allowedDomains)) {
            abort(403, 'Allowed domains untuk widget live chat belum dikonfigurasi.');
        }

        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        abort_unless($originHost !== '' && $this->hostAllowed($originHost, $allowedDomains), 403);
    }

    private function corsHeaders(Request $request, LiveChatWidget $widget): array
    {
        $origin = trim((string) $request->headers->get('Origin', ''));
        if ($origin === '') {
            return [];
        }

        $allowedDomains = $widget->allowed_domains ?? [];
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));

        if (!empty($allowedDomains) && ($originHost === '' || !$this->hostAllowed($originHost, $allowedDomains))) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            'Vary' => 'Origin',
        ];
    }

    private function resolveOrIssueVisitorSession(Request $request, LiveChatWidget $widget, array $data): array
    {
        $requestedVisitorKey = trim((string) ($data['visitor_key'] ?? ''));
        $visitorToken = trim((string) ($data['visitor_token'] ?? ''));

        $session = null;

        if ($requestedVisitorKey !== '' && $visitorToken !== '') {
            $session = $this->findValidSession($widget, $requestedVisitorKey, $visitorToken);
        }

        if ($session) {
            $session->session_token_plain = $visitorToken;
            $session->origin_host = $this->originHost($request);
            $session->ip_address = $this->clientIpHash($request);
            $session->user_agent = Str::limit((string) $request->userAgent(), 500, '');
            $session->last_seen_at = now();
            $session->expires_at = now()->addDays(30);
            $session->save();

            return [$session->visitor_key, $session];
        }

        $visitorKey = (string) Str::uuid();
        $plainToken = Str::random(64);

        $session = new LiveChatVisitorSession([
            'tenant_id' => $this->resolvedTenantId($widget),
            'live_chat_widget_id' => $widget->id,
            'visitor_key' => $visitorKey,
            'session_token_hash' => hash('sha256', $plainToken),
            'origin_host' => $this->originHost($request),
            'ip_address' => $this->clientIpHash($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'last_seen_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        $session->session_token_plain = $plainToken;
        $session->save();

        return [$visitorKey, $session];
    }

    private function authorizeVisitorSession(Request $request, LiveChatWidget $widget, string $visitorKey, string $visitorToken): LiveChatVisitorSession
    {
        $session = $this->findValidSession($widget, $visitorKey, $visitorToken);
        abort_unless($session !== null, 403, 'Visitor session tidak valid atau sudah kadaluarsa.');

        $originHost = $this->originHost($request);
        if ($session->origin_host && $originHost && $session->origin_host !== $originHost) {
            abort(403, 'Origin visitor session tidak cocok.');
        }

        $session->forceFill([
            'origin_host' => $originHost ?: $session->origin_host,
            'ip_address' => $this->clientIpHash($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'last_seen_at' => now(),
            'expires_at' => now()->addDays(30),
        ])->save();

        return $session;
    }

    private function findValidSession(LiveChatWidget $widget, string $visitorKey, string $visitorToken): ?LiveChatVisitorSession
    {
        if ($visitorKey === '' || $visitorToken === '') {
            return null;
        }

        $session = LiveChatVisitorSession::query()
            ->where('live_chat_widget_id', $widget->id)
            ->where('visitor_key', $visitorKey)
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->first();

        if (!$session) {
            return null;
        }

        return hash_equals((string) $session->session_token_hash, hash('sha256', $visitorToken)) ? $session : null;
    }

    private function originHost(Request $request): ?string
    {
        $origin = trim((string) $request->headers->get('Origin', ''));
        if ($origin === '') {
            return null;
        }

        $host = strtolower((string) parse_url($origin, PHP_URL_HOST));

        return $host !== '' ? $host : null;
    }

    private function clientIpHash(Request $request): ?string
    {
        $ip = trim((string) $request->ip());

        return $ip !== '' ? hash('sha256', $ip) : null;
    }

    private function hostAllowed(string $originHost, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain === '') {
                continue;
            }

            if ($domain === '*' || $originHost === $domain || str_ends_with($originHost, '.' . ltrim($domain, '.'))) {
                return true;
            }
        }

        return false;
    }

    private function resolvedTenantId(LiveChatWidget $widget): int
    {
        return (int) ($widget->tenant_id ?: LiveChatWidget::DEFAULT_TENANT_ID);
    }
}
