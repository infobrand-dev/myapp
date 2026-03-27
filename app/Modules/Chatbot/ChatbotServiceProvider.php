<?php

namespace App\Modules\Chatbot;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry;
use App\Modules\Chatbot\Services\ConversationBotIntegrationManager;
use App\Modules\Conversations\Contracts\ConversationAiAssistantRegistry;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\ServiceProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        $this->app->singleton(ConversationBotIntegrationRegistry::class, ConversationBotIntegrationManager::class);

        $this->app->afterResolving(ConversationAiAssistantRegistry::class, function (ConversationAiAssistantRegistry $registry): void {
            $registry->registerAccountResolver(function (?int $accountId) {
                if (!$accountId) {
                    return null;
                }

                return ChatbotAccount::query()
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
