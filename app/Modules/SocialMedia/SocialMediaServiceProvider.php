<?php

namespace App\Modules\SocialMedia;

use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // bindings later
    }

    public function boot(): void
    {
        if (!config('modules.social_media.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'socialmedia');
    }
}
