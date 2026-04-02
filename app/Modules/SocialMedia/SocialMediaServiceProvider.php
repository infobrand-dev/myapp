<?php

namespace App\Modules\SocialMedia;

use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use App\Modules\SocialMedia\Services\SocialPlatformRegistry;
use App\Support\BooleanQuery;
use App\Support\PlanLimit;
use App\Support\RegistersModuleRoutes;
use Illuminate\Support\Facades\App;
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
        $this->app->singleton(SocialPlatformRegistry::class, fn () => new SocialPlatformRegistry());

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

        $this->app->afterResolving(ConversationChannelManager::class, function (ConversationChannelManager $channels): void {
            $channels->register('social_dm', [
                'default_message_type' => 'text',
                'validate_media_send' => function (Conversation $conversation, string $publicUrl): ?string {
                    if (!filter_var($publicUrl, FILTER_VALIDATE_URL)) {
                        return 'Upload file untuk Social DM membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    $scheme = strtolower((string) parse_url($publicUrl, PHP_URL_SCHEME));
                    $host = strtolower((string) parse_url($publicUrl, PHP_URL_HOST));
                    $isValidHost = $host !== '' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

                    if ($scheme !== 'https' || !$isValidHost || !is_dir(public_path('storage'))) {
                        return 'Upload file untuk Social DM membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    if (filter_var($host, FILTER_VALIDATE_IP)
                        && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        return 'Upload file untuk Social DM membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    return null;
                },
                'ui_features' => [
                    'show_ai_bot' => true,
                    'show_contact_crm' => false,
                ],
            ]);
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

        $hooks->register('dashboard.overview.cards', 'socialmedia.dashboard.card', function (): string {
            if (!Schema::hasTable('social_accounts')) {
                return '';
            }

            $tenantId = \App\Support\TenantContext::currentId();
            $total = \App\Modules\SocialMedia\Models\SocialAccount::query()
                ->where('tenant_id', $tenantId)->count();
            $connectedQuery = \App\Modules\SocialMedia\Models\SocialAccount::query()
                ->where('tenant_id', $tenantId);
            $connected = BooleanQuery::apply($connectedQuery, 'is_active', true)->count();

            $plans = app(\App\Support\TenantPlanManager::class);
            $limit = $plans->limit(\App\Support\PlanLimit::SOCIAL_ACCOUNTS, $tenantId);

            return view('socialmedia::dashboard.card', compact('total', 'connected', 'limit'))->render();
        });
    }
}
