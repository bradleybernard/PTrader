<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Tweet;
use Log;

class DeleteTweet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tweetId;

    public function __construct($tweetId)
    {
        $this->tweetId = $tweetId;
    }

    public function handle()
    {
        if(!$tweet = Tweet::where('tweet_id', $this->tweetId)->first()) {
            Log::error("Tweet delete but didn't exist in first place: " . $this->tweetId);
            return;
        }

        $tweet->markDeleted();
    }
}
