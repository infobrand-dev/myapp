<?php

namespace App\Modules\WhatsAppApi;

use Illuminate\Support\ServiceProvider;

class WhatsAppApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings or singletons can be registered here later.
    }

    public function boot(): void
    {
        if (!config('modules.whatsapp_api.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappapi');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
