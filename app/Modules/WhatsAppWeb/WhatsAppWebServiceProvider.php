<?php

namespace App\Modules\WhatsAppWeb;

use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppWeb\Jobs\SendWhatsAppWebMessage;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebBridgeClient;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebConversationSyncService;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\ServiceProvider;

class WhatsAppWebServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        $this->app->singleton(WhatsAppWebBridgeClient::class);
        $this->app->singleton(WhatsAppWebConversationSyncService::class);
        $this->app->afterResolving(ConversationChannelManager::class, function (ConversationChannelManager $channels): void {
            $channels->register('wa_web', [
                'default_message_type' => 'text',
                'preflight_send_error' => function (Conversation $conversation): ?string {
                    return trim((string) data_get($conversation->metadata, 'client_id', '')) === ''
                        ? 'Client WhatsApp Web untuk percakapan ini belum tersimpan.'
                        : null;
                },
                'ui_features' => [
                    'show_ai_bot' => true,
                ],
                'outbound_persistence_defaults' => [
                    'status' => 'queued',
                    'sent_at' => null,
                ],
            ]);
        });
        $this->app->afterResolving(ConversationAccessRegistry::class, function (ConversationAccessRegistry $access): void {
            $access->registerViewRule('whatsapp_web_admin', function (Conversation $conversation, User $user): bool {
                return $conversation->channel === 'wa_web' && $user->hasRole('Admin');
            });
            $access->registerParticipateRule('whatsapp_web_admin', function (Conversation $conversation, User $user): bool {
                return $conversation->channel === 'wa_web' && $user->hasRole('Admin');
            });
            $access->registerVisibilityScope('whatsapp_web_admin', function ($query, User $user): void {
                if ($user->hasRole('Admin')) {
                    $query->orWhere('channel', 'wa_web');
                }
            });
        });
        $this->app->afterResolving(ConversationOutboundDispatcher::class, function (ConversationOutboundDispatcher $dispatcher): void {
            $dispatcher->register('wa_web', function (ConversationMessage $message): void {
                SendWhatsAppWebMessage::dispatch($message->id);
            });
        });
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappweb');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'whatsappweb');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
