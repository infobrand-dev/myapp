<?php

namespace App\Modules\SocialMedia;

use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        $chatbotRegistry = \App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry::class;

        $this->app->afterResolving($chatbotRegistry, function ($registry): void {
            $registry->register('social_dm', function (Conversation $conversation): ?array {
                if (!$conversation->instance_id || !Schema::hasTable('social_account_chatbot_integrations')) {
                    return null;
                }

                $integration = DB::table('social_account_chatbot_integrations')
                    ->where('social_account_id', (int) $conversation->instance_id)
                    ->first(['auto_reply', 'chatbot_account_id']);

                if (!$integration || empty($integration->chatbot_account_id)) {
                    return null;
                }

                return [
                    'channel' => 'social_dm',
                    'connected' => true,
                    'auto_reply' => (bool) ($integration->auto_reply ?? false),
                    'chatbot_account_id' => (int) $integration->chatbot_account_id,
                ];
            });
        });

        $this->app->afterResolving(ConversationOutboundDispatcher::class, function (ConversationOutboundDispatcher $dispatcher): void {
            $dispatcher->register('social_dm', function (ConversationMessage $message): void {
                SendSocialMessage::dispatch($message->id);
            });
        });
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'socialmedia');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'socialmedia');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
