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

Route::get('bet/{twitterId}', 'Bet\BetController@placeBet');
Route::get('login', 'Bet\LoginController@refreshSessions');
Route::get('dispatch', 'Bet\BetController@test');
Route::get('twitter', 'Scrape\TwitterController@importTweets');
