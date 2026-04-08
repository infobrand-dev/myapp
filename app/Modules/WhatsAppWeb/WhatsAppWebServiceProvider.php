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
use App\Support\BooleanQuery;
use App\Support\PlanFeature;
use App\Support\RegistersModuleRoutes;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class WhatsAppWebServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'whatsapp_web.view',
        'whatsapp_web.send',
        'whatsapp_web.sync',
        'whatsapp_web.manage_settings',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'whatsapp_web.view',
            'whatsapp_web.send',
            'whatsapp_web.sync',
        ],
        'Sales' => [
            'whatsapp_web.view',
            'whatsapp_web.send',
            'whatsapp_web.sync',
        ],
    ];

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
                return $conversation->channel === 'wa_web' && $user->can('whatsapp_web.view');
            });
            $access->registerParticipateRule('whatsapp_web_admin', function (Conversation $conversation, User $user): bool {
                return $conversation->channel === 'wa_web' && $user->can('whatsapp_web.view');
            });
            $access->registerVisibilityScope('whatsapp_web_admin', function ($query, User $user): void {
                if ($user->can('whatsapp_web.view')) {
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
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
        $this->ensurePermissions();
        $this->registerDashboardHooks();
    }

    private function ensurePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = false;

        foreach (self::PERMISSIONS as $permission) {
            $record = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            $created = $created || $record->wasRecentlyCreated;
        }

        if ($created) {
            app(\App\Support\TenantRoleProvisioner::class)->ensureForAllTenants();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function registerDashboardHooks(): void
    {
        $hooks = $this->app->make(\App\Support\HookManager::class);

        $hooks->register('dashboard.overview.cards', 'whatsapp_web.dashboard.card', function (): string {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_web_settings')) {
                return '';
            }

            $tenantId = \App\Support\TenantContext::currentId();

            if (!app(\App\Support\TenantPlanManager::class)->hasFeature(PlanFeature::WHATSAPP_WEB, $tenantId)) {
                return '';
            }

            $totalQuery = \App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting::query()
                ->where('tenant_id', $tenantId);
            $connectedQuery = \App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('last_test_status', 'ok');
            $total = BooleanQuery::apply($totalQuery, 'is_active', true)->count();
            $connected = BooleanQuery::apply($connectedQuery, 'is_active', true)->count();

            return view('whatsappweb::dashboard.card', compact('total', 'connected'))->render();
        });
    }
}
