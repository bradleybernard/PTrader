<?php

namespace App\Http\Controllers\Scrape;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \GuzzleHttp\Client;

class ScrapeController extends Controller
{
    protected $client   = null;
    protected $baseUri  = 'https://www.predictit.org/api/marketdata/';
    protected $options  = [];
    
    public function __construct()
    {
        $options = array_merge(['base_uri'  => $this->baseUri, 'headers' => $this->headers()], $this->options);
        $this->client = new Client($options);
    }

    private function headers()
    {
        return [
            'Pragma'            => 'no-cache',
            'Origin'            => 'www.predictit.org',
            'Accept-Encoding'   => 'gzip, deflate, br',
            'User-Agent'        => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.13 Safari/537.36',
            'Accept'            => '*/*',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'Dnt'               => 1,
        ];
    }

    protected function clean($ele, $buyNo = null)
    {
        if($ele) {
            return $ele;
        }

        if($buyNo === true) {
            return 1.05;
        }

        if($buyNo === false) {
            return -0.05;
        }

        return 0;
    }
}
