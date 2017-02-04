<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Contract;
use App\Tweet;

class Market extends Model
{
    use Traits\SendsRequests;

    protected $guarded = [];
    protected $baseUri  = 'https://www.predictit.org/';
    
    public function contracts()
    {
        return $this->hasMany('App\Contract', 'market_id', 'market_id');
    }

    public function findBestContract() 
    {
        $this->createClient();
        
        try {
            $response = $this->client->request('GET', 'api/marketdata/ticker/' . $this->ticker_symbol);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $response = json_decode((string)$response->getBody());

        $contractId = null;
        $bestBuyYesCost = PHP_INT_MIN;

        foreach($response->Contracts as $contract) {
            if($contract->Status === 'Open' && $contract->BestBuyYesCost > $bestBuyYesCost) {
                $contractId = $contract->ID;
                $bestBuyYesCost = $contract->BestBuyYesCost;
            }
        }

        if(!$contractId) {
            return null;
        }

        $contract = Contract::select(['id', 'market_id', 'contract_id', 'active', 'status'])->where('contract_id', $contractId)->first();
        $contract->fill([
            'best_buy_yes_cost' => $bestBuyYesCost,
            'type' => 1,
        ]);

        return $contract;
    }

    public function findNoContracts()
    {
        $this->createClient();

        try {
            $response = $this->client->request('GET', 'api/marketdata/ticker/' . $this->ticker_symbol);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $response = json_decode((string)$response->getBody());

        $tweetCount = Tweet::where('twitter_id', $this->twitter_id)->whereBetween('api_created_at', [$this->date_start, $this->date_end])->count();
        $contracts = [];

        foreach($response->Contracts as $contract) {
            $this->parseRanges($contract);
            if($contract->Status === 'Open' && $contract->BestBuyNoCost > 0.00 && $contract->BestBuyNoCost < 0.99 && $tweetCount > $contract->MaxTweets) {
                $model = Contract::select(['id', 'market_id', 'contract_id', 'active', 'status'])->where('contract_id', $contract->ID)->first();
                $model->fill(['best_buy_no_cost' => $contract->BestBuyNoCost, 'type' => 0]);
                $contracts[] = $model;
            }
        }

        if(count($contracts) == 0) {
            return null;
        }

        return $contracts;
    }

    private function parseRanges(&$contract) {
        $short = $contract->ShortName;
        $len = strlen($short);
        $pieces = explode(' ', $short);
        $count = count($pieces);

        // 39-
        if($count == 1 && $short[$len - 1] === '-') {
            $contract->MinTweets = 0;
            $contract->MaxTweets = (int)$short;
        }

        // 65+
        if($count == 1 && $short[$len - 1] === '+') {
            $contract->MinTweets = (int)$short;
            $contract->MaxTweets = PHP_INT_MAX;
        }

        // 50 - 54
        if($count == 3) {
            $contract->MinTweets = (int)$pieces[0];
            $contract->MaxTweets = (int)$pieces[2];
        }
    }
}
