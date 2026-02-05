<?php

namespace App\Modules\EmailMarketing;

use Illuminate\Support\ServiceProvider;

class EmailMarketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!config('modules.email_marketing.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'emailmarketing');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
