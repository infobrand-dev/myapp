<?php

namespace App\Modules\SocialMedia;

use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Support\PlanLimit;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SocialMediaServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'social_media.view',
        'social_media.reply',
        'social_media.manage_accounts',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'social_media.view',
            'social_media.reply',
        ],
        'Sales' => [
            'social_media.view',
            'social_media.reply',
        ],
    ];

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::SOCIAL_ACCOUNTS => [
            'table' => 'social_accounts',
            'model' => \App\Modules\SocialMedia\Models\SocialAccount::class,
        ],
    ];

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

        $hooks->register('dashboard.overview.cards', 'socialmedia.dashboard.card', function (): string {
            if (!Schema::hasTable('social_accounts')) {
                return '';
            }

            $tenantId = \App\Support\TenantContext::currentId();
            $total = \App\Modules\SocialMedia\Models\SocialAccount::query()
                ->where('tenant_id', $tenantId)->count();
            $connected = \App\Modules\SocialMedia\Models\SocialAccount::query()
                ->where('tenant_id', $tenantId)->where('is_active', true)->count();

            $plans = app(\App\Support\TenantPlanManager::class);
            $limit = $plans->limit(\App\Support\PlanLimit::SOCIAL_ACCOUNTS, $tenantId);

            return view('socialmedia::dashboard.card', compact('total', 'connected', 'limit'))->render();
        });
    }
}
