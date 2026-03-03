<?php

namespace App\Providers;

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

        // Register active module providers dynamically from DB state.
        try {
            /** @var ModuleManager $modules */
            $modules = $this->app->make(ModuleManager::class);
            foreach ($modules->activeProviders() as $providerClass) {
                $this->app->register($providerClass);
            }
        } catch (\Throwable $e) {
            // Keep core booting even if module metadata is unavailable (fresh install).
        }
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
