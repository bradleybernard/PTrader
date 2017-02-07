<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListenForMarketChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for market changes and insert data into database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $controller = app('\App\Http\Controllers\Scrape\MarketController');
        while(true) {
            $controller->pollContracts();
            sleep(5);
        }
    }
}
