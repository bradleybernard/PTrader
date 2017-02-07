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

Route::get('/stats', 'Stats\StatsController@showStats');
Route::get('bet/{twitterId}', 'Bet\BetController@placeBet');
Route::get('login', 'Bet\LoginController@createNewAccountSessions');
Route::get('dispatch', 'Bet\BetController@test');
Route::get('twitter', 'Scrape\TwitterController@importTweets');
Route::get('markets', 'Scrape\MarketController@scrape');
Route::get('poll', 'Scrape\MarketController@pollContracts');
Route::get('contract/{contractId}', 'Stats\StatsController@contract');
Route::get('market/{marketId}', 'Stats\StatsController@market');
