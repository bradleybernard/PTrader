<?php

namespace App\Http\Controllers\Bet;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;
use App\Account;

class LoginController extends ScrapeController
{

    public function createNewAccountSessions()
    {
        $accounts = Account::all();

        foreach($accounts as $account) {
            $account->createClient()->login();
        }
    }
}
