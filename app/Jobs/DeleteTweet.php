<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Tweet;
use App\DeletedTweet;
use Log; 
use App\Market;
use DB;

class DeleteTweet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tweet;

    public function __construct($tweet)
    {
        $this->tweet = $tweet;
    }

    public function handle()
    {
        $twitterId = null;

        if($exists = DB::table('deleted_tweets')->where('tweet_id', $this->tweet['id_str'])->first()) {
            return;
        }

        if($tweet = Tweet::where('tweet_id', $this->tweet['id_str'])->first()) {
            $twitterId = $tweet->twitter_id;
            $tweet->markDeleted();
        } else {
            $tweet = DeletedTweet::create([
                'twitter_id' => $this->tweet['user_id_str'],
                'tweet_id' => $this->tweet['id_str'],
            ]);
            $twitterId = $tweet->twitter_id;
        }

        Market::where('twitter_id', $twitterId)->where('active', true)->where('status', true)->decrement('tweets_current', 1);
        app('App\Http\Controllers\Bet\BetController')->sellPurchasedNoContracts($this->tweet['user_id_str']);
    }
}
