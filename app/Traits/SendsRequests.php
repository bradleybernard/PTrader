<?php

namespace App\Traits;

use \GuzzleHttp\Client;

trait SendsRequests {

    protected $client = null;
    protected $options = [];

    public function createClient()
    {
        if(!$this->client) { 
            $options = array_merge(['base_uri'  => $this->baseUri, 'headers' => $this->headers()], $this->options);
            $this->client = new Client($options);
        }

        return $this;
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
}
