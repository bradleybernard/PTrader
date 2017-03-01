<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Stats\StatsController@showStats');
Route::get('/stats', 'Stats\StatsController@showStats');
Route::get('contract/{contractId}', 'Stats\StatsController@contract');
Route::get('market/{marketId}', 'Stats\StatsController@market');
Route::get('sum/{marketId}', 'Stats\StatsController@sum');
Route::get('accounts', 'Accounts\AccountsController@accounts');
Route::get('account/{account}/refresh', 'Accounts\AccountsController@refresh');

Route::get('login', 'Bet\LoginController@createNewAccountSessions');
Route::get('twitter', 'Scrape\TwitterController@importTweets');
Route::get('markets', 'Scrape\MarketController@scrape');
Route::get('poll', 'Scrape\MarketController@pollContracts');


// Route::get('dispatch', 'Bet\BetController@test');
// Route::get('bet/{twitterId}', 'Bet\BetController@placeBet');
// Route::get('revert', function() {
//     app('App\Http\Controllers\Bet\BetController')->revertNoBets('822215679726100480');
// });
