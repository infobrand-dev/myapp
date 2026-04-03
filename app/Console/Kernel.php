<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Mark active subscriptions that have passed their end date as expired.
        // Runs every day at 01:00 server time.
        $schedule->command('subscriptions:check-expiry')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('media:cleanup-orphans')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

