<?php

namespace App\Http\Controllers\Stats;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Market;
use App\Twitter;
use App\Tweet;
use App\DeletedTweet;
use App\Contract;
use DB;

class StatsController extends Controller
{
    public function showStats()
    {
        $markets = Market::where('status', true)->where('active', true)->get();
        foreach($markets as $market) {
            $twitter = Twitter::where('twitter_id', $market->twitter_id)->first();
            $contracts = Contract::where('market_id', $market->market_id)->get();
            $count = Tweet::where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->count();
            $deleted = DeletedTweet::where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->count();
            $remaining = \Carbon\Carbon::now()->diffForHumans(\Carbon\Carbon::parse($market->date_end));
            $minutes = \Carbon\Carbon::now()->diffInMinutes(\Carbon\Carbon::parse($market->date_end));
            echo "Market: {$market->short_name}<br/> Twitter: @{$twitter->username}<br/>From: {$market->date_start}<br/>To: {$market->date_end}<br/>Current Time: {$remaining} ({$minutes} mins)<br/>Tweet Count: {$count} tweets<br/>Deleted Tweet Count: {$deleted} <br/>";
            foreach($contracts as $contract) {
                echo "Contract: <a href='/plot/{$contract->contract_id}' target='_blank'>{$contract->short_name}</a> <br/>";
            }
            echo "<br/><br/>";
        }
    }

    public function plot(Request $request, $contractId)
    {
        if(!$contract = Contract::where('contract_id', $contractId)->first()) {
            return "Contract not found";
        }

        $columns = ['last_trade_price', 'best_buy_yes_cost', 'best_buy_no_cost', 'best_sell_yes_cost', 'best_sell_no_cost', 'last_close_price'];
        $history = DB::table('contract_history')->where('contract_id', $contractId)->get();
        
        return view('plot')->with([
            'history' => $history,
            'contract' => $contract,
            'columns' => $columns,
        ]);
    }
}
