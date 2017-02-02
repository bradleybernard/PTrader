<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use Twitter;
use DB;

class TwitterController extends ScrapeController
{
    public function importTweets()
    {
        $twitters = DB::table('twitter')->select('twitter_id')->get();

        foreach($twitters as $twitter) {
            
            $tweets = json_decode(Twitter::getUserTimeline(['user_id' => $twitter->twitter_id, 'count' => 100, 'format' => 'json']));
            $insert = collect([]);
            
            foreach($tweets as $tweet) {
                $insert->push([
                    'twitter_id'        => $twitter->twitter_id,
                    'tweet_id'          => $tweet->id_str,
                    'text'              => $tweet->text,
                    'api_created_at'    => \Carbon\Carbon::parse($tweet->created_at),
                    'created_at'        => \Carbon\Carbon::now(),
                ]);
            }

            $tweetIds = $insert->pluck('tweet_id');
            $exists = collect(DB::table('tweets')->whereIn('tweet_id', $tweetIds->toArray())->select('tweet_id')->get());

            if($exists->count() > 0)
                $exists = $exists->pluck('tweet_id');

            $exists = $exists->toArray();

            $filtered = $insert->filter(function ($value, $key) use ($exists) {
                if(!in_array($value['tweet_id'], $exists)) {
                    return $value;
                }
            });

            DB::table('tweets')->insert($filtered->toArray());
        }
    }
}
