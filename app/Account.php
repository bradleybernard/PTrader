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

    public function trades()
    {
        return $this->hasMany('App\Trade');
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
            $this->removeExpired();
            $this->insertSession($file);
            $this->refreshMoney($jar);
        } else {
            Log::error("Login failed for accountId: $this->id");
        }
    }

    public function removeExpired()
    {
        $expired = Session::where('account_id', $this->id)->where('expires_after', '<=', \Carbon\Carbon::now())->get();
        if($expired->count() == 0) {
            return;
        }

        foreach($expired as $expire) {
            @unlink(storage_path($expire->cookie_file));
        }

        Session::whereIn('id', $expired->pluck('id')->toArray())->delete();
    }

    public function insertSession($file)
    {
        Session::where('account_id', $this->id)->where('active', true)->update(['active' => false]);
        
        return Session::create([
            'account_id'    => $this->id,
            'cookie_file'   => $file,
            'active'        => true,
            'expires_after' => \Carbon\Carbon::now()->addDays(1),
        ]);
    }

    public function refreshMoney($jar = NULL)
    {
        $this->createClient();
        
        if(!$jar) {
            $session = $this->session;
            $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path($session->cookie_file), true);
        }

        try {
            $response = $this->client->request('GET', 'PrivateData/_UserFundsMainNav', ['cookies' => $jar]);
        } catch (ClientException $e) {
            Log::error($e->getMessage()); return;
        } catch (ServerException $e) {
            Log::error($e->getMessage()); return;
        }

        $response = json_decode((string)$response->getBody());

        $gainLoss = (double)str_replace('$', '', $response->SharesText);
        $invested = (double)str_replace('$', '', $response->PortfolioText);
        $available = (double)str_replace('$', '', $response->BalanceText);

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
