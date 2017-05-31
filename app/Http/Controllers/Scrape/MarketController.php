<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use TwitterAPI;

use App\Jobs\BuyYesEarly;
use App\Jobs\BuyPastNoContinuous;
use App\Market;
use App\Contract;
use App\Twitter;
use App\Account;
use App\ContractHistory;
use App\Tier;
use Symfony\Component\Process\Process;
use DB;

class MarketController extends ScrapeController
{
    private $search = 'How many tweets will @';
    private $timezone = 'America/New_York';

    public function pollContracts()
    {        
        $markets = Market::where('status', true)->where('active', true)->get();
        foreach($markets as $market) {
            try {
                $response = $this->client->request('GET', 'ticker/' . $market->ticker_symbol);
            } catch (ClientException $e) {
                Log::error($e->getMessage()); return;
            } catch (ServerException $e) {
                Log::error($e->getMessage()); return;
            }

            $response = json_decode((string)$response->getBody());
            
            $count = ($market->tweets_current - $market->tweets_start);

            $history = [];
            $contracts = [];
            foreach($response->Contracts as $contract) {

                $market->parseRanges($contract);
                if($contract->Status === 'Open' && $contract->BestBuyNoCost > Market::buyNoMin && $contract->BestBuyNoCost < Market::buyNoMax && $count > $contract->MaxTweets) {
                    $model = Contract::select(['id', 'market_id', 'contract_id', 'short_name', 'active', 'status'])->where('contract_id', $contract->ID)->first();
                    $model->fill(['cost' => $contract->BestBuyNoCost, 'action' => Contract::BUY, 'type' => Contract::NO]);
                    $contracts[] = $model;
                }

                // $history[] = [
                //     'contract_id' => $contract->ID,
                //     'last_trade_price' => $this->clean($contract->LastTradePrice),
                //     'best_buy_yes_cost' => $this->clean($contract->BestBuyYesCost),
                //     'best_buy_no_cost' => $this->clean($contract->BestBuyNoCost, true),
                //     'best_sell_yes_cost' => $this->clean($contract->BestSellYesCost, false),
                //     'best_sell_no_cost' => $this->clean($contract->BestSellNoCost),
                //     'last_close_price' => $this->clean($contract->LastClosePrice),
                // ];
            }

            if(count($contracts) > 0) {
                dispatch(new BuyPastNoContinuous($contracts));
            }

            if(count($history) == 0) {
                continue;
            }

            //ContractHistory::insert($history);
        }
    }

    public function scrape()
    {
        try {
            $response = $this->client->request('GET', 'all');
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        if($response->getStatusCode() != 200) {
            Log::error("MarketController::scrape() returned HTTP" . $response->getStatusCode()); return;
        }

        $response = json_decode((string)$response->getBody());
        $markets = [];

        foreach($response->Markets as $market) {
            if(strpos($market->Name, $this->search) === false) {
                continue;
            }

            $from = trim($this->getStringBetween($market->Name, 'from', 'to'))  . ' ' . $this->timezone;
            $to = trim($this->getStringBetween($market->Name, 'to', '?')) . ' ' . $this->timezone;

            $markets[] = [
                'market_id' => $market->ID,
                'twitter_id' => $this->getTwitterId($market->Name),
                'status' => ($market->Status == 'Open' ? true : false),
                'active' => true,
                'name'  => $market->Name,
                'short_name' => $market->ShortName,
                'ticker_symbol' => $market->TickerSymbol,
                'image' => $market->Image,
                'url' => $market->URL,
                'date_start' => \Carbon\Carbon::parse($from)->setTimezone('UTC'),
                'date_end' => \Carbon\Carbon::parse($to)->setTimezone('UTC'),
                'updated_at' => \Carbon\Carbon::now(),
                'created_at' => \Carbon\Carbon::now(),
            ];

            $contracts = collect([]);

            foreach($market->Contracts as $contract) {
                $contracts->push([
                    'market_id' => $market->ID,
                    'contract_id' => $contract->ID,
                    'active' => true,
                    'short_name' => $this->fixShortName($contract),
                    'long_name' => $contract->LongName,
                    'ticker_symbol' => $contract->TickerSymbol,
                    'status' => ($contract->Status == 'Open' ? true : false),
                    'image' => $contract->Image,
                    'url' => $contract->URL,
                    'date_end' => \Carbon\Carbon::parse($contract->DateEnd . ' EST')->setTimezone('UTC'),
                    'updated_at' => \Carbon\Carbon::now(),
                    'created_at' => \Carbon\Carbon::now(),
                ]);
            }

            $exists = collect(Contract::whereIn('contract_id', $contracts->pluck('contract_id'))->get());

            if($exists->count() > 0)
                $exists = $exists->pluck('contract_id');

            $exists = $exists->toArray();

            $filtered = $contracts->filter(function ($value, $key) use ($exists) {
                if(!in_array($value['contract_id'], $exists)) {
                    return $value;
                }
            });

            Contract::insert($filtered->toArray());
        }

        Market::where('active', true)->update(['active' => false]);

        if(count($markets) > 0) {
            foreach($markets as $market) {
                $model = Market::firstOrNew(['market_id' => $market['market_id']]);
                $model->fill($market);
                if(!$model->exists) {
                    $this->setStartCount($model);
                    $model->save();
                    dispatch(new BuyYesEarly($model->market_id));
                } else {
                    $model->save();
                }
            }
        }
    }

    public function fetchNoPrices() 
    {
        $account = Account::where('available', '>=', '1.00')->orderBy('available', 'desc')->first();
        if(!$account->session)
            return;

        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($account->session->cookie_file), true);
        $markets = Market::where('active', true)->where('status', true)->get();

        foreach($markets as $market) {

            $selected = [];
            $contracts = $market->contracts;
            $tweetCount = ($market->tweets_current - $market->tweets_start);
            $next = false;

            if(count($contracts) == 0) 
                continue;

            foreach($contracts as $contract) {
                $contract->parseRanges();
                if(($tweetCount >= $contract->MinTweets && $tweetCount <= $contract->MaxTweets) || $next) {
                    $next = count($selected) == 0;
                    $contract->fill(['action' => Contract::BUY, 'type' => Contract::NO]);
                    $selected[] = $contract;
                    if(count($selected) == 2) break;
                }
            }

            if(count($selected) == 0)
                continue;

            foreach($selected as $contract) {

                try {
                    $response = $this->client->request('GET', 'https://www.predictit.org/Trade/Load' . $contract->urlType() .  '?contractId=' . $contract->contract_id, ['cookies' => $jar]);
                } catch (ClientException $e) {
                    Log::error($e->getMessage()); return;
                } catch (ServerException $e) {
                    Log::error($e->getMessage()); return;
                }

                // echo (string)$response->getBody();
                // die();

                $html = new \Htmldom((string)$response->getBody());            
                $rows = $html->find('div.offers tbody tr');
                $tiers = [];
                
                foreach($rows as $key => $row) {
                    if($key == 0) continue;

                    $parts = $row->find('td a');
                    
                    if(!isset($parts[0])) continue;

                    $tier = (object) [
                        'quantity' => (int)trim($parts[0]->plaintext),
                        'price' => (float) (rtrim(trim($parts[1]->plaintext), '¢')/100),
                    ];

                    if($tier->price <= Market::buyNoMin || $tier->price >= Market::buyNoMax) continue;

                    $tiers[] = $tier;
                }

                if(count($tiers) == 0) continue;

                Tier::where('market_id', $market->market_id)->where('contract_id', $contract->contract_id)->delete();

                $insertTiers = [];
                $now = \Carbon\Carbon::now();

                foreach($tiers as $tier) {
                    $insertTiers[] = [
                        'market_id' => $market->market_id,
                        'contract_id' => $contract->contract_id,
                        'quantity' => $tier->quantity,
                        'price' => $tier->price,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('tiers')->insert($insertTiers);
            }
        }
    }

    private function fixShortName(&$contract)
    {
        $len = strlen($contract->ShortName);
        if(strpos($contract->Name, 'or more') !== false && $contract->ShortName[$len - 1] == '-') {
            $contract->ShortName[$len - 1] = '+';
        }

        if(strpos($contract->Name, 'or fewer') !== false && $contract->ShortName[$len - 1] == '+') {
            $contract->ShortName[$len - 1] = '-';
        }

        return $contract->ShortName;
    }

    private function setStartCount(&$market)
    {
        try {
            $response = $this->client->request('GET', $market->url);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $response = (string)$response->getBody();
        $count = $this->getStringBetween($response, ', shall exceed ', ' by the number or range');
        $market->tweets_start = str_replace(',', '', $count);
        $market->tweets_current = $market->tweets_start;
    }

    private function insertTwitter($username) 
    {
        $lookup = TwitterAPI::getUsersLookup(['screen_name' => $username]);

        $twitter = Twitter::create([
            'username'      => $lookup[0]->screen_name,
            'twitter_id'    => $lookup[0]->id_str,
        ]);

        // restart because tweet listener has a new account
        $this->restartDaemon();

        return $twitter->twitter_id;
    }

    private function restartDaemon()
    {

        // $process = new Process('echo ' . config('services.forge.sudo') . ' | sudo -S supervisorctl restart ' . config('services.forge.daemon'));
        // $process->start();
        // $process->wait();

        // try {
        //     $response = $this->client->request('POST', 'https://forge.laravel.com/api/v1/servers/' . config('services.forge.sid') . "/daemons/" . config('services.forge.did') . '/restart', [
        //         'headers' => [
        //             'Authorization'     => 'Bearer ' . config('services.forge.key'),
        //             'Accept'            => 'application/json',
        //             'Content-Type'      => 'application/json',
        //         ]
        //     ]);
        // } catch (ClientException $e) {
        //     Log::error($e->getMessage()); return;
        // } catch (ServerException $e) {
        //     Log::error($e->getMessage()); return;
        // }
    }

    private function getTwitterId($string)
    {
        preg_match("/@([A-Za-z0-9_]{1,15})/", $string, $matches);
        $username = $matches[1];

        $row = Twitter::where('username', $username)->first();

        if(!$row) {
            return $this->insertTwitter($username);
        } else {
            return $row->twitter_id;
        }
    }

    private function getStringBetween($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
