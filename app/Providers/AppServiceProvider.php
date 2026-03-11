<?php

namespace App\Providers;

use App\Support\HookManager;
use App\Support\ModuleManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager());
        $this->app->singleton(HookManager::class, fn () => new HookManager());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
