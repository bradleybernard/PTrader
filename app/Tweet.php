<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\DeletedTweet;

class Tweet extends Model
{
    protected $guarded = [];

    public function twitter()
    {
        return $this->belongsTo('App\Twitter', 'twitter_id', 'twitter_id');
    }

    public function markDeleted()
    {
        $count = DeletedTweet::where('tweet_id', $this->tweet_id)->count();

        if($count != 0) {
            return;
        }

        DeletedTweet::create([
            'twitter_id' => $this->twitter_id,
            'tweet_id' => $this->tweet_id,
            'text' => $this->text,
            'api_created_at' => $this->api_created_at,
        ]);
    }
}
