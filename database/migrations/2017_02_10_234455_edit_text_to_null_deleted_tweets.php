<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditTextToNullDeletedTweets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deleted_tweets', function (Blueprint $table) {
            $table->text('text')->nullable()->change();
            $table->datetime('api_created_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deleted_tweets', function (Blueprint $table) {
            $table->text('text')->nullable(false)->change();
            $table->datetime('api_created_at')->nullable(false)->change();
        });
    }
}
