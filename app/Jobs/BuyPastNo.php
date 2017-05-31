<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Tweet;

class BuyPastNo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tweet;

    public function __construct(Tweet $tweet)
    {
        $this->tweet = $tweet;
    }

    public function handle()
    {
        // app('App\Http\Controllers\Bet\BetController')->buyPastNo($this->tweet->twitter_id);
        app('App\Http\Controllers\Bet\BetController')->fastBuyPastNo($this->tweet->twitter_id);
    }
}
