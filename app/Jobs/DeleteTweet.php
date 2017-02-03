<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Twitter;

class DeleteTweet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tweetId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tweetId)
    {
        $this->tweetId = $tweetId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Twitter::where('tweet_id', $this->tweetId)->delete();
    }
}
