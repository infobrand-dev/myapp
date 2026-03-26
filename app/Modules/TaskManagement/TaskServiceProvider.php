<?php

namespace App\Modules\TaskManagement;

use App\Support\RegistersModuleRoutes;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    use RegistersModuleRoutes;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleRoutes([__DIR__ . '/routes/web.php']);
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'taskmgmt');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'taskmgmt');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
