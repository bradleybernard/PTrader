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

    const NO = 0;
    const YES = 1;

    const BUY = 1;
    const SELL = 0;

    public function market()
    {
        return $this->belongsTo('App\Market', 'market_id', 'market_id');
    }

    public function buyNos($account) 
    {
        $this->createClient();

        if(!$session = $account->session) {
            return;
        }

        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);

        try {
            $response = $this->client->request('GET', 'Trade/Load' . $this->urlType() .  '?contractId=' . $this->contract_id, ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $html = new \Htmldom((string)$response->getBody());
        $token = $html->find('input[name="__RequestVerificationToken"]', 0)->value;
        
        $rows = $html->find('div.offers tbody tr');
        $tiers = [];
        foreach($rows as $key => $row) {
            if($key == 0) continue;

            $parts = $row->find('td a');
            $tiers[] = (object) [
                'quantity' => (int)trim($parts[0]->plaintext),
                'price' => (float) (rtrim(trim($parts[1]->plaintext), '¢')/100),
            ];
        }

        foreach($tiers as $tier) {

            $total = $tier->quantity * $tier->price;

            do {
                $tier->quantity -= 1;
                $total = $tier->quantity * $tier->price;
            } while($total > $account->available);

            if($tier->quantity < 1) {
                continue;
            }

            try {
                $response = $this->client->request('POST', 'Trade/SubmitTrade', [
                    'cookies' => $jar,
                    'form_params' => [ 
                        '__RequestVerificationToken'        => $token,
                        'BuySellViewModel.ContractId'       => $this->contract_id,
                        'BuySellViewModel.TradeType'        => $this->tradeType(),
                        'BuySellViewModel.Quantity'         => $tier->quantity,
                        'BuySellViewModel.PricePerShare'    => $tier->price,
                        'X-Requested-With'                  => 'XMLHttpRequest',
                    ],
                ]);
            } catch (ClientException $e) {
                Log::error($e->getMessage()); return;
            } catch (ServerException $e) {
                Log::error($e->getMessage()); return;
            }

            if($response->getStatusCode() != 200) {
                return Log::error("Bad HTTP code: " . $response->getStatusCode() . "\n\n" . (string)$response->getBody()); 
            }

            $content = (string)$response->getBody();
            if(strpos($content, 'There was a problem creating your offer') !== false) {
                return Log::error('Might have yes or no contracts preventing you from purchasing the opposite contract. ContractId: ' . $this->contract_id . ' Type: ' . $this->type); 
            } else if(strpos($content, 'You do not have sufficient funds to make this offer') !== false) {
                return Log::error('Insufficient funds in the account. Balance: ' . $account->available . ' Checkout price: ' . $price);
            }

            $account->available -= $total;

            Trade::create([
                'account_id'        => $session->account_id,
                'order_id'          => $this->getOrderId($response),
                'market_id'         => $this->market_id,
                'contract_id'       => $this->contract_id,
                'action'            => $this->action,
                'type'              => $this->type,
                'quantity'          => $tier->quantity,
                'price_per_share'   => $tier->price,
                'total'             => ($tier->quantity * $tier->price),
            ]);
        }

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
        return $this->type == Contract::NO ? 'Short' : 'Long';
    }

    private function tradeType()
    {
        if($this->action == Contract::BUY) {
            return $this->type == Contract::NO ? 0 : 1;
        } else {
            return $this->type == Contract::NO ? 2 : 3;
        }
    }

    public function parseRanges() {
        $short = $this->short_name;
        $len = strlen($short);
        $pieces = explode(' ', $short);
        $count = count($pieces);

        // 39-
        if($count == 1 && $short[$len - 1] === '-') {
            $this->MinTweets = PHP_INT_MIN;
            $this->MaxTweets = (int)$short;
        }

        // 65+
        if($count == 1 && $short[$len - 1] === '+') {
            $this->MinTweets = (int)$short;
            $this->MaxTweets = PHP_INT_MAX;
        }

        // 50 - 54
        if($count == 3) {
            $this->MinTweets = (int)$pieces[0];
            $this->MaxTweets = (int)$pieces[2];
        }
    }
}
