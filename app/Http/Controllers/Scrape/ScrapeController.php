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
        $options = array_merge(['base_uri'  => $this->baseUri], $this->options);
        $this->client = new Client($options);
    }
}
