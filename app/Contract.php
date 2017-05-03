<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Trade;
use App\Session;
use App\Share;
use App\Market;
use Log;
use Nexmo;
use App\Jobs\SendText;

class Contract extends Model
{
    use Traits\SendsRequests;

    protected $guarded = [];
    protected $baseUri  = 'https://www.predictit.org/';

    const NO = 0;
    const YES = 1;

    const SELL = 0;
    const BUY = 1;

    public function market()
    {
        return $this->belongsTo('App\Market', 'market_id', 'market_id');
    }

    public function buySingleYes($account)
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
        
        $quantity = (int)floor($account->available / $this->cost);

        try {
            $response = $this->client->request('POST', 'Trade/SubmitTrade', [
                'cookies' => $jar,
                'form_params' => [ 
                    '__RequestVerificationToken'        => $token,
                    'BuySellViewModel.ContractId'       => $this->contract_id,
                    'BuySellViewModel.TradeType'        => $this->tradeType(),
                    'BuySellViewModel.Quantity'         => $quantity,
                    'BuySellViewModel.PricePerShare'    => $this->cost,
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

        $trade = Trade::create([
            'account_id'        => $session->account_id,
            'order_id'          => $this->getOrderId($response),
            'market_id'         => $this->market_id,
            'contract_id'       => $this->contract_id,
            'action'            => $this->action,
            'type'              => $this->type,
            'quantity'          => $quantity,
            'price_per_share'   => $this->cost,
            'total'             => ($this->cost * $quantity),
        ]);

        // Insert shares

        $account->refreshMoney($jar);
    }

    public function buyAllOfSingleNo($account) 
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
            
            if(!isset($parts[0])) {
                Log::error($row->outertext);
                continue;
            }

            $tier = (object) [
                'quantity' => (int)trim($parts[0]->plaintext),
                'price' => (float) (rtrim(trim($parts[1]->plaintext), '¢')/100),
            ];

            if($tier->price <= Market::buyNoMin || $tier->price >= Market::buyNoMax)
                continue;

            $tiers[] = $tier;
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
                return Log::error('Insufficient funds in the account. Balance: ' . $account->available . ' Checkout price: ' . ($tier->quantity * $tier->price));
            }

            // Try to buy them all in full
            // $account->available -= $total;

            $trade = Trade::create([
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

            dispatch(
                (new SendText(
                    $account->phone, 
                    "{$trade->quantity} no shares ($" . $trade->price_per_share . "/share) purchased at $" . $trade->total . " for contract: " . $this->short_name . " in market: {$this->market->short_name}. Current account balance for {$account->name}: $" . $account->available
                ))->onQueue('texts')
            );

            // Insert shares
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
            $short = rtrim($short, "-");
            if(!is_numeric($short))
                throw new \Exception("Invalid parse occured #1.");

            $this->MinTweets = PHP_INT_MIN;
            $this->MaxTweets = (int)$short;
            return;
        }

        // 65+
        if($count == 1 && $short[$len - 1] === '+') {
            $short = rtrim($short, "+");
            if(!is_numeric($short))
                throw new \Exception("Invalid parse occured #2.");

            $this->MinTweets = (int)$short;
            $this->MaxTweets = PHP_INT_MAX;
            return;
        }

        // 56 (no chars after)
        if($count == 1 && ($short[$len - 1] != '+' && $short[$len - 1] != '+')) {
            throw new \Exception("Invalid parse occured #4.");
        }

        // 50 - 54
        //35 or more
        if($count == 3) {
            if($pieces[2] == "more") 
                $pieces[2] = PHP_INT_MAX;
            
            if(!is_numeric($pieces[0]) || !is_numeric($pieces[2]))
                throw new \Exception("Invalid parse occured #3.");

            $this->MinTweets = (int)$pieces[0];
            $this->MaxTweets = (int)$pieces[2];
            return;
        }
    }
}
