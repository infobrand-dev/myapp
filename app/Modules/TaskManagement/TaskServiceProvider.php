<?php

namespace App\Modules\TaskManagement;

use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!config('modules.task_management.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'taskmgmt');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
