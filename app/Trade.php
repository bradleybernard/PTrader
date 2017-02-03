<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    public function session()
    {
        return $this->belongsTo('App\Session');
    }

    public function market()
    {
        return $this->belongsTo('App\Market', 'market_id', 'market_id');
    }

    public function contract()
    {
        return $this->belongsTo('App\Contract', 'contract_id', 'contract_id');
    }

    protected $guarded = [];
}
