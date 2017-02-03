<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Twitter extends Model
{
    protected $table = 'twitter';
    protected $guarded = [];

    public function tweets()
    {
        return $this->hasMany('App\Tweet', 'twitter_id', 'twitter_id');
    }
}
