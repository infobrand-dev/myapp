<?php

namespace App\Modules\WhatsAppApi;

use App\Modules\WhatsAppApi\Console\Commands\CheckWhatsAppInstances;
use App\Modules\WhatsAppApi\Console\Commands\DispatchScheduledWABlasts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class WhatsAppApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings or singletons can be registered here later.
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsappapi');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CheckWhatsAppInstances::class,
            DispatchScheduledWABlasts::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('whatsapp:check-instances')->everyTenMinutes();
            $schedule->command('whatsapp:dispatch-scheduled-blasts')->everyMinute();
        });
    }
}
