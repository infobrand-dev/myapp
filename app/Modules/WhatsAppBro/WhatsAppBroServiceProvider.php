<?php

namespace App\Modules\WhatsAppBro;

use Illuminate\Support\ServiceProvider;

class WhatsAppBroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!config('modules.whatsapp_bro.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappbro');
    }
}
