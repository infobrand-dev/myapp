<?php

namespace App\Modules\Chatbot;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry;
use App\Modules\Chatbot\Services\ConversationBotIntegrationManager;
use App\Modules\Conversations\Contracts\ConversationAiAssistantRegistry;
use App\Support\TenantContext;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::CHATBOT_ACCOUNTS => [
            'table' => 'chatbot_accounts',
            'model' => \App\Modules\Chatbot\Models\ChatbotAccount::class,
        ],
        \App\Support\PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => [
            'table' => 'chatbot_knowledge_documents',
            'model' => \App\Modules\Chatbot\Models\ChatbotKnowledgeDocument::class,
        ],
    ];

    public function register(): void
    {
        $this->app->singleton(ConversationBotIntegrationRegistry::class, ConversationBotIntegrationManager::class);

        $this->app->afterResolving(ConversationAiAssistantRegistry::class, function (ConversationAiAssistantRegistry $registry): void {
            $registry->registerAccountResolver(function (?int $accountId) {
                if (!$accountId) {
                    return null;
                }

                if (!Schema::hasTable('chatbot_accounts')) {
                    return null;
                }

                return ChatbotAccount::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('status', 'active')
                    ->find($accountId);
            });
        });
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'chatbot');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'chatbot');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->registerConversationHooks();
    }

    private function registerConversationHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('conversations.show.actions', 'chatbot.bot-actions', function (array $context): string {
            $conversation = $context['conversation'] ?? null;
            $user = $context['user'] ?? auth()->user();

            if (!$conversation || !$user) {
                return '';
            }

            /** @var \App\Modules\Chatbot\Services\ConversationBotManager $manager */
            $manager = $this->app->make(\App\Modules\Chatbot\Services\ConversationBotManager::class);
            $integration = $manager->integrationForConversation($conversation);

            if (!($integration['connected'] ?? false)) {
                return '';
            }

            $isOwner = (int) ($conversation->owner_id ?? 0) === (int) $user->id;
            $isSuperAdmin = method_exists($user, 'hasRole') && $user->hasRole('Super-admin');

            if (!$isOwner && !$isSuperAdmin) {
                return '';
            }

            $meta = is_array($conversation->metadata) ? $conversation->metadata : [];
            $botPaused = (bool) ($meta['auto_reply_paused'] ?? false);

            return view('chatbot::conversations.actions', [
                'conversation' => $conversation,
                'showPause' => !$botPaused,
                'showResume' => $botPaused,
            ])->render();
        });
    }
}
