<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    protected $guarded = [];

    public function twitter()
    {
        return $this->belongsTo('App\Twitter', 'twitter_id', 'twitter_id');
    }
}
