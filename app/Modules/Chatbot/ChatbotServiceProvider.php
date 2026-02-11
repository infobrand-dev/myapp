<?php

namespace App\Modules\Chatbot;

use Illuminate\Support\ServiceProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!config('modules.chatbot.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'chatbot');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
