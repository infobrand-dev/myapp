<?php

namespace App\Modules\Conversations;

use App\Modules\Conversations\Console\Commands\ReleaseExpiredLocks;
use App\Modules\Conversations\Models\Conversation;
use App\Support\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ConversationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // future bindings
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'conversations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->registerBroadcastChannels();
        $this->registerDashboardHooks();
        $this->registerSidebarState();
        $this->registerConsoleHooks();
    }

    private function registerBroadcastChannels(): void
    {
        Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
            $conversation = Conversation::query()
                ->select(['id', 'owner_id', 'channel', 'instance_id'])
                ->find((int) $conversationId);

            if (!$conversation) {
                return false;
            }

            if ($user->hasRole('Super-admin')) {
                return true;
            }

            if ((int) $conversation->owner_id === (int) $user->id) {
                return true;
            }

            $isParticipant = DB::table('conversation_participants')
                ->where('conversation_id', (int) $conversation->id)
                ->where('user_id', (int) $user->id)
                ->exists();

            if ($isParticipant) {
                return true;
            }

            if ($conversation->channel === 'wa_api'
                && $conversation->instance_id
                && class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstance::class)
                && Schema::hasTable('whatsapp_instances')
                && Schema::hasTable('whatsapp_instance_user')) {
                return DB::table('whatsapp_instance_user')
                    ->where('instance_id', (int) $conversation->instance_id)
                    ->where('user_id', (int) $user->id)
                    ->exists();
            }

            return false;
        });
    }

    private function registerSidebarState(): void
    {
        View::composer('shared.sidebar', function ($view): void {
            $badges = $view->getData()['moduleNavBadges'] ?? [];
            $badges['conversation_unread_total'] = $this->conversationUnreadTotal();
            $view->with('moduleNavBadges', $badges);
        });
    }

    private function registerDashboardHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        $hooks->register('dashboard.overview.cards', 'conversations.dashboard.card', function (): string {
            $user = auth()->user();

            if (!$user || !Schema::hasTable('conversations')) {
                return '';
            }

            $baseQuery = Conversation::query();

            if (!$user->hasAnyRole(['Super-admin', 'Admin'])) {
                $baseQuery->where(function ($query) use ($user): void {
                    $query->where('owner_id', $user->id)
                        ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $user->id));
                });
            }

            $metrics = [
                'open' => (clone $baseQuery)->where('status', 'open')->count(),
                'claimed' => (clone $baseQuery)->whereNotNull('owner_id')->count(),
                'unread' => (clone $baseQuery)->where('unread_count', '>', 0)->sum('unread_count'),
                'audience' => $user->hasAnyRole(['Super-admin', 'Admin']) ? 'global' : 'personal',
            ];

            return view('conversations::dashboard.card', compact('metrics'))->render();
        });
    }

    private function registerConsoleHooks(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ReleaseExpiredLocks::class,
        ]);

        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)
                ->command('conversations:release-expired-locks')
                ->everyFiveMinutes();
        });
    }

    private function conversationUnreadTotal(): int
    {
        if (!auth()->check() || !Schema::hasTable('conversations')) {
            return 0;
        }

        $query = Conversation::query()->where('unread_count', '>', 0);
        $authUser = auth()->user();

        if (!$authUser->hasRole('Super-admin')) {
            $query->where(function ($builder) use ($authUser): void {
                $builder->where('owner_id', $authUser->id)
                    ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $authUser->id));

                if (Schema::hasTable('whatsapp_instances') && Schema::hasTable('whatsapp_instance_user')) {
                    $instanceIds = DB::table('whatsapp_instance_user')
                        ->where('user_id', $authUser->id)
                        ->pluck('instance_id')
                        ->all();

                    if (!empty($instanceIds)) {
                        $builder->orWhere(function ($waQuery) use ($instanceIds): void {
                            $waQuery->where('channel', 'wa_api')
                                ->whereIn('instance_id', $instanceIds);
                        });
                    }
                }
            });
        }

        return (int) $query->sum('unread_count');
    }
}
