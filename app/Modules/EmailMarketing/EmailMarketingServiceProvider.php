<?php

namespace App\Modules\EmailMarketing;

use App\Support\RegistersModuleRoutes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class EmailMarketingServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('email-marketing-public', function (Request $request) {
            $token = (string) $request->route('token', '');
            $ip = (string) $request->ip();
            $routeName = (string) optional($request->route())->getName();

            return [
                Limit::perMinute(60)->by('email-tracking:' . $routeName . ':' . $token . ':' . $ip),
                Limit::perMinute(300)->by('email-tracking:ip:' . $ip),
            ];
        });

        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'emailmarketing');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'emailmarketing');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
