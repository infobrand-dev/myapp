<?php

namespace App\Modules\Conversations;

use App\Modules\Conversations\Contracts\ConversationAccessRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Console\Commands\ReleaseExpiredLocks;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Services\ConversationInboxIngester;
use App\Modules\Conversations\Services\ConversationAccessManager;
use App\Modules\Conversations\Services\ConversationChannelRegistry;
use App\Modules\Conversations\Services\ConversationOutboundRegistry;
use App\Support\HookManager;
use App\Support\TenantContext;
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
        $this->app->singleton(ConversationAccessRegistry::class, ConversationAccessManager::class);
        $this->app->singleton(ConversationChannelManager::class, ConversationChannelRegistry::class);
        $this->app->singleton(InboxMessageIngester::class, ConversationInboxIngester::class);
        $this->app->singleton(ConversationOutboundDispatcher::class, ConversationOutboundRegistry::class);
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
                ->where('tenant_id', TenantContext::currentId())
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
                ->where('tenant_id', TenantContext::currentId())
                ->where('conversation_id', (int) $conversation->id)
                ->where('user_id', (int) $user->id)
                ->exists();

            if ($isParticipant) {
                return true;
            }

            return $this->app->make(ConversationAccessRegistry::class)->canView($conversation, $user);
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

            $baseQuery = Conversation::query()->where('tenant_id', TenantContext::currentId());

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

        $query = Conversation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('unread_count', '>', 0);
        $authUser = auth()->user();

        if (!$authUser->hasRole('Super-admin')) {
            $query->where(function ($builder) use ($authUser): void {
                $builder->where('owner_id', $authUser->id)
                    ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $authUser->id));
                $this->app->make(ConversationAccessRegistry::class)->applyVisibilityScope($builder, $authUser);
            });
        }

        return (int) $query->sum('unread_count');
    }
}
