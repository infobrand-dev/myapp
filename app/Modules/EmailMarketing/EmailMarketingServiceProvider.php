<?php

namespace App\Modules\EmailMarketing;

use App\Support\RegistersModuleRoutes;
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
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'emailmarketing');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
