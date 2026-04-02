<?php

namespace App\Modules\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Http\Requests\StoreChatbotAccountRequest;
use App\Modules\Chatbot\Http\Requests\UpdateChatbotAccountRequest;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotDecisionLog;
use App\Modules\Chatbot\Models\ChatbotKnowledgeDocument;
use App\Services\AiProviderClient;
use App\Support\ByoAiAddon;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

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
            'access_scope' => 'public',
            'ai_source' => 'managed',
            'provider' => 'openai',
            'status' => 'active',
            'model' => 'gpt-4o-mini',
            'automation_mode' => 'ai_first',
            'response_style' => 'balanced',
            'operation_mode' => 'ai_then_human',
            'rag_top_k' => 3,
            'metadata' => [
                'bot_config' => [
                    'auto_reply_enabled' => true,
                    'allowed_channels' => ['wa_api', 'wa_web', 'social_dm'],
                    'allow_interactive_buttons' => true,
                    'human_handoff_ack_enabled' => true,
                    'minimum_context_score' => 4,
                    'reply_cooldown_seconds' => 30,
                    'max_bot_replies_per_conversation' => 0,
                ],
            ],
        ]);
        return view('chatbot::accounts.form', $this->formViewData($account));
    }

    public function store(StoreChatbotAccountRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfStorageMissing()) {
            return $redirect;
        }

        $plans = app(TenantPlanManager::class);
        $plans->ensureWithinLimit(PlanLimit::CHATBOT_ACCOUNTS);

        $data = $this->validated($request);
        $this->ensureByoEligibility($data);
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
        return view('chatbot::accounts.form', $this->formViewData($account));
    }

    public function update(UpdateChatbotAccountRequest $request, ChatbotAccount $account): RedirectResponse
    {
        $data = $this->validated($request, true);
        $this->ensureByoEligibility($data, $account);
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

        $allowedProviders = $this->allowedByoProvidersForTenant();
        if (!in_array((string) $provider, $allowedProviders, true)) {
            return response()->json(['ok' => false, 'message' => 'Provider ini belum diizinkan untuk add-on BYO AI tenant Anda.']);
        }

        try {
            $ok = app(AiProviderClient::class)->verifyApiKey((string) $provider, $apiKey);

            return response()->json([
                'ok' => $ok,
                'message' => $ok
                    ? 'API key provider valid.'
                    : 'API key ditolak oleh provider. Periksa kembali key Anda.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Tidak dapat terhubung ke server provider. Coba lagi.']);
        }
    }

    private function validated(Request $request, bool $isEdit = false): array
    {
        $data = $request->validated();
        $behaviorMode = strtolower((string) ($data['behavior_mode'] ?? $request->input('behavior_mode', 'ai_then_human')));
        [$automationMode, $operationMode] = $this->mapBehaviorMode($behaviorMode);
        $data['automation_mode'] = $automationMode;
        $data['operation_mode'] = $operationMode;
        $data['ai_source'] = strtolower((string) ($data['ai_source'] ?? $request->input('ai_source', 'managed')));
        $data['access_scope'] = strtolower((string) ($data['access_scope'] ?? $request->input('access_scope', 'public')));

        if ($data['ai_source'] !== 'byo') {
            $data['provider'] = 'openai';
        }

        if ($isEdit && !$request->filled('api_key')) {
            unset($data['api_key']);
        }

        if (($data['automation_mode'] ?? $request->input('automation_mode')) === 'rule_only' && !array_key_exists('api_key', $data)) {
            $data['api_key'] = 'rule-only-disabled';
        }

        if (($data['ai_source'] ?? 'managed') === 'managed' && !array_key_exists('api_key', $data)) {
            $data['api_key'] = 'managed-platform-key';
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
            'allowed_channels' => array_values(array_filter((array) $request->input('allowed_channels', ['wa_api', 'wa_web', 'social_dm']))),
            'allow_interactive_buttons' => $request->boolean('allow_interactive_buttons', true),
            'human_handoff_ack_enabled' => $request->boolean('human_handoff_ack_enabled', true),
            'minimum_context_score' => (float) $request->input('minimum_context_score', 4),
            'reply_cooldown_seconds' => (int) $request->input('reply_cooldown_seconds', 30),
            'max_bot_replies_per_conversation' => (int) $request->input('max_bot_replies_per_conversation', 0),
        ];

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(ChatbotAccount $account): array
    {
        $planManager = app(TenantPlanManager::class);
        $tenantId = TenantContext::currentId();

        return [
            'account' => $account,
            'byoEnabled' => $planManager->hasFeature(PlanFeature::CHATBOT_BYO_AI, $tenantId),
            'byoUsageStates' => [
                'accounts' => $planManager->usageState(PlanLimit::BYO_CHATBOT_ACCOUNTS, $tenantId),
                'requests' => $planManager->usageState(PlanLimit::BYO_AI_REQUESTS_MONTHLY, $tenantId),
                'tokens' => $planManager->usageState(PlanLimit::BYO_AI_TOKENS_MONTHLY, $tenantId),
            ],
            'byoProviders' => ByoAiAddon::providers(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureByoEligibility(array $data, ?ChatbotAccount $existing = null): void
    {
        if (($data['ai_source'] ?? 'managed') !== 'byo') {
            return;
        }

        $plans = app(TenantPlanManager::class);
        $tenantId = TenantContext::currentId();
        $plans->ensureFeature(PlanFeature::CHATBOT_BYO_AI, 'Add-on BYO AI belum aktif untuk tenant ini.', $tenantId);

        $isNewByo = !$existing || !$existing->isByoAi();
        if ($isNewByo) {
            $plans->ensureWithinLimit(PlanLimit::BYO_CHATBOT_ACCOUNTS, 1, null, $tenantId);
        }

        $provider = strtolower((string) ($data['provider'] ?? 'openai'));
        if (!in_array($provider, $this->allowedByoProvidersForTenant(), true)) {
            throw ValidationException::withMessages([
                'provider' => 'Provider BYO AI ini belum diizinkan untuk tenant Anda. Hubungi tim platform untuk penyesuaian add-on.',
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function allowedByoProvidersForTenant(): array
    {
        $subscription = app(TenantPlanManager::class)->currentSubscription(TenantContext::currentId());
        $allowed = data_get($subscription?->meta, 'byo_ai.allowed_providers', []);

        return array_values(array_filter((array) $allowed, fn ($provider) => is_string($provider) && trim($provider) !== ''));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapBehaviorMode(string $behaviorMode): array
    {
        return match ($behaviorMode) {
            'rule_only' => ['rule_only', 'ai_only'],
            'ai_only' => ['ai_first', 'ai_only'],
            default => ['ai_first', 'ai_then_human'],
        };
    }
}
