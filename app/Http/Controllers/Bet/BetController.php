<?php

namespace App\Http\Controllers\Bet;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use DB;
use Log;

class BetController extends ScrapeController
{
    private $lookup = [
        '25073877' => 'TRUMPTWEETS.20817',
        '822215679726100480' => 'POTUSTWEETS.020717',
    ];

    protected $baseUri  = 'https://www.predictit.org/';

    public function test() 
    {
        dispatch(new \App\Jobs\PerformTrade('822215679726100480'));
    }

    public function placeBet($twitterId) 
    {
        $contract = $this->findBestContract($twitterId);

        if(!$contract) {
            return;
        }

        $this->betOnContract($contract);
    }

    private function findBestContract($twitterId) 
    {
        try {
            $response = $this->client->request('GET', 'api/marketdata/ticker/' . $this->lookup[$twitterId]);
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
            'type' => 1,
        ];
    }

    private function betOnContract($contract) 
    {
        $accountId = 1;
        $session = DB::table('sessions')->where('account_id', $accountId)->where('active', true)->first();
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

        // dd($token);

        // try {
        //     $response = $this->client->request('POST', 'Trade/ConfirmTrade', [
        //         'cookies' => $jar,
        //         'form_params' => [ 
        //             '__RequestVerificationToken'    => $token,
        //             'ContractId'                    => $contract->contractId,
        //             'TradeType'                     => 1,
        //             'Quantity'                      => 1,
        //             'PricePerShare'                 => $contract->bestBuyYesCost,
        //             'X-Requested-With'              => 'XMLHttpRequest',
        //         ],
        //     ]);
        // } catch (ClientException $e) {
        //     Log::error($e->getMessage()); return;
        // } catch (ServerException $e) {
        //     Log::error($e->getMessage()); return;
        // }

        // $html = new \Htmldom((string)$response->getBody());
        // $token = $html->find('#BuyTradeSubmit', 0)->find('input[name="__RequestVerificationToken"]', 0)->value;

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
            preg_match("/orderId: '([0-9]+)'/", ((string)$response->getBody()), $matches);
            DB::table('trades')->insert([
                'account_id'        => $session->account_id,
                'session_id'        => $session->id,
                'order_id'          => $matches[1],
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
}
