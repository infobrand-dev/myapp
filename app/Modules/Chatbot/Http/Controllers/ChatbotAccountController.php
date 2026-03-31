<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Http\Requests\StoreChatbotAccountRequest;
use App\Modules\Chatbot\Http\Requests\UpdateChatbotAccountRequest;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotDecisionLog;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ChatbotAccountController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $accounts = ChatbotAccount::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('name')
            ->paginate(20);
        $decisionLogs = collect();
        $decisionStats = [
            'reply_sent' => 0,
            'handoff' => 0,
            'error' => 0,
            'no_context' => 0,
            'paused' => 0,
        ];
        $escalationQueue = collect();
        $topKnowledgeDocuments = collect();

        if (Schema::hasTable('chatbot_decision_logs')) {
            $decisionLogs = ChatbotDecisionLog::query()
                ->with('conversation', 'chatbotAccount')
                ->where('tenant_id', TenantContext::currentId())
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            $decisionStats = [
                'reply_sent' => ChatbotDecisionLog::query()->where('tenant_id', TenantContext::currentId())->where('action', 'reply_sent')->count(),
                'handoff' => ChatbotDecisionLog::query()->where('tenant_id', TenantContext::currentId())->where('action', 'handoff')->count(),
                'error' => ChatbotDecisionLog::query()->where('tenant_id', TenantContext::currentId())->where('action', 'error')->count(),
                'no_context' => ChatbotDecisionLog::query()->where('tenant_id', TenantContext::currentId())->where('reason', 'no_context')->count(),
                'paused' => ChatbotDecisionLog::query()->where('tenant_id', TenantContext::currentId())->where('reason', 'paused_for_human')->count(),
            ];

            $escalationQueue = ChatbotDecisionLog::query()
                ->with('conversation', 'chatbotAccount')
                ->where('tenant_id', TenantContext::currentId())
                ->where(function ($query) {
                    $query->where('action', 'handoff')
                        ->orWhereIn('reason', ['low_confidence', 'no_context', 'ai_error']);
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $topDocumentIds = ChatbotDecisionLog::query()
                ->where('tenant_id', TenantContext::currentId())
                ->orderByDesc('id')
                ->limit(200)
                ->get()
                ->flatMap(function (ChatbotDecisionLog $log) {
                    $metadata = is_array($log->metadata) ? $log->metadata : [];
                    return array_values(array_filter((array) ($metadata['knowledge_document_ids'] ?? [])));
                })
                ->countBy()
                ->sortDesc()
                ->take(5);

            if ($topDocumentIds->isNotEmpty()) {
                $topKnowledgeDocuments = ChatbotKnowledgeDocument::query()
                    ->whereIn('id', $topDocumentIds->keys()->all())
                    ->get()
                    ->sortByDesc(fn (ChatbotKnowledgeDocument $document) => (int) ($topDocumentIds[$document->id] ?? 0))
                    ->values();
            }
        }

        return view('chatbot::accounts.index', compact('accounts', 'decisionLogs', 'decisionStats', 'escalationQueue', 'topKnowledgeDocuments'));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $account = new ChatbotAccount([
            'provider' => 'openai',
            'status' => 'active',
            'model' => 'gpt-4o-mini',
            'automation_mode' => 'ai_first',
            'response_style' => 'balanced',
            'operation_mode' => 'ai_only',
            'rag_top_k' => 3,
            'metadata' => [
                'bot_config' => [
                    'auto_reply_enabled' => true,
                    'allowed_channels' => ['wa_api', 'social_dm'],
                    'allow_interactive_buttons' => true,
                    'human_handoff_ack_enabled' => true,
                    'minimum_context_score' => 4,
                    'reply_cooldown_seconds' => 30,
                ],
            ],
        ]);
        return view('chatbot::accounts.form', compact('account'));
    }

    public function store(StoreChatbotAccountRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::CHATBOT_ACCOUNTS);

        $data = $this->validated($request);
        $user = $request->user();
        $data['tenant_id'] = TenantContext::currentId();
        $data['created_by'] = $user ? $user->id : null;
        $data['mirror_to_conversations'] = $request->boolean('mirror_to_conversations');
        $data['rag_enabled'] = $request->boolean('rag_enabled');
        $data['metadata'] = $this->buildMetadata($request, null);
        ChatbotAccount::create($data);
        return redirect()->route('chatbot.accounts.index')->with('status', 'Akun chatbot dibuat.');
    }

    public function edit(ChatbotAccount $account): View
    {
        return view('chatbot::accounts.form', ['account' => $account]);
    }

    public function update(UpdateChatbotAccountRequest $request, ChatbotAccount $account): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['mirror_to_conversations'] = $request->boolean('mirror_to_conversations');
        $data['rag_enabled'] = $request->boolean('rag_enabled');
        $data['metadata'] = $this->buildMetadata($request, is_array($account->metadata) ? $account->metadata : []);
        $account->update($data);
        return redirect()->route('chatbot.accounts.index')->with('status', 'Akun chatbot diperbarui.');
    }

    public function destroy(ChatbotAccount $account): RedirectResponse
    {
        $account->delete();
        return back()->with('status', 'Akun chatbot dihapus.');
    }

    public function testApiKey(Request $request): JsonResponse
    {
        $provider = $request->input('provider', 'openai');
        $apiKey = trim((string) $request->input('api_key', ''));

        if ($apiKey === '') {
            return response()->json(['ok' => false, 'message' => 'API key tidak boleh kosong.']);
        }

        try {
            if ($provider === 'openai') {
                $response = Http::withToken($apiKey)->timeout(8)->get('https://api.openai.com/v1/models');
                return $response->successful()
                    ? response()->json(['ok' => true, 'message' => 'API key OpenAI valid.'])
                    : response()->json(['ok' => false, 'message' => 'API key ditolak oleh OpenAI. Periksa kembali key Anda.']);
            }

            if ($provider === 'anthropic') {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->timeout(8)->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 1,
                    'messages' => [['role' => 'user', 'content' => 'Hi']],
                ]);
                return ($response->status() !== 401)
                    ? response()->json(['ok' => true, 'message' => 'API key Anthropic valid.'])
                    : response()->json(['ok' => false, 'message' => 'API key ditolak oleh Anthropic. Periksa kembali key Anda.']);
            }

            if ($provider === 'groq') {
                $response = Http::withToken($apiKey)->timeout(8)->get('https://api.groq.com/openai/v1/models');
                return $response->successful()
                    ? response()->json(['ok' => true, 'message' => 'API key Groq valid.'])
                    : response()->json(['ok' => false, 'message' => 'API key ditolak oleh Groq. Periksa kembali key Anda.']);
            }

            return response()->json(['ok' => false, 'message' => 'Provider tidak didukung untuk verifikasi.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Tidak dapat terhubung ke server provider. Coba lagi.']);
        }
    }

    private function validated(Request $request, bool $isEdit = false): array
    {
        $data = $request->validated();

        if ($isEdit && !$request->filled('api_key')) {
            unset($data['api_key']);
        }

        if (($data['automation_mode'] ?? $request->input('automation_mode')) === 'rule_only' && !array_key_exists('api_key', $data)) {
            $data['api_key'] = 'rule-only-disabled';
        }

        return $data;
    }

    private function redirectIfStorageMissing(): ?RedirectResponse
    {
        if (Schema::hasTable('chatbot_accounts')) {
            return null;
        }

        return redirect()
            ->route('modules.index')
            ->with('status', 'Storage modul Chatbot belum siap. Jalankan install atau aktivasi ulang modul Chatbot agar migration modul dijalankan.');
    }

    private function buildMetadata(Request $request, ?array $existing = null): array
    {
        $metadata = $existing ?? [];
        $metadata['bot_config'] = [
            'auto_reply_enabled' => $request->boolean('auto_reply_enabled', true),
            'allowed_channels' => array_values(array_filter((array) $request->input('allowed_channels', ['wa_api', 'social_dm']))),
            'allow_interactive_buttons' => $request->boolean('allow_interactive_buttons', true),
            'human_handoff_ack_enabled' => $request->boolean('human_handoff_ack_enabled', true),
            'minimum_context_score' => (float) $request->input('minimum_context_score', 4),
            'reply_cooldown_seconds' => (int) $request->input('reply_cooldown_seconds', 30),
        ];

        return $metadata;
    }
}
