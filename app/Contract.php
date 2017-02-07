<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Trade;
use App\Session;
use Log;

class Contract extends Model
{
    use Traits\SendsRequests;

    protected $guarded = [];
    protected $baseUri  = 'https://www.predictit.org/';

    public function market()
    {
        return $this->belongsTo('App\Market', 'market_id', 'market_id');
    }

    public function bet($account) 
    {
        $this->createClient();

        if(!$session = $account->session) {
            return;
        }

        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);
        $account->createClient()->refreshMoney($jar);

        try {
            $response = $this->client->request('GET', 'Trade/Load' . $this->urlType() .  '?contractId=' . $this->contract_id, ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $html = new \Htmldom((string)$response->getBody());
        $token = $html->find('input[name="__RequestVerificationToken"]', 0)->value;

        $price = ($this->type == 0 ? $this->best_buy_no_cost : $this->best_buy_yes_cost);
        // $quantity = 1;
        $quantity = (int) floor($account->available / $price);

        try {
            $response = $this->client->request('POST', 'Trade/SubmitTrade', [
                'cookies' => $jar,
                'form_params' => [ 
                    '__RequestVerificationToken'        => $token,
                    'BuySellViewModel.ContractId'       => $this->contract_id,
                    'BuySellViewModel.TradeType'        => $this->type,
                    'BuySellViewModel.Quantity'         => $quantity,
                    'BuySellViewModel.PricePerShare'    => $price,
                    'X-Requested-With'                  => 'XMLHttpRequest',
                ],
            ]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        if($response->getStatusCode() != 200) {
            Log::error((string)$response->getBody()); return;
        }

        Trade::create([
            'account_id'        => $session->account_id,
            'session_id'        => $session->id,
            'order_id'          => $this->getOrderId($response),
            'market_id'         => $this->market_id,
            'contract_id'       => $this->contract_id,
            'type'              => $this->type,
            'quantity'          => $quantity,
            'price_per_share'   => $price,
            'total'             => ($quantity * $price),
        ]);

        $account->refreshMoney($jar);
    }

    private function getOrderId(&$response)
    {
        preg_match("/orderId: '([0-9]+)'/", ((string)$response->getBody()), $matches);
        if(!isset($matches[1])) {
            return null;
        }

        return $matches[1];
    }

    private function urlType()
    {
        return $this->type == 0 ? 'Short' : 'Long';
    }
}
