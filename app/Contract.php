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

        $session = Session::where('account_id', $account->id)->where('active', true)->first();
        if(!$session) {
            return;
        }

        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);

        try {
            $response = $this->client->request('GET', 'Trade/LoadLong?contractId=' . $this->contract_id, ['cookies' => $jar]);
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
                    'BuySellViewModel.ContractId'       => $this->contract_id,
                    'BuySellViewModel.TradeType'        => $this->type,
                    'BuySellViewModel.Quantity'         => 1,
                    'BuySellViewModel.PricePerShare'    => $this->best_buy_yes_cost,
                    'X-Requested-With'                  => 'XMLHttpRequest',
                ],
            ]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        if($response->getStatusCode() != 200) {
            Log::error((string)$response->getBody());
        }

        Trade::create([
            'account_id'        => $session->account_id,
            'session_id'        => $session->id,
            'order_id'          => $this->getOrderId($response),
            'market_id'         => $this->market_id,
            'contract_id'       => $this->contract_id,
            'type'              => $this->type,
            'quantity'          => 1,
            'price_per_share'   => $this->best_buy_yes_cost,
        ]);

        $account->createClient()->refreshMoney();
    }

    private function getOrderId(&$response)
    {
        preg_match("/orderId: '([0-9]+)'/", ((string)$response->getBody()), $matches);
        return $matches[1];
    }
}
