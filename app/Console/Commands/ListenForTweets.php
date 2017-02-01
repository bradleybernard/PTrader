<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\TwitterStreamingApi\PublicStream;
use App\Jobs\PerformTrade;

class ListenForTweets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for tweets from specific accounts.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ids = [
            'realDonaldTrump' => '25073877',
            'potus' => '822215679726100480',
        ];

        $map = [];

        foreach($ids as $user => $id) {
            $map[$id] = 1;
        }

        $twitter = [
            'access_token' => env('TWITTER_ACCESS_TOKEN'),
            'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
            'consumer_key' => env('TWITTER_CONSUMER_KEY'),
            'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
        ];

        PublicStream::create(
            $twitter['access_token'],
            $twitter['access_token_secret'],
            $twitter['consumer_key'],
            $twitter['consumer_secret']
        )->whenTweets(implode(',', $ids), function($tweet) use ($ids, $map) {
            if(isset($tweet['favorite_count']) && isset($map[$tweet['user']['id']])) {
                echo \Carbon\Carbon::now() . " => {$tweet['user']['screen_name']} just tweeted {$tweet['text']}";
                dispatch(new PerformTrade($tweet['user']['id']));
            }
        })->startListening();
    }
}
