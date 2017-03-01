<?php

namespace App\Http\Controllers\Accounts;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Account;

class AccountsController extends Controller
{
    public function accounts()
    {
        $accounts = Account::with('trades')->get()->sortByDesc('available');
        return view('accounts')->with('accounts', $accounts);
    }

    public function refresh(Account $account)
    {
        $account->refreshMoney();

        return redirect('/accounts');
    }
}
