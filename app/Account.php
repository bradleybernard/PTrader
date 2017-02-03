<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \GuzzleHttp\Client;
use App\Session;

class Account extends Model
{
    use Traits\SendsRequests;

    protected $guarded = [];
    protected $baseUri  = 'https://www.predictit.org/';
    
    private $form = '#logoutForm';
    private $input = '__RequestVerificationToken';

    public function sessions()
    {
        return $this->hasMany('App\Session');
    }

    public function session()
    {
        return $this->hasOne('App\Session')->where('active', true);
    }

    public function login() 
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
                    'Email'             => $this->email,
                    'Password'          => decrypt($this->password),
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
            $this->insertSession($file);
            $this->refreshMoney($jar);
        } else {
            Log::error("Login failed for accountId: $this->id");
        }
    }

    public function insertSession($file)
    {
        Session::where('account_id', $this->id)->where('active', true)->update(['active' => false]);
        
        return Session::create([
            'account_id'    => $this->id,
            'cookie_file'   => $file,
            'active'        => true,
        ]);
    }

    public function refreshMoney($jar = NULL)
    {
        if(!$jar) {
            $session = Session::select('cookie_file')->where('account_id', $this->id)->where('active', true)->first();
            $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);
        }

        try {
            $response = $this->client->request('GET', 'Profile/MyShares', ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $html = new \Htmldom((string)$response->getBody());
        $gainLoss = str_replace('$', '', trim($html->find('#acct-value', 0)->find('span', 0)->plaintext));
        $invested = str_replace('$', '', trim($html->find('#committed', 0)->find('span', 0)->plaintext));
        $available = str_replace('$', '', trim($html->find('#avail-low', 0)->find('span', 0)->plaintext));

        $this->update([
            'available' => $available,
            'gain_loss' => $gainLoss,
            'invested' => $invested,
        ]);
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
