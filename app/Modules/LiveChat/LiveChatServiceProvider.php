<?php

namespace App\Modules\LiveChat;

use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\LiveChat\Support\LiveChatRealtimeState;
use App\Support\RegistersModuleRoutes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class LiveChatServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        $this->app->singleton(LiveChatRealtimeState::class, fn () => new LiveChatRealtimeState());

        $this->app->afterResolving(ConversationChannelManager::class, function (ConversationChannelManager $channels): void {
            $channels->register('live_chat', [
                'default_message_type' => 'text',
                'ui_features' => [
                    'show_ai_bot' => true,
                    'show_contact_crm' => false,
                ],
                'outbound_persistence_defaults' => [
                    'status' => 'sent',
                    'sent_at' => now(),
                ],
            ]);
        });

        $this->app->afterResolving(ConversationAccessRegistry::class, function (ConversationAccessRegistry $access): void {
            $access->registerViewRule('live_chat_admin', function (Conversation $conversation, User $user): bool {
                return $conversation->channel === 'live_chat' && $user->hasRole('Admin');
            });
            $access->registerParticipateRule('live_chat_admin', function (Conversation $conversation, User $user): bool {
                return $conversation->channel === 'live_chat' && $user->hasRole('Admin');
            });
            $access->registerVisibilityScope('live_chat_admin', function ($query, User $user): void {
                if ($user->hasRole('Admin')) {
                    $query->orWhere('channel', 'live_chat');
                }
            });
        });

        $this->app->afterResolving(ConversationOutboundDispatcher::class, function (ConversationOutboundDispatcher $dispatcher): void {
            $dispatcher->register('live_chat', function (ConversationMessage $message): void {
                // Outbound live chat message is already persisted and available to widget polling.
            });
        });
    }

    public function boot(): void
    {
        RateLimiter::for('live-chat-public', function (Request $request) {
            $token = (string) $request->route('token', '');
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(20)->by('live-chat:bootstrap:' . $token . ':' . $ip),
                Limit::perMinute(90)->by('live-chat:messages:' . $token . ':' . $ip),
            ];
        });

        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'livechat');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'livechat');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
