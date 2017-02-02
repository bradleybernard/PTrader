<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;

class PerformTrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $twitterId;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($twitterId, $data)
    {
        $this->twitterId = $twitterId;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('tweets')->insert($this->data);
        app('App\Http\Controllers\Bet\BetController')->placeBet($this->twitterId);
    }
}
