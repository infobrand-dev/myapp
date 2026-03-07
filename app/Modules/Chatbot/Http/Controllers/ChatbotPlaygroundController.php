<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Jobs\MirrorPlaygroundTurnToConversation;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotMessage;
use App\Modules\Chatbot\Models\ChatbotSession;
use App\Modules\Chatbot\Services\RagContextBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotPlaygroundController extends Controller
{
    private const HISTORY_LIMIT = 12;
    private const HISTORY_ITEM_MAX_CHARS = 1200;
    private const ROLLING_SUMMARY_LINES = 8;
    private RagContextBuilder $rag;

    public function __construct(RagContextBuilder $rag)
    {
        $this->rag = $rag;
    }

    public function index(Request $request): View
    {
        $accounts = $this->activeAccounts();
        $sessions = $this->userSessions($request);

        return view('chatbot::playground.index', [
            'accounts' => $accounts,
            'sessions' => $sessions,
            'activeSession' => null,
        ]);
    }

    public function show(Request $request, int $session): View
    {
        $session = ChatbotSession::query()
            ->where('id', $session)
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

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chatbot_account_id' => ['required', 'integer', 'exists:chatbot_accounts,id'],
            'session_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $user = $request->user();

        $account = ChatbotAccount::query()
            ->where('status', 'active')
            ->findOrFail((int) $data['chatbot_account_id']);

        $session = $this->resolveSession($user->id, $account->id, $data);

        $userMessage = ChatbotMessage::create([
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

        try {
            $response = Http::withToken($account->api_key)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            $raw = $response->json();
            if ($response->successful()) {
                $reply = trim((string) ($response->json('choices.0.message.content') ?? ''));
                $usage = $response->json('usage');
            } else {
                $error = (string) ($response->json('error.message') ?: $response->body() ?: 'Unknown error');
                Log::warning('chatbot.playground.openai_failed', [
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'chatbot_account_id' => $account->id,
                    'http_status' => $response->status(),
                    'request_id' => $response->header('x-request-id'),
                    'error' => Str::limit($error, 300),
                ]);
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::error('chatbot.playground.openai_exception', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'chatbot_account_id' => $account->id,
                'exception' => get_class($e),
                'error' => Str::limit($error, 300),
            ]);
        }

        if ($reply === null || $reply === '') {
            $reply = 'Maaf, saya belum bisa memproses permintaan ini sekarang.';
        }

        $assistantMessage = ChatbotMessage::create([
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
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function userSessions(Request $request)
    {
        return ChatbotSession::query()
            ->with('chatbotAccount')
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
                ->where('user_id', $userId)
                ->where('chatbot_account_id', $accountId)
                ->firstOrFail();

            return $session;
        }

        return ChatbotSession::create([
            'chatbot_account_id' => $accountId,
            'user_id' => $userId,
            'last_message_at' => now(),
        ]);
    }

    private function buildPromptHistory(ChatbotSession $session): array
    {
        $history = ChatbotMessage::query()
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
            ->where('session_id', $session->id)
            ->count();

        $metadata = is_array($session->metadata) ? $session->metadata : [];
        $rollingSummary = isset($metadata['rolling_summary']) ? (string) $metadata['rolling_summary'] : null;

        if ($total > self::HISTORY_LIMIT) {
            $olderLines = ChatbotMessage::query()
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
}
