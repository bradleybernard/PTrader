<?php

namespace App\Http\Controllers\Stats;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Market;
use App\Twitter;
use App\Tweet;
use App\DeletedTweet;

class StatsController extends Controller
{
    public function showStats()
    {
        $markets = Market::where('status', true)->where('active', true);
        foreach($markets as $market) {
            $twitter = Twitter::where('twitter_id', $market->twitter_id)->first();
            $count = Tweet::where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->count();
            $deleted = DeletedTweet::where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->count();
            $remaining = \Carbon\Carbon::now()->diffForHumans(\Carbon\Carbon::parse($market->date_end));
            $minutes = \Carbon\Carbon::now()->diffInMinutes(\Carbon\Carbon::parse($market->date_end));
            echo "Market: {$market->short_name}<br/> Twitter: @{$twitter->username}<br/>From: {$market->date_start}<br/>To: {$market->date_end}<br/>Current Time: {$remaining} ({$minutes} mins)<br/>Tweet Count: {$count} tweets<br/>Deleted Tweet Count: {$deleted} <br/><br/>";
        }
    }
}
