<?php

namespace App\Modules\WhatsAppApi;

use App\Models\User;
use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Http\Controllers\ContactActionController;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Support\HookManager;
use App\Modules\WhatsAppApi\Console\Commands\CheckWhatsAppInstances;
use App\Modules\WhatsAppApi\Console\Commands\DispatchScheduledWABlasts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class WhatsAppApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappapi');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->registerContactHooks();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CheckWhatsAppInstances::class,
            DispatchScheduledWABlasts::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('whatsapp:check-instances')->everyTenMinutes();
            $schedule->command('whatsapp:dispatch-scheduled-blasts')->everyMinute();
        });
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
}
