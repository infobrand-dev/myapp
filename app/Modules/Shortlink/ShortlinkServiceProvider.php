<?php

namespace App\Modules\Shortlink;

use Illuminate\Support\ServiceProvider;

class ShortlinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!config('modules.shortlink.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'shortlink');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
