<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('account_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->bigInteger('order_id')->unsigned();
            $table->bigInteger('market_id')->unsigned();
            $table->integer('contract_id')->unsigned();
            $table->string('type');
            $table->integer('quantity')->unsigned();
            $table->decimal('price_per_share', 10, 4)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
