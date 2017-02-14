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

    public function findCheapestYesContract()
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
        $bestBuyYesCost = PHP_INT_MAX;

        foreach($response->Contracts as $contract) {
            if($contract->Status === 'Open' && $contract->BestBuyYesCost <= $bestBuyYesCost && $contract->BestBuyYesCost > 0.00 && $contract->BestBuyYesCost < 1.00) {
                $contractId = $contract->ID;
                $bestBuyYesCost = $contract->BestBuyYesCost;
            }
        }

        if(!$contractId) {
            return null;
        }

        $contract = Contract::select(['id', 'market_id', 'contract_id', 'active', 'status'])->where('contract_id', $contractId)->first();
        $contract->fill([
            'cost' => $bestBuyYesCost,
            'type' => Contract::YES,
            'action' => Contract::BUY,
        ]);

        return $contract;
    }

    public function findMaxYesContract() 
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
            'cost' => $bestBuyYesCost,
            'type' => Contract::YES,
            'action' => Contract::BUY,
        ]);

        return $contract;
    }

    public function findPastNoContracts()
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

        $tweetCount = ($this->tweets_current - $this->tweets_start);
        $contracts = [];

        foreach($response->Contracts as $contract) {
            $this->parseRanges($contract);
            if($contract->Status === 'Open' && $contract->BestBuyNoCost > 0.00 && $contract->BestBuyNoCost < 0.98 && $tweetCount > $contract->MaxTweets) {
                $model = Contract::select(['id', 'market_id', 'contract_id', 'active', 'status'])->where('contract_id', $contract->ID)->first();
                $model->fill(['cost' => $contract->BestBuyNoCost, 'action' => Contract::BUY, 'type' => Contract::NO]);
                $contracts[] = $model;
            }
        }

        if(count($contracts) == 0) {
            return null;
        }

        return $contracts;
    }

    public function parseRanges(&$contract) {
        $short = $contract->ShortName;
        $len = strlen($short);
        $pieces = explode(' ', $short);
        $count = count($pieces);

        // 39-
        if($count == 1 && $short[$len - 1] === '-') {
            $contract->MinTweets = PHP_INT_MIN;
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
