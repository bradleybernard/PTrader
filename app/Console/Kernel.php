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
        $closingSoon = Market::where('active', true)->where('status', true)->where('date_end', '>=', \Carbon\Carbon::now())->count();
        if($closingSoon > 0) {
            $schedule->call('App\Http\Controllers\Scrape\MarketController@scrape')
                ->everyMinute();
        }
            
        $schedule->call('App\Http\Controllers\Bet\LoginController@createNewAccountSessions')
            ->twiceDaily(8, 20)
            ->timezone('America/New_York');
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
