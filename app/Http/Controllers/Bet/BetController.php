<?php

namespace App\Http\Controllers\Bet;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use Log;

use App\Account;
use App\Market;
use App\Contract;
use App\Trade;
use App\Session;
use App\Tweet;
use App\Share;

class BetController extends ScrapeController
{
    protected $baseUri  = 'https://www.predictit.org/';

    public function test() 
    {
        dispatch(new \App\Jobs\BuyPastNo(Tweet::find(1)));
    }

    public function sellPurchasedNoContracts($twitterId) 
    {
        if(!$market = Market::where('twitter_id', $twitterId)->first()) {
            return;
        }

        $stock = Share::where('type', Contract::NO)->where('market_id', $market->market_id)->get();

        // 1. View stock (quantity) of current bought no shares from the current market tied to this twitter account
        // 2. For each bought stock group 
        //      Check if  current tweet count (current - start) is greater than contract.min and less than contract.max 
        //      If so then sell that group, log trade and minus stock and update balance
        //      Else no problem
        
        // $trades = Trade::join('markets', 'markets.market_id', '=', 'trades.market_id')
        //             ->select(['trades.*', 'markets.*', 'trades.id as trade_id'])
        //             ->where('markets.twitter_id', $twitterId)
        //             ->where('active', true)
        //             ->where('status', true)
        //             ->where('action', Contract::BUY)
        //             ->where('type', Contract::NO)
        //             ->get()
        //             ->keyBy('trade_id');

        // $contracts = Contract::whereIn('contract_id', $trades->pluck('contract_id')->unique())->get();
        // foreach($contracts as &$contract) {
        //     $contract->parseRanges();
        // }
    }

    public function buyEarlyYesContract($marketId) 
    {
        $algo = 'early_yes';

        if(!$market = Market::where('market_id', $marketId)->first()) {
            return;
        }

        if(!$contract = $market->findCheapestYesContract()) {
            return;
        }

        if(!$account = $this->chooseAccount($algo)) {
            return;
        }

        $contract->buySingleYes($account);
    }

    public function buyPastNo($twitterId) 
    {
        $algo = 'free_nos';

        if(!$markets = $this->findMarkets($twitterId)) {
            return;
        }

        foreach($markets as $market) {
            if(!$contracts = $market->findPastNoContracts()) {
                return;
            }

            if(!$account = $this->chooseAccount($algo)) {
                return;
            }

            foreach($contracts as $contract) {
                $contract->buyAllOfSingleNo($account);
            }
        }
    }

    public function buyPastNoContracts($contracts) 
    {
        $algo = 'free_nos';

        if(!$account = $this->chooseAccount($algo)) {
            return;
        }

        foreach($contracts as $contract) {
            $contract->buyAllOfSingleNo($account);
        }
    }

    private function findMarket($twitterId)
    {
        $market = Market::where('twitter_id', $twitterId)
                    ->where('active', true)
                    ->where('status', true)
                    ->first();

        if(!$market) {
            return null;
        }

        return $market;
    }

    private function findMarkets($twitterId)
    {
        $markets = Market::where('twitter_id', $twitterId)
                    ->where('active', true)
                    ->where('status', true)
                    ->get();

        if($markets->count() == 0) {
            return null;
        }

        return $markets;
    }

    private function chooseAccount($algorithm = 'free_nos')
    {
        return Account::where('algorithm', $algorithm)->where('available', '>=', '1.00')->orderBy('available', 'desc')->first();
    }
}
