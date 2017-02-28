<?php

namespace App\Http\Controllers\Stats;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Market;
use App\Twitter;
use App\Tweet;
use App\DeletedTweet;
use App\Contract;
use App\ContractHistory;
use View;
use DB;

class StatsController extends Controller
{
    public function showStats()
    {
        $markets = Market::where('status', true)->orderBy('date_end', 'DESC')->get();
        $columns = ['best_buy_yes_cost', 'best_buy_no_cost', 'best_sell_no_cost', 'best_sell_yes_cost', 'last_close_price', 'last_trade_price'];
        $highlight = ['best_buy_yes_cost', 'best_buy_no_cost', 'last_trade_price', 'last_close_price'];

        foreach($markets as &$market) {
            $market->twitter = Twitter::where('twitter_id', $market->twitter_id)->first();
            $market->contracts = Contract::where('market_id', $market->market_id)->get()->keyBy('contract_id');
            $market->deleted = DeletedTweet::where('twitter_id', $market->twitter_id)->whereBetween('created_at', [$market->date_start, $market->date_end])->count();
            
            foreach($market->contracts as &$contract) {
                $contract->parseRanges();
            }

            if(!($market->status && $market->active)) {
                continue;
            }

            $market->remaining = \Carbon\Carbon::now()->diffForHumans(\Carbon\Carbon::parse($market->date_end));
            $market->minutes = \Carbon\Carbon::now()->diffInMinutes(\Carbon\Carbon::parse($market->date_end));
            
            $cs = $market->contracts->pluck('contract_id');
            $history = ContractHistory::whereIn('contract_id', $cs)->orderBy('id', 'DESC')->take(count($cs))->get()->keyBy('contract_id');
            foreach($history as $cid => $hist) {
                $market->contracts[$cid]->history = $hist;
            }


            $maxes = $mins = [];
            foreach($highlight as $column) {  
                $maxes[$column] = PHP_INT_MIN;
                $mins[$column] = PHP_INT_MAX;
            }

            foreach($history as $contract) {
                foreach($highlight as $column) {
                    $value = $contract->{$column};
                    if($value > 0.99 || $value < 0.01) 
                        continue;

                    $maxes[$column] = max($value, $maxes[$column]);
                    $mins[$column] = min($value, $mins[$column]);
                }
            }
        
            $gMaxes = $gMins = [];
            foreach($history as $contract) {
                foreach($highlight as $column) {
                    $value = $contract->{$column};
                    if($maxes[$column] == $value) {
                        $gMaxes[$column][$contract->contract_id] = true;
                    }
                    if($mins[$column] == $value) {
                        $gMins[$column][$contract->contract_id] = true;
                    }
                }
            }

            $market->maxes = $gMaxes;
            $market->mins = $gMins;
        }

        View::share('columns', $columns);

        return view('markets')->with('markets', $markets);
    }

    public function market(Request $request, $marketId)
    {
        if(!$market = Market::where('market_id', $marketId)->first()) {
            return 'Market not found!';
        }

        $contracts = $market->contracts;
        $columns = ['best_buy_yes_cost', 'best_buy_no_cost'];
        $select = array_merge($columns, ['created_at']);

        $tweets = Tweet::select(['api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = DeletedTweet::select(['created_at as api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = $deleted->mapWithKeys(function ($item) {
            return ['d_' . $item->tweet_id => $item];    
        });

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
            $history[$contract->contract_id] = DB::table('contract_history')->select($select)->where('contract_id', $contract->contract_id)->get();
        }

        return view('market')->with([
            'history' => $history,
            'market' => $market,
            'contracts' => $contracts,
            'columns' => $columns,
            'tweets' => $tweets,
            'deleted' => $deleted,
            'all' => $all,
        ]);
    }

    public function sum(Request $request, $marketId)
    {
        if(!$market = Market::where('market_id', $marketId)->first()) {
            return 'Market not found!';
        }

        $contracts = $market->contracts;
        $columns = ['best_buy_yes_cost', 'best_buy_no_cost'];
        $select = array_merge($columns, ['created_at']);

        $tweets = Tweet::select(['api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = DeletedTweet::select(['created_at as api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = $deleted->mapWithKeys(function ($item) {
            return ['d_' . $item->tweet_id => $item];    
        });

        $all = $tweets->union($deleted);
        $all = $all->sortBy('api_created_at');
        $all = collect($all->values());

        $sum = 0;
        $all->transform(function ($item, $key) use(&$sum) {
            $sum += ($item->getTable() == 'tweets' ? 1 : -1);
            $item->value = $sum;
            return $item;
        });

        $history = DB::table('contract_history')->select($select)->whereIn('contract_id', $contracts->pluck('contract_id'))->orderBy('created_at', 'ASC')->get();

        $sum = [];
        $group = $contracts->count();
        $total = 0;
        $current = 0;
        foreach($history as $event) {
            $total += $event->best_buy_no_cost;
            if (++$current == $group) {
                $sum[] = ['sum' => $total, 'date' => $event->created_at];
                $total = $current = 0;
            }
        }

        return view('sum')->with([
            'sum' => $sum,
            'market' => $market,
            'contracts' => $contracts,
            'columns' => $columns,
            'tweets' => $tweets,
            'deleted' => $deleted,
            'all' => $all,
        ]);
    }

    public function contract(Request $request, $contractId)
    {
        if(!$contract = Contract::where('contract_id', $contractId)->first()) {
            return 'Contract not found!';
        }

        $columns = ['best_buy_yes_cost', 'best_buy_no_cost'];
        $history = DB::table('contract_history')->where('contract_id', $contractId)->get();

        if(!$market = Market::where('market_id', $contract->market_id)->first()) {
            return 'Market not found!';
        }

        $tweets = Tweet::select(['api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('api_created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = DeletedTweet::select(['created_at as api_created_at', 'tweet_id'])->where('twitter_id', $market->twitter_id)->whereBetween('created_at', [$market->date_start, $market->date_end])->get()->keyBy('tweet_id');
        $deleted = $deleted->mapWithKeys(function ($item) {
            return ['d_' . $item->tweet_id => $item];    
        });

        $all = $tweets->union($deleted);
        $all = $all->sortBy('api_created_at');
        $all = collect($all->values());

        $sum = 0;
        $all->transform(function ($item, $key) use(&$sum) {
            $sum += ($item->getTable() == 'tweets' ? 1 : -1);
            $item->value = $sum;
            return $item;
        });

        return view('contract')->with([
            'history' => $history,
            'contract' => $contract,
            'columns' => $columns,
            'tweets' => $tweets,
            'deleted' => $deleted,
            'all' => $all,
            'market' => $market,
        ]);
    }
}
