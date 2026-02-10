<?php

namespace App\Modules\Conversations;

use Illuminate\Support\ServiceProvider;

class ConversationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // future bindings
    }

    public function boot(): void
    {
        if (!config('modules.whatsapp_api.enabled', false)) {
            // still allow internal chat; keep enabled by default
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'conversations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
