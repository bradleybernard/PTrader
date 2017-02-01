<?php

namespace App\Http\Controllers\Bet;

use Illuminate\Http\Request;
use App\Http\Controllers\Scrape\ScrapeController;

use DB;

class LoginController extends ScrapeController
{
    protected $baseUri  = 'https://www.predictit.org/';

    private $form = '#logoutForm';
    private $input = '__RequestVerificationToken';

    public function refreshSessions()
    {
        $accounts = DB::table('accounts')->get();

        foreach($accounts as $account) {
            $this->login($account);
        }
    }

    public function login($account) 
    {
        $file = 'cookies/' . str_random(10) . '.json';
        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($file), true);

        $token = $this->getToken($jar);

        if(!$token) {
            return;
        }

        try {
            $response = $this->client->request('POST', 'Account/LogIn', [
                'cookies' => $jar,
                'form_params' => [ 
                    $this->input        => $token,
                    'ReturnUrl'         => '/',
                    'Email'             => $account->email,
                    'Password'          => decrypt($account->password),
                    'RememberMe'        => 'true',
                    'X-Requested-With'  => 'XMLHttpRequest',
                ],
            ]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $response = json_decode((string)$response->getBody());
        if($response->IsSuccess === true) {
            DB::table('sessions')->where('account_id', $account->id)->update(['active' => false]);
            DB::table('sessions')->insert([
                'account_id'    => $account->id,
                'cookie_file'   => $file,
                'active'        => true,
                'created_at'    => \Carbon\Carbon::now(),
                'updated_at'    => \Carbon\Carbon::now(),
            ]);
        } else {
            Log::error("Login failed for accountId: $account->id");
        }
    }

    private function getToken($jar)
    {
        try {
            $response = $this->client->request('GET', '', ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $html = new \Htmldom((string)$response->getBody());
        $token = $html->find($this->form, 0)->find('input[name="' . $this->input . '"]', 0)->value;

        return $token;
    }
}
