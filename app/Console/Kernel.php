<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('delete:database-images')->withoutOverlapping()->weeklyOn(0, '4:00')->timezone('Asia/Yangon');
        // $schedule->command('delete:storage-images')->withoutOverlapping()->weeklyOn(0, '4:00')->timezone('Asia/Yangon');
        $schedule->command('fix:variation')->name('fix:variation')->withoutOverlapping()->everyFiveMinutes()->onOneServer();
        $schedule->command('order:assign')->name('order:assign')->withoutOverlapping()->everyMinute()->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
