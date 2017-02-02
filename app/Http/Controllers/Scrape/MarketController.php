<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use DB;
use Twitter;

class MarketController extends ScrapeController
{
    private $search = 'How many tweets will @';
    private $timezone = 'EST';

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

            $exists = collect(DB::table('contracts')->whereIn('contract_id', $contracts->pluck('contract_id'))->get());

            if($exists->count() > 0)
                $exists = $exists->pluck('contract_id');

            $exists = $exists->toArray();

            $filtered = $contracts->filter(function ($value, $key) use ($exists) {
                if(!in_array($value['contract_id'], $exists)) {
                    return $value;
                }
            });

            DB::table('contracts')->insert($filtered->toArray());
        }

        DB::table('markets')->update(['active' => false]);
        if(count($markets) > 0) {
            foreach($markets as $market) {
                $exists = DB::table('markets')->where('market_id', $market['market_id'])->count();
                if($exists == 0) {
                    DB::table('markets')->insert($market);
                } else {
                    DB::table('markets')->where('market_id', $market['market_id'])->update(['active' => true]);
                }
            }
        }
    }

    private function insertTwitter($username) 
    {
        $lookup = Twitter::getUsersLookup(['screen_name' => $username]);

        DB::table('twitter')->insert([
            'username'      => $lookup[0]->screen_name,
            'twitter_id'    => $lookup[0]->id_str,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ]);

        return $lookup;
    }

    private function getTwitterId($string)
    {
        preg_match("/@([A-Za-z0-9_]{1,15})/", $string, $matches);
        $username = $matches[1];

        $row = DB::table('twitter')->where('username', $username)->first();

        if(!$row) {
            return $this->insertTwitter($username)[0]->id_str;
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
