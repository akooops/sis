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
        $schedule->command('model:prune')->daily();

        $schedule->command('pages:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('articles:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('albums:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('brands:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('events:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('achievements:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('job-offers:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('newsletters:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('newsletters:send-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('banners:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('forms:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('forms:close-abandoned')->everyFifteenMinutes()->withoutOverlapping();
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
