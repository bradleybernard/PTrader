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

class BetController extends ScrapeController
{
    protected $baseUri  = 'https://www.predictit.org/';

    public function test() 
    {
        dispatch(new \App\Jobs\BuyAllNosLessThanTweetCount(Tweet::find(1)));
    }

    public function revertNoBets($twitterId) 
    {
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

    public function placeNoBets($twitterId) 
    {
        if(!$market = $this->findMarket($twitterId)) {
            return;
        }

        if(!$contracts = $market->findNoContracts()) {
            return;
        }

        $account = $this->chooseAccount();

        foreach($contracts as $contract) {
            $contract->buyNos($account);
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

    private function chooseAccount()
    {
        return Account::where('id', 1)->first();
    }
}
