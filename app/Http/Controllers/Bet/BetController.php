<?php

namespace App\Http\Controllers\Bet;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use DB;
use Log;

class BetController extends ScrapeController
{
    protected $baseUri  = 'https://www.predictit.org/';

    public function test() 
    {
        dispatch(new \App\Jobs\PerformTrade('822215679726100480', [
            'twitter_id'        => '822215679726100480',
            'tweet_id'          => '827214173025271811',
            'text'              => 'Great meeting with Harly Davidson!',
            'api_created_at'    => \Carbon\Carbon::now(),
            'created_at'        => \Carbon\Carbon::now(),
        ]));
    }

    public function placeBet($twitterId) 
    {
        $market = $this->findMarket($twitterId);
        $contract = $this->findBestContract($market);

        if(!$contract) {
            return;
        }

        $this->betOnContract($contract);
    }

    private function findMarket($twitterId)
    {
        $market = DB::table('markets')->select(['ticker_symbol', 'market_id'])
                    ->where('twitter_id', $twitterId)
                    ->where('active', true)
                    ->where('status', true)
                    ->first();

        if(!$market) {
            return null;
        }

        return $market;
    }

    private function findBestContract($market) 
    {
        if(!$market) {
            return null;
        }

        try {
            $response = $this->client->request('GET', 'api/marketdata/ticker/' . $market->ticker_symbol);
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

        return (object) [
            'contractId' => $contractId,
            'bestBuyYesCost' => $bestBuyYesCost,
            'marketId' => $market->market_id,
            'type' => 1,
        ];
    }

    private function chooseAccount()
    {
        return DB::table('accounts')->select('id')->where('id', 1)->first();
    }

    private function betOnContract($contract) 
    {
        $account = $this->chooseAccount();
        $session = DB::table('sessions')->where('account_id', $account->id)->where('active', true)->first();
        if(!$session) {
            return;
        }

        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);

        try {
            $response = $this->client->request('GET', 'Trade/LoadLong?contractId=' . $contract->contractId, ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $html = new \Htmldom((string)$response->getBody());
        $token = $html->find('#BuySubmit', 0)->find('input[name="__RequestVerificationToken"]', 0)->value;

        try {
            $response = $this->client->request('POST', 'Trade/SubmitTrade', [
                'cookies' => $jar,
                'form_params' => [ 
                    '__RequestVerificationToken'        => $token,
                    'BuySellViewModel.ContractId'       => $contract->contractId,
                    'BuySellViewModel.TradeType'        => $contract->type,
                    'BuySellViewModel.Quantity'         => 1,
                    'BuySellViewModel.PricePerShare'    => $contract->bestBuyYesCost,
                    'X-Requested-With'                  => 'XMLHttpRequest',
                ],
            ]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        if($response->getStatusCode() == 200) {
            DB::table('trades')->insert([
                'account_id'        => $session->account_id,
                'session_id'        => $session->id,
                'order_id'          => $this->getOrderId($response),
                'market_id'         => $contract->marketId,
                'contract_id'       => $contract->contractId,
                'type'              => $contract->type,
                'quantity'          => 1,
                'price_per_share'   => $contract->bestBuyYesCost,
                'updated_at'        => \Carbon\Carbon::now(),
                'created_at'        => \Carbon\Carbon::now(),
            ]);
        } else {
            Log::error((string)$response->getBody());
        }
    }

    private function getOrderId(&$response)
    {
        preg_match("/orderId: '([0-9]+)'/", ((string)$response->getBody()), $matches);
        return $matches[1];
    }
}
