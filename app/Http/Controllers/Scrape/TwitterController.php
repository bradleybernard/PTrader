<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use App\Twitter;
use App\Tweet;
use TwitterAPI;
use App\Market;
use Log;

class TwitterController extends ScrapeController
{
    public function importTweets()
    {
        $twitters = Twitter::all()->pluck('twitter_id');

        foreach($twitters as $twitterId) {
            
            $tweets = TwitterAPI::getUserTimeline(['user_id' => $twitterId, 'count' => 200, 'format' => 'json']);
            $tweets = json_decode($tweets);
            $insert = collect([]);
            
            foreach($tweets as $tweet) {
                $insert->push([
                    'twitter_id'        => $twitterId,
                    'tweet_id'          => $tweet->id_str,
                    'text'              => $tweet->text,
                    'api_created_at'    => \Carbon\Carbon::parse($tweet->created_at),
                    'created_at'        => \Carbon\Carbon::now(),
                    'updated_at'        => \Carbon\Carbon::now(),
                ]);
            }

            $tweetIds = $insert->pluck('tweet_id');
            $exists = collect(Tweet::whereIn('tweet_id', $tweetIds->toArray())->select('tweet_id')->get());

            if($exists->count() > 0)
                $exists = $exists->pluck('tweet_id');

            $exists = $exists->toArray();

            $filtered = $insert->filter(function ($value, $key) use ($exists) {
                if(!in_array($value['tweet_id'], $exists)) {
                    return $value;
                }
            });

            Tweet::insert($filtered->toArray());
        }
    }

    public function verifyCounts() 
    {

        $twitters = Twitter::all();

        foreach($twitters as $twitter) {

            try {
                $response = $this->client->request('GET', 'https://twitter.com/' . $twitter->username);
            } catch (ClientException $e) {
                Log::error($e->getMessage()); return;
            } catch (ServerException $e) {
                Log::error($e->getMessage()); return;
            }

            $html = new \Htmldom((string)$response->getBody());
            $ele = $html->find('span.ProfileNav-value', 0);
            if(!isset($ele->{'data-count'})) {
                throw new \Exception("Invalid tweet verify count html element.");
            }

            $count = (int)$ele->{'data-count'};

            $markets = Market::where('status', 1)->where('active', 1)->where('twitter_id', $twitter->twitter_id)->where('tweets_current', '<>', $count)->get();
            foreach($markets as $market) {
                Log::error("Market count is wrong\n Market: {$market->short_name} Wrong: {$market->tweets_current} Correct: {$count}");
                $market->update(['tweets_current' => $count]);
            }
        }
    }
}
