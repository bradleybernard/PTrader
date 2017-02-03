<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo('App\Account');
    }
}
