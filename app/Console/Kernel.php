<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Market;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ListenForTweets::class,
        Commands\ListenForMarketChanges::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call('App\Http\Controllers\Scrape\MarketController@scrape')
            ->hourly();

        $schedule->call('App\Http\Controllers\Scrape\TwitterController@verifyCounts')
            ->hourly();

        // maybe should make a daemon for most up-to-date price grabs
        $schedule->call('App\Http\Controllers\Scrape\MarketController@fetchNoPrices')
            ->everyMinute();

        $schedule->call('App\Http\Controllers\Bet\LoginController@createNewAccountSessions')
            ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
