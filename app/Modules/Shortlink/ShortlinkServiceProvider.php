<?php

namespace App\Modules\Shortlink;

use App\Support\RegistersModuleRoutes;
use Illuminate\Support\ServiceProvider;

class ShortlinkServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'shortlink');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'shortlink');
        $this->loadMigrationsFrom(\App\Support\ModulePath::migrationDirectory(__DIR__) ?? (__DIR__ . '/Database/Migrations'));
    }
}
