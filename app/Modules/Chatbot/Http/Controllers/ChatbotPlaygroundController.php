<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Http\Requests\ChatbotPlaygroundSendRequest;
use App\Modules\Chatbot\Jobs\MirrorPlaygroundTurnToConversation;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotMessage;
use App\Modules\Chatbot\Models\ChatbotSession;
use App\Modules\Chatbot\Services\RagContextBuilder;
use App\Services\AiProviderClient;
use App\Services\AiUsageService;
use App\Services\ByoAiGuardService;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotPlaygroundController extends Controller
{
    private const HISTORY_LIMIT = 12;
    private const HISTORY_ITEM_MAX_CHARS = 1200;
    private const ROLLING_SUMMARY_LINES = 8;
    private const RESPONSE_CACHE_TTL_SECONDS = 600;
    private RagContextBuilder $rag;

    public function __construct(RagContextBuilder $rag)
    {
        $this->rag = $rag;
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $accounts = $this->activeAccounts();
        $sessions = $this->userSessions($request);

        return view('chatbot::playground.index', [
            'accounts' => $accounts,
            'sessions' => $sessions,
            'activeSession' => null,
        ]);
    }

    public function show(Request $request, int $session): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $session = ChatbotSession::query()
            ->where('id', $session)
            ->where('tenant_id', TenantContext::currentId())
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $session->load([
            'messages' => fn ($q) => $q->orderBy('id'),
            'chatbotAccount',
        ]);

        return view('chatbot::playground.index', [
            'accounts' => $this->activeAccounts(),
            'sessions' => $this->userSessions($request),
            'activeSession' => $session,
        ]);
    }

    public function send(ChatbotPlaygroundSendRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $data = $request->validated();

        $user = $request->user();

        $account = ChatbotAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('status', 'active')
            ->findOrFail((int) $data['chatbot_account_id']);

        if (!$account->usesAi()) {
            return redirect('/chatbot/playground')->with('status', 'Chatbot mode Rule-only tidak memakai AI Playground. Siapkan rule/automation di modul automation saat modul itu aktif.');
        }

        $planManager = app(\App\Support\TenantPlanManager::class);
        $tenantId = TenantContext::currentId();

        if ($account->isManagedAi() && !app(AiUsageService::class)->hasCreditsRemaining($tenantId)) {
            return redirect('/chatbot/playground')->with('status', 'AI Credits tenant bulan ini sudah habis.');
        }

        if ($account->isByoAi()) {
            if (!$planManager->hasFeature(PlanFeature::CHATBOT_BYO_AI, $tenantId)) {
                return redirect('/chatbot/playground')->with('status', 'Add-on BYO AI belum aktif untuk tenant ini.');
            }

            if (method_exists($account, 'byoProviderAllowed') && !$account->byoProviderAllowed()) {
                return redirect('/chatbot/playground')->with('status', 'Provider BYO AI chatbot ini tidak diizinkan untuk tenant Anda.');
            }

            $planManager->ensureWithinLimit(PlanLimit::BYO_AI_REQUESTS_MONTHLY, 1, 'Kapasitas request BYO AI bulanan tenant sudah habis.', $tenantId);
            if (($planManager->usageState(PlanLimit::BYO_AI_TOKENS_MONTHLY, $tenantId)['status'] ?? 'ok') !== 'ok'
                && ($planManager->remaining(PlanLimit::BYO_AI_TOKENS_MONTHLY, $tenantId) ?? 1) <= 0) {
                return redirect('/chatbot/playground')->with('status', 'Kapasitas token BYO AI bulanan tenant sudah habis.');
            }
        }

        $session = $this->resolveSession($user->id, $account->id, $data);

        $userMessage = ChatbotMessage::create([
            'tenant_id' => $tenantId,
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $data['message'],
        ]);

        [$history, $rollingSummary] = $this->buildPromptHistory($session);
        $ragContexts = $account->rag_enabled
            ? $this->rag->retrieve($account, (string) $data['message'])
            : [];

        $systemMessages = [['role' => 'system', 'content' => $this->resolveSystemPrompt($account)]];
        if ($rollingSummary !== null && trim($rollingSummary) !== '') {
            $systemMessages[] = ['role' => 'system', 'content' => "Ringkasan konteks sebelumnya:\n{$rollingSummary}"];
        }
        if (!empty($ragContexts)) {
            $contextText = collect($ragContexts)->map(function ($ctx, $i) {
                $n = $i + 1;
                return "[S{$n}] " . ($ctx['title'] ?? 'Dokumen') . "\n" . ($ctx['content'] ?? '');
            })->implode("\n\n");

            $systemMessages[] = [
                'role' => 'system',
                'content' => "Gunakan konteks knowledge base di bawah jika relevan. Jika tidak relevan, jawab normal dan jangan mengarang.\n\n{$contextText}",
            ];
        }

        $payload = [
            'model' => $account->model ?: config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_merge($systemMessages, $history),
            'temperature' => $this->resolveTemperature($account),
            'max_tokens' => 400,
        ];

        $reply = null;
        $usage = null;
        $raw = null;
        $error = null;
        $byoGuardAcquired = false;

        try {
            $cacheKey = $this->responseCacheKey((int) $account->id, $payload);
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $reply = trim((string) ($cached['reply'] ?? ''));
                $usage = is_array($cached['usage'] ?? null) ? $cached['usage'] : null;
                $raw = is_array($cached['raw'] ?? null) ? $cached['raw'] : null;
            } else {
                if ($account->isByoAi()) {
                    app(ByoAiGuardService::class)->acquire($tenantId);
                    $byoGuardAcquired = true;
                }

                $result = app(AiProviderClient::class)->chat(
                    $account->runtimeProvider(),
                    (string) $account->runtimeApiKey(),
                    $account->model ?: config('services.openai.model', 'gpt-4o-mini'),
                    $payload['messages'],
                    (float) $payload['temperature'],
                    (int) $payload['max_tokens'],
                    30,
                );

                $reply = $result['reply'];
                $usage = $result['usage'];
                $raw = $result['raw'];

                if ($reply !== '') {
                    Cache::put($cacheKey, [
                        'reply' => $reply,
                        'usage' => is_array($usage) ? $usage : null,
                        'raw' => is_array($raw) ? $raw : null,
                    ], now()->addSeconds(self::RESPONSE_CACHE_TTL_SECONDS));
                }
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::error('chatbot.playground.ai_exception', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'chatbot_account_id' => $account->id,
                'exception' => get_class($e),
                'error' => Str::limit($error, 300),
            ]);
        } finally {
            if ($byoGuardAcquired) {
                app(ByoAiGuardService::class)->release($tenantId);
            }
        }

        if ($reply === null || $reply === '') {
            $reply = 'Maaf, saya belum bisa memproses permintaan ini sekarang.';
        }

        $assistantMessage = ChatbotMessage::create([
            'tenant_id' => $tenantId,
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $reply,
            'provider_response' => array_filter([
                'raw' => $raw,
                'rag_sources' => !empty($ragContexts) ? array_map(function ($ctx) {
                    return [
                        'chunk_id' => $ctx['chunk_id'] ?? null,
                        'document_id' => $ctx['document_id'] ?? null,
                        'title' => $ctx['title'] ?? null,
                    ];
                }, $ragContexts) : null,
            ]),
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0) ?: null,
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0) ?: null,
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0) ?: null,
        ]);

        if (is_array($usage ?? null) && (int) ($usage['total_tokens'] ?? 0) > 0) {
            app(AiUsageService::class)->recordUsage([
                'tenant_id' => $tenantId,
                'source_module' => 'chatbot',
                'source_type' => 'playground',
                'source_id' => $session->id,
                'chatbot_account_id' => $account->id,
                'provider' => $account->runtimeProvider(),
                'model' => $account->model ?: config('services.openai.model', 'gpt-4o-mini'),
                'billing_mode' => $account->isByoAi() ? 'byo' : 'managed',
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                'metadata' => [
                    'channel' => 'playground',
                    'chatbot_message_id' => $assistantMessage->id,
                ],
            ]);
        }

        $session->update([
            'last_message_at' => now(),
            'title' => $session->title ?: Str::limit(trim((string) $data['message']), 80),
        ]);

        if ($account->mirror_to_conversations) {
            MirrorPlaygroundTurnToConversation::dispatch(
                (int) $account->id,
                (int) $user->id,
                (int) $session->id,
                [(int) $userMessage->id, (int) $assistantMessage->id]
            )->onQueue('default');
        }

        $redirect = redirect('/chatbot/playground/' . $session->id)->with('status', 'Pesan dikirim.');
        if ($error) {
            return $redirect->with('status', 'AI fallback aktif: ' . Str::limit($error, 160));
        }

        return $redirect;
    }

    private function activeAccounts()
    {
        return ChatbotAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function userSessions(Request $request)
    {
        return ChatbotSession::query()
            ->with('chatbotAccount')
            ->where('tenant_id', TenantContext::currentId())
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();
    }

    private function resolveSession(int $userId, int $accountId, array $data): ChatbotSession
    {
        $sessionId = (int) ($data['session_id'] ?? 0);
        if ($sessionId > 0) {
            $session = ChatbotSession::query()
                ->where('id', $sessionId)
                ->where('tenant_id', TenantContext::currentId())
                ->where('user_id', $userId)
                ->where('chatbot_account_id', $accountId)
                ->firstOrFail();

            return $session;
        }

        return ChatbotSession::create([
            'tenant_id' => TenantContext::currentId(),
            'chatbot_account_id' => $accountId,
            'user_id' => $userId,
            'last_message_at' => now(),
        ]);
    }

    private function buildPromptHistory(ChatbotSession $session): array
    {
        $history = ChatbotMessage::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('session_id', $session->id)
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->map(function (ChatbotMessage $msg) {
                return [
                    'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                    'content' => Str::limit((string) ($msg->content ?? ''), self::HISTORY_ITEM_MAX_CHARS, '...'),
                ];
            })
            ->values()
            ->all();

        $total = ChatbotMessage::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('session_id', $session->id)
            ->count();

        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $rollingSummary = isset($metadata['rolling_summary']) ? (string) $metadata['rolling_summary'] : null;

        if ($total > self::HISTORY_LIMIT) {
            $olderLines = ChatbotMessage::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('session_id', $session->id)
                ->orderByDesc('id')
                ->skip(self::HISTORY_LIMIT)
                ->limit(self::ROLLING_SUMMARY_LINES)
                ->get()
                ->reverse()
                ->map(function (ChatbotMessage $msg) {
                    $role = $msg->role === 'assistant' ? 'AI' : 'USER';
                    return $role . ': ' . Str::limit((string) ($msg->content ?? ''), 140, '...');
                })
                ->all();

            if (!empty($olderLines)) {
                $rollingSummary = implode("\n", $olderLines);
                $metadata['rolling_summary'] = $rollingSummary;
                $session->update(['metadata' => $metadata]);
            }
        }

        return [$history, $rollingSummary];
    }

    private function resolveSystemPrompt(ChatbotAccount $account): string
    {
        $base = trim((string) $account->system_prompt);
        if ($base === '') {
            $base = 'Kamu asisten yang ringkas, jelas, dan sopan berbahasa Indonesia.';
        }

        $scope = trim((string) $account->focus_scope);
        if ($scope !== '') {
            $base .= "\nBatasan fokus: {$scope}";
        }

        return $base;
    }

    private function resolveTemperature(ChatbotAccount $account): float
    {
        $style = strtolower((string) $account->response_style);
        if ($style === 'concise') {
            return 0.3;
        }
        if ($style === 'detailed') {
            return 0.7;
        }

        return 0.5;
    }

    private function responseCacheKey(int $accountId, array $payload): string
    {
        return 'chatbot_playground_cache:' . $accountId . ':' . sha1(json_encode($payload));
    }

    private function redirectIfStorageMissing(): ?RedirectResponse
    {
        if (Schema::hasTable('chatbot_accounts') && Schema::hasTable('chatbot_sessions')) {
            return null;
        }

        return redirect()
            ->route('modules.index')
            ->with('status', 'Storage modul Chatbot belum siap. Jalankan install atau aktivasi ulang modul Chatbot agar migration modul dijalankan.');
    }
}
