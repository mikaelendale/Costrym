<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check for daily tasks every morning at 7 AM and dispatch them
        $schedule->command('tasks:check-daily --dispatch')
            ->dailyAt('07:00')
            ->timezone('UTC')
            ->description('Check and dispatch tasks scheduled for today');

        // Optional: Send daily summary without dispatching at 6 AM
        $schedule->command('tasks:check-daily')
            ->dailyAt('06:00')
            ->timezone('UTC')
            ->description('Show summary of tasks scheduled for today');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
