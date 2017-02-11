<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use TwitterAPI;

use App\Market;
use App\Contract;
use App\Twitter;
use App\ContractHistory;

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

            $history = [];
            foreach($response->Contracts as $contract) {
                $history[] = [
                    'contract_id' => $contract->ID,
                    'last_trade_price' => $this->clean($contract->LastTradePrice),
                    'best_buy_yes_cost' => $this->clean($contract->BestBuyYesCost),
                    'best_buy_no_cost' => $this->clean($contract->BestBuyNoCost, true),
                    'best_sell_yes_cost' => $this->clean($contract->BestSellYesCost, false),
                    'best_sell_no_cost' => $this->clean($contract->BestSellNoCost),
                    'last_close_price' => $this->clean($contract->LastClosePrice),
                ];
            }

            if(count($history) == 0) {
                continue;
            }

            ContractHistory::insert($history);
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
                    'short_name' => $contract->ShortName,
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
                if(!$model->exists) 
                    $this->setStartCount($model);
                $model->save();
            }
        }
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

        return $twitter->twitter_id;
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
