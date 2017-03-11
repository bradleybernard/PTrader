<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\TwitterStreamingApi\PublicStream;
use App\Jobs\BuyPastNo;
use App\Jobs\DeleteTweet;
use App\Twitter;
use App\Tweet;
use App\Market;
use Log;
use DB;

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
        app('\App\Http\Controllers\Scrape\TwitterController')->importTweets();
        
        $ids = Twitter::select('twitter_id')->get();
        if(count($ids) == 0) {
            return $this->error('No Twitter accounts exist in twitter table.');
        }

        $map = [];
        foreach($ids as $twitter) {
            $map[$twitter->twitter_id] = 1;
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
        )->whenTweets(implode(',', $ids->pluck('twitter_id')->toArray()), function($tweet) use ($ids, $map) {

            $isTweet = isset($tweet['favorite_count']) && isset($map[$tweet['user']['id']]);
            $isDelete = isset($tweet['delete']) && isset($map[$tweet['delete']['status']['user_id_str']]);

            if($isTweet) {

                if(!$exists = DB::table('tweets')->where('tweet_id', $tweet['id_str'])->first()) {

                    $tweet = Tweet::create([
                        'twitter_id'        => $tweet['user']['id_str'],
                        'tweet_id'          => $tweet['id_str'],
                        'text'              => $tweet['text'],
                        'api_created_at'    => \Carbon\Carbon::parse($tweet['created_at']),
                    ]);

                    Market::where('twitter_id', $tweet->twitter_id)->where('active', true)->where('status', true)->increment('tweets_current', 1);
                    dispatch(new BuyPastNo($tweet));
                }

            } else if($isDelete) {
                dispatch(new DeleteTweet($tweet['delete']['status']));
            }

        })->startListening();
    }

}
