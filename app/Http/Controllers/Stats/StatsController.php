<?php

namespace App\Http\Controllers\Stats;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Market;
use App\Twitter;
use App\Tweet;

class StatsController extends Controller
{
    public function showStats()
    {
        $markets = Market::all();
        foreach($markets as $market) {
            $twitter = Twitter::where('twitter_id', $market->twitter_id)->first();
            $count = Tweet::where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->count();
            echo "Market: {$market->short_name}<br/> Twitter: @{$twitter->username}<br/>From: {$market->date_start}<br/>To: {$market->date_end}<br/>Tweet Count: {$count} tweets<br/><br/>";
        }
    }
}
