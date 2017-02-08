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
            echo "Market: <a href='/market/{$market->market_id}' target='_blank'>{$market->short_name}</a><br/> Twitter: <a href='https://twitter.com/{$twitter->username}'>@{$twitter->username}</a><br/>From: {$market->date_start}<br/>To: {$market->date_end}<br/>Current Time: {$remaining} ({$minutes} mins)<br/>Tweet Count: {$count} tweets<br/>Deleted Tweet Count: {$deleted} <br/>";
            foreach($contracts as $contract) {
                echo "Contract: <a href='/contract/{$contract->contract_id}' target='_blank'>{$contract->short_name}</a> <br/>";
            }
            echo "<br/><br/>";
        }
    }

    public function market(Request $request, $marketId)
    {
        if(!$market = Market::where('market_id', $marketId)->first()) {
            return 'Market not found!';
        }

        $contracts = $market->contracts;
        $columns = ['best_buy_yes_cost', 'best_buy_no_cost'];
        $select = array_merge($columns, ['created_at']);

        $tweets = Tweet::select(['api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->get();
        $deleted = DeletedTweet::select(['api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->get();

        $all = $tweets->union($deleted);
        $all = $all->sortBy('api_created_at');
        $all = collect($all->values());

        $sum = 0;

        $all->transform(function ($item, $key) use(&$sum) {
            $sum += ($item->getTable() == 'tweets' ? 1 : -1);
            $item->value = $sum;
            return $item;
        });

        $history = [];
        foreach($contracts as $contract) {
            foreach($columns as $column) {
                $history[$contract->contract_id] = DB::table('contract_history')->select($select)->where('contract_id', $contract->contract_id)->get();
            }
        }

        return view('market')->with([
            'history' => $history,
            'market' => $market,
            'contracts' => $contracts,
            'columns' => $columns,
            'tweets' => $all,
        ]);
    }

    public function contract(Request $request, $contractId)
    {
        if(!$contract = Contract::where('contract_id', $contractId)->first()) {
            return 'Contract not found!';
        }

        $columns = ['last_trade_price', 'best_buy_yes_cost', 'best_buy_no_cost', 'best_sell_yes_cost', 'best_sell_no_cost', 'last_close_price'];
        $history = DB::table('contract_history')->where('contract_id', $contractId)->get();

        return view('contract')->with([
            'history' => $history,
            'contract' => $contract,
            'columns' => $columns,
        ]);
    }
}
