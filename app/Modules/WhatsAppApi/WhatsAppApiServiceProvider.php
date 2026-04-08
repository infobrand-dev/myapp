<?php

namespace App\Modules\WhatsAppApi;

use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Console\Commands\PruneWebhookPayloads;
use App\Modules\WhatsAppApi\Http\Controllers\ContactActionController;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Support\BooleanQuery;
use App\Support\HookManager;
use App\Support\RegistersModuleRoutes;
use App\Modules\WhatsAppApi\Console\Commands\CheckWhatsAppInstances;
use App\Modules\WhatsAppApi\Console\Commands\DispatchScheduledWABlasts;
use App\Support\PlanFeature;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class WhatsAppApiServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public const PERMISSIONS = [
        'whatsapp_api.view',
        'whatsapp_api.reply',
        'whatsapp_api.manage_settings',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => self::PERMISSIONS,
        'Customer Service' => [
            'whatsapp_api.view',
            'whatsapp_api.reply',
        ],
        'Sales' => [
            'whatsapp_api.view',
            'whatsapp_api.reply',
        ],
    ];

    public const PLAN_LIMIT_MODELS = [
        \App\Support\PlanLimit::WHATSAPP_INSTANCES => [
            'table' => 'whatsapp_instances',
            'model' => \App\Modules\WhatsAppApi\Models\WhatsAppInstance::class,
        ],
    ];

    public function register(): void
    {
        $chatbotRegistry = \App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry::class;

        $this->app->afterResolving($chatbotRegistry, function ($registry): void {
            $registry->register('wa_api', function (Conversation $conversation): ?array {
                if (!$conversation->instance_id
                    || !class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstanceChatbotIntegration::class)
                    || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instance_chatbot_integrations')) {
                    return null;
                }

                $integration = \Illuminate\Support\Facades\DB::table('whatsapp_instance_chatbot_integrations')
                    ->where('instance_id', (int) $conversation->instance_id)
                    ->first(['auto_reply', 'chatbot_account_id']);

                if (!$integration || empty($integration->chatbot_account_id)) {
                    return null;
                }

                return [
                    'channel' => 'wa_api',
                    'connected' => true,
                    'auto_reply' => (bool) ($integration->auto_reply ?? false),
                    'chatbot_account_id' => (int) $integration->chatbot_account_id,
                ];
            });
        });

        $this->app->afterResolving(ConversationChannelManager::class, function (ConversationChannelManager $channels): void {
            $channels->register('wa_api', [
                'default_message_type' => 'text',
                'preflight_send_error' => function (Conversation $conversation): ?string {
                    if (!$conversation->instance_id) {
                        return 'Instance untuk percakapan WA API tidak ditemukan. Pastikan WA Instance masih aktif.';
                    }

                    if (!class_exists(WhatsAppInstance::class)
                        || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')) {
                        return 'Instance untuk percakapan WA API tidak ditemukan. Pastikan WA Instance masih aktif.';
                    }

                    $exists = \Illuminate\Support\Facades\DB::table('whatsapp_instances')
                        ->where('id', (int) $conversation->instance_id)
                        ->exists();

                    return $exists ? null : 'Instance untuk percakapan WA API tidak ditemukan. Pastikan WA Instance masih aktif.';
                },
                'validate_text_send' => function (Conversation $conversation): ?string {
                    $lastIncomingAt = $conversation->last_incoming_at;
                    if (!$lastIncomingAt || $lastIncomingAt->lt(now()->subHours(24))) {
                        return 'Di luar jendela 24 jam. Gunakan template message untuk mengirim pesan.';
                    }

                    return null;
                },
                'validate_media_send' => function (Conversation $conversation, string $publicUrl): ?string {
                    $lastIncomingAt = $conversation->last_incoming_at;
                    if (!$lastIncomingAt || $lastIncomingAt->lt(now()->subHours(24))) {
                        return 'Di luar jendela 24 jam. Gunakan template message untuk mengirim pesan.';
                    }

                    $provider = \Illuminate\Support\Facades\DB::table('whatsapp_instances')
                        ->where('id', (int) $conversation->instance_id)
                        ->value('provider');

                    if (strtolower((string) $provider) !== 'cloud') {
                        return null;
                    }

                    if (!filter_var($publicUrl, FILTER_VALIDATE_URL)) {
                        return 'Upload file untuk WhatsApp Cloud membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    $scheme = strtolower((string) parse_url($publicUrl, PHP_URL_SCHEME));
                    $host = strtolower((string) parse_url($publicUrl, PHP_URL_HOST));
                    $isValidHost = $host !== '' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

                    if ($scheme !== 'https' || !$isValidHost || !is_dir(public_path('storage'))) {
                        return 'Upload file untuk WhatsApp Cloud membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    if (filter_var($host, FILTER_VALIDATE_IP)
                        && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        return 'Upload file untuk WhatsApp Cloud membutuhkan APP_URL publik HTTPS dan folder public/storage yang bisa diakses dari internet.';
                    }

                    return null;
                },
                'supports_templates' => function (Conversation $conversation): bool {
                    return class_exists(\App\Modules\WhatsAppApi\Models\WATemplate::class)
                        && \Illuminate\Support\Facades\Schema::hasTable('wa_templates');
                },
                'templates' => function (Conversation $conversation) {
                    if ($conversation->channel !== 'wa_api'
                        || !class_exists(\App\Modules\WhatsAppApi\Models\WATemplate::class)
                        || !\Illuminate\Support\Facades\Schema::hasTable('wa_templates')) {
                        return collect();
                    }

                    return \App\Modules\WhatsAppApi\Models\WATemplate::where('status', 'active')
                        ->orderBy('name')
                        ->get();
                },
                'find_template' => function (Conversation $conversation, int $templateId) {
                    if ($conversation->channel !== 'wa_api'
                        || !class_exists(\App\Modules\WhatsAppApi\Models\WATemplate::class)
                        || !\Illuminate\Support\Facades\Schema::hasTable('wa_templates')) {
                        return null;
                    }

                    return \App\Modules\WhatsAppApi\Models\WATemplate::query()
                        ->where('tenant_id', \App\Support\TenantContext::currentId())
                        ->find($templateId);
                },
                'build_template_payload' => function (Conversation $conversation, $template, array $params): ?array {
                    if ($conversation->channel !== 'wa_api' || !$template) {
                        return null;
                    }

                    $header = collect($template->components ?? [])->firstWhere('type', 'header');
                    $headerText = strtolower(data_get($header, 'format')) === 'text'
                        ? data_get($header, 'parameters.0.text')
                        : null;

                    $bodyIndexes = $this->placeholderIndexes($template->body);
                    $headerIndexes = $this->placeholderIndexes($headerText);
                    $allIndexes = array_values(array_unique(array_merge($bodyIndexes, $headerIndexes)));
                    sort($allIndexes);

                    $bodyParams = [];
                    foreach ($bodyIndexes as $idx) {
                        $bodyParams[] = [
                            'type' => 'text',
                            'text' => $params[$idx] ?? '',
                        ];
                    }

                    $headerParams = [];
                    if ($headerText) {
                        foreach ($headerIndexes as $idx) {
                            $headerParams[] = [
                                'type' => 'text',
                                'text' => $params[$idx] ?? '',
                            ];
                        }
                    } elseif ($header && data_get($header, 'parameters.0.link')) {
                        $linkType = strtolower(data_get($header, 'parameters.0.type', 'image'));
                        $headerParams[] = [
                            'type' => $linkType,
                            'link' => data_get($header, 'parameters.0.link'),
                        ];
                    }

                    $components = [];
                    if ($headerParams) {
                        $components[] = [
                            'type' => 'header',
                            'parameters' => $headerParams,
                        ];
                    }
                    if ($bodyParams) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }

                    return [
                        'template_id' => $template->id,
                        'name' => $template->name,
                        'meta_name' => method_exists($template, 'metaTemplateName') ? $template->metaTemplateName() : ($template->meta_name ?: $template->name),
                        'language' => $template->language,
                        'components' => $components,
                        'placeholders' => $allIndexes,
                    ];
                },
                'supports_ai_structured_reply' => function (Conversation $conversation): bool {
                    if ($conversation->channel !== 'wa_api' || !$conversation->instance_id) {
                        return false;
                    }

                    if (!class_exists(WhatsAppInstance::class)
                        || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')) {
                        return false;
                    }

                    $provider = \Illuminate\Support\Facades\DB::table('whatsapp_instances')
                        ->where('tenant_id', \App\Support\TenantContext::currentId())
                        ->where('id', (int) $conversation->instance_id)
                        ->value('provider');

                    return strtolower((string) $provider) === 'cloud';
                },
                'ui_features' => [
                    'show_ai_bot' => true,
                    'show_media_composer' => true,
                    'show_template_composer' => true,
                    'show_contact_crm' => true,
                ],
                'outbound_persistence_defaults' => [
                    'status' => 'queued',
                    'sent_at' => null,
                ],
            ]);
        });
        $this->app->afterResolving(ConversationAccessRegistry::class, function (ConversationAccessRegistry $access): void {
            $access->registerViewRule('whatsapp_api_instance_users', function (Conversation $conversation, User $user): bool {
                if ($conversation->channel !== 'wa_api' || !$conversation->instance_id) {
                    return false;
                }

                if (!class_exists(WhatsAppInstance::class)
                    || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')
                    || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instance_user')) {
                    return false;
                }

                return \Illuminate\Support\Facades\DB::table('whatsapp_instance_user')
                    ->where('instance_id', (int) $conversation->instance_id)
                    ->where('user_id', (int) $user->id)
                    ->exists();
            });
            $access->registerVisibilityScope('whatsapp_api_instance_users', function ($query, User $user): void {
                if (!class_exists(WhatsAppInstance::class)
                    || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')
                    || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instance_user')) {
                    return;
                }

                $instanceIds = \Illuminate\Support\Facades\DB::table('whatsapp_instance_user')
                    ->where('user_id', (int) $user->id)
                    ->pluck('instance_id')
                    ->all();

                if (!empty($instanceIds)) {
                    $query->orWhere(function ($waQuery) use ($instanceIds): void {
                        $waQuery->where('channel', 'wa_api')
                            ->whereIn('instance_id', $instanceIds);
                    });
                }
            });
        });
        $this->app->afterResolving(ConversationOutboundDispatcher::class, function (ConversationOutboundDispatcher $dispatcher): void {
            $dispatcher->register('wa_api', function (ConversationMessage $message): void {
                SendWhatsAppMessage::dispatch($message->id);
            });
        });
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappapi');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'whatsappapi');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
        $this->registerContactHooks();
        $this->registerConversationHooks();
        $this->registerDashboardHooks();
        $this->ensurePermissions();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CheckWhatsAppInstances::class,
            DispatchScheduledWABlasts::class,
            PruneWebhookPayloads::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('whatsapp:check-instances')->everyTenMinutes();
            $schedule->command('whatsapp:dispatch-scheduled-blasts')->everyMinute();
            $schedule->command('whatsapp:prune-webhook-payloads --days=' . (int) config('modules.storage_efficiency.whatsapp_webhook_payload_retention_days', 14))
                ->dailyAt('02:30')
                ->withoutOverlapping()
                ->runInBackground();
        });
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

    private function registerContactHooks(): void
    {
        if (!class_exists(\App\Modules\Contacts\Models\Contact::class)) {
            return;
        }

        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('contacts.index.row_actions', 'whatsapp_api.contact_button', function (array $context): string {
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            return view('whatsappapi::contact-actions.button', compact('contact'))->render();
        });

        $hooks->register('contacts.show.header_actions', 'whatsapp_api.contact_button', function (array $context): string {
            $contact = $context['contact'] ?? null;
            if (!$contact) {
                return '';
            }

            return view('whatsappapi::contact-actions.button', compact('contact'))->render();
        });

        $modalRenderer = function (): string {
            $data = ContactActionController::modalData(auth()->user());
            return view('whatsappapi::contact-actions.modal', $data)->render();
        };

        $hooks->register('contacts.index.after_content', 'whatsapp_api.contact_modal', fn () => $modalRenderer());
        $hooks->register('contacts.show.after_content', 'whatsapp_api.contact_modal', fn () => $modalRenderer());
    }

    private function registerConversationHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('conversations.index.integration_badges', 'whatsapp_api.instance_badges', function (array $context): string {
            $conversation = $context['conversation'] ?? null;
            $instance = $conversation ? $this->resolveConversationInstance($conversation) : null;

            if (!$instance) {
                return '';
            }

            return view('whatsappapi::conversations.hooks.index-badges', [
                'instanceName' => $instance->name,
                'instanceStatus' => $instance->status,
            ])->render();
        });

        $hooks->register('conversations.show.detail_rows', 'whatsapp_api.instance_detail', function (array $context): string {
            $conversation = $context['conversation'] ?? null;
            $instance = $conversation ? $this->resolveConversationInstance($conversation) : null;

            if (!$instance) {
                return '';
            }

            return view('whatsappapi::conversations.hooks.detail-row', [
                'instanceName' => $instance->name,
            ])->render();
        });
    }

    private function resolveConversationInstance(?Conversation $conversation): ?object
    {
        if (!$conversation || $conversation->channel !== 'wa_api' || !$conversation->instance_id) {
            return null;
        }

        if (!class_exists(WhatsAppInstance::class)
            || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('whatsapp_instances')
            ->where('tenant_id', \App\Support\TenantContext::currentId())
            ->where('id', (int) $conversation->instance_id)
            ->first(['name', 'status']);
    }

    private function registerDashboardHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('dashboard.overview.cards', 'whatsapp_api.dashboard.card', function (): string {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')) {
                return '';
            }

            $tenantId = \App\Support\TenantContext::currentId();
            $plans = app(\App\Support\TenantPlanManager::class);

            if (!$plans->hasFeature(PlanFeature::WHATSAPP_API, $tenantId)) {
                return '';
            }

            $totalQuery = \App\Modules\WhatsAppApi\Models\WhatsAppInstance::query()
                ->where('tenant_id', $tenantId);
            $connectedQuery = \App\Modules\WhatsAppApi\Models\WhatsAppInstance::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'connected');
            $total = BooleanQuery::apply($totalQuery, 'is_active', true)->count();
            $connected = BooleanQuery::apply($connectedQuery, 'is_active', true)->count();

            $limit = $plans->limit(\App\Support\PlanLimit::WHATSAPP_INSTANCES, $tenantId);

            return view('whatsappapi::dashboard.card', compact('total', 'connected', 'limit'))->render();
        });
    }

    private function placeholderIndexes(?string $text): array
    {
        if (!$text) {
            return [];
        }

        preg_match_all('/\\{\\{(\\d+)\\}\\}/', $text, $matches);
        $indexes = array_map('intval', $matches[1] ?? []);
        $unique = array_values(array_unique($indexes));
        sort($unique);

        return $unique;
    }
}
