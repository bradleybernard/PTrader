<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Tweet;

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
        Tweet::where('tweet_id', $this->tweetId)->delete();
    }
}
