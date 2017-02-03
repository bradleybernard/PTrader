<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Contract;

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
}
