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
        Commands\CrawGameRecord::class,
        Commands\AllAgentFanyong::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('CrawGameRecord')->everyFiveMinutes();
        $schedule->command('AllAgentFanyong')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/agent-fanyong.log'));
        $schedule->command('GameOpsAudit')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/game-ops-audit.log'));
        $schedule->command('FrontendOpsAudit')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/frontend-ops-audit.log'));
        $schedule->command('XhApiOpsAudit')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/xh-api-ops-audit.log'));
        $schedule->command('WalletOpsAudit')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/wallet-ops-audit.log'));
        $schedule->command('AdminOpsAudit')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/admin-ops-audit.log'));
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
